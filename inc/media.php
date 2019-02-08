<?php

/**
 *媒体处理
 */

/**
 *@since 2019.01.20
 *ajax文件上传
 *
 */
function wnd_upload_file() {

	// These files need to be included as dependencies when on the front end.
	if (!is_admin()) {
		require ABSPATH . 'wp-admin/includes/image.php';
		require ABSPATH . 'wp-admin/includes/file.php';
		require ABSPATH . 'wp-admin/includes/media.php';
	}

	$files = $_FILES["file"];
	$save_width = (int) $_POST["save_width"] ?? 0;
	$save_height = (int) $_POST["save_height"] ?? 0;
	$meta_key = $_POST['meta_key'] ?? 0;
	$post_parent = (int) $_POST['post_parent'] ?? 0;
	$user_id = get_current_user_id();
	
	if(!$user_id and !$post_parent){
		return array('status'=>0,'msg'=>'错误：user ID及post ID均为空！');
	}

	if (empty($files)) {
		return array('status' => 0, 'msg' => '获取上传文件失败！');
	}

	if (0 < $_FILES['file']['error']) {
		return array('status' => 0, 'msg' => 'Error: ' . $_FILES['file']['error']);
	}

	foreach ($_FILES as $file => $array) {
		//上传文件并附属到对应的post 默认为0 即不附属到
		$attachment_id = media_handle_upload($file, $post_parent);

		// 上传失败
		if (is_wp_error($attachment_id)) {
			return array('status' => 0, 'msg' => $attachment_id->get_error_message());
		}

		//上传成功，根据尺寸进行图片裁剪
		if ($save_width or $save_height) {
			//获取文件服务器路径
			$image_file = get_attached_file($attachment_id);
			$image = wp_get_image_editor($image_file);
			if (!is_wp_error($image)) {
				$image->resize($save_width, $save_height, array('center', 'center'));
				$image->save($image_file);
			}
		}
		//处理完成根据用途做下一步处理
		do_action('wnd_upload_file', $attachment_id, $post_parent, $meta_key);

		// 将当前上传的图片信息返回
		return array('status' => 1, 'msg' => array('url' => wp_get_attachment_url($attachment_id), 'id' => $attachment_id));
	}
	unset($file, $array);
}

/**
 *删除附件
 *@since 2019.01.23
 */
function wnd_delete_attachment() {

	$meta_key = $_POST['meta_key'];
	$post_parent = $_POST["post_parent"];
	$attachment_id = $_POST["attachment_id"];

	if (!$attachment_id) {
		return array('status' => 0, 'msg' => '文件不存在！');
	}

	if (!current_user_can('edit_post', $attachment_id)) {
		return array('status' => 0, 'msg' => '权限错误或文件不存在！');
	}

	// 执行删除
	if (wp_delete_attachment($attachment_id)) {

		do_action('wnd_delete_attachment', $attachment_id, $post_parent, $meta_key);
		return array('status' => 1, 'msg' => $attachment_id);

		//删除失败
	} else {

		return array('status' => 0, 'msg' => '权限错误！');

	}

}

/**
 *@since 初始化
 *下载文件
 *通过php脚本的方式将文件发送到浏览器下载，避免保留文件的真实路径
 *然而，用户仍然可能通过文件名和网站结构，猜测到可能的真实路径，
 *因此建议将$file定义在网站目录之外，这样通过任何url都无法访问到文件存储目录
 *主要用户付费下载
 */
function wnd_download_file($file, $parent_id = 0) {

	//检查文件是否存在
	if (!file_exists($file)) {
		echo "文件找不到";
		exit();

	}

	// 获取文件信息
	$name = get_option('blogname');
	$ext = '.' . pathinfo($file)['extension'];

	//打开文件
	$the_file = fopen($file, "r");
	Header("Content-type: application/octet-stream");
	Header("Accept-Ranges: bytes");
	Header("Accept-Length: " . filesize($file));
	Header("Content-Disposition: attachment; filename=" . $name . '-' . $parent_id . $ext);// 重命名文件名，防止用户通过文件名绕道直接下载
	//读取文件内容并直接输出到浏览器
	echo fread($the_file, filesize($file));
	fclose($the_file);
	exit();

}

/**
 *@since 2019.01.22
 *保存文章中的外链图片，并替换html图片地址
 */
function wnd_save_content_images($content, $upload_dir, $post_id) {

	if (empty($content)) {
		return;
	}

	$preg = preg_match_all('/<img.*?src="(.*?)"/', stripslashes($content), $matches);

	if ($preg) {
		$i = 1;
		foreach ($matches[1] as $image_url) {
			if (empty($image_url)) {
				continue;
			}

			$pos = strpos($image_url, $upload_dir); // 判断图片链接是否为外链
			if ($pos === false) {
				$replace = wnd_save_remote_image($image_url, $post_id, time() . '-' . $i);
				// 完成替换
				$content = str_replace($image_url, $replace, $content);
			}
			$i++;
		}
		unset($image_url);
	}

	return $content;

}

/**
 *@since 2019.01.22
 *WordPress 远程下载图片 并返回上传后的图片地址
 */
function wnd_save_remote_image($url, $post_parent, $desc) {

	if (!function_exists('media_sideload_image')) {
		require ABSPATH . 'wp-admin/includes/media.php';
		require ABSPATH . 'wp-admin/includes/file.php';
		require ABSPATH . 'wp-admin/includes/image.php';
	}
	$image_src = media_sideload_image($url, $post_parent, $desc, 'src');
	return $image_src;
}