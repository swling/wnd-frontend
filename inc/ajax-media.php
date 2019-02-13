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

	$save_width = (int) $_POST["save_width"] ?? 0;
	$save_height = (int) $_POST["save_height"] ?? 0;
	$meta_key = $_POST['meta_key'] ?? 0;
	$post_parent = (int) $_POST['post_parent'] ?? 0;
	$user_id = get_current_user_id();

	// 定义二维返回数组，以支持多文件上传
	$return_array = array();

	if (!$user_id and !$post_parent) {
		$temp_array = array('status' => 0, 'msg' => '错误：user ID及post ID均为空！');
		array_push($return_array, $temp_array);
		return $return_array;
	}

	if (empty($_FILES)) {
		$temp_array = array('status' => 0, 'msg' => '获取上传文件失败！');
		array_push($return_array, $temp_array);
		return $return_array;
	}

	if (0 < $_FILES['file']['error']) {
		$temp_array = array('status' => 0, 'msg' => 'Error: ' . $_FILES['file']['error']);
		array_push($return_array, $temp_array);
		return $return_array;
	}

	// 遍历文件上传
	foreach ($_FILES as $file => $array) {
		//上传文件并附属到对应的post 默认为0 即不附属到
		$attachment_id = media_handle_upload($file, $post_parent);

		// 上传失败
		if (is_wp_error($attachment_id)) {
			$temp_array = array('status' => 0, 'msg' => $attachment_id->get_error_message());
			array_push($return_array, $temp_array);
			continue;
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

		// 将当前上传的图片信息写入数组
		$temp_array = array('status' => 1, 'msg' => array('url' => wp_get_attachment_url($attachment_id), 'id' => $attachment_id));
		array_push($return_array, $temp_array);

		/**
		*@since 2019.02.13 当存在meta key时，表明上传文件为特定用途存储，仅允许上传单个文件 
		*/
		if($meta_key){
			break;
		}
		
	}
	unset($file, $array);

	// 返回上传信息二维数组合集
	return $return_array;

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
 *@since 2019.02.12 文件校验下载
 */
function wnd_paid_download() {

	$post_id = (int) $_REQUEST['post_id'];
	$price = get_post_meta($post_id, 'price', 1);
	$attachment_id = wnd_get_post_meta($post_id, 'file') ?: get_post_meta($post_id, 'file');

	$file = get_attached_file($attachment_id, $unfiltered = true);
	if (!$file) {
		wp_die('获取文件失败！', get_option('blogname'));
	}

	/**
	 *@since 2019.02.12
	 *此处必须再次校验用户下载权限。
	 *否则用户下载一次后即可获得ajax_nonce，从而在24小时内可以通过ajax校验
	 *此期间通过修改 post_id 可参数下载其他为经过权限校验的文件
	 *校验方式：
	 *1、通过生成特定nonce，$action必须包含 $post_id 或$attachment_id以确保文件唯一性，且改nonce不得通过其他任何获得
	 *（wp_nonce已包含了当前用户数据）
	 *2、再次完整验证用户权限
	 *（安全性更高，但重复验证稍显繁琐）
	 */

	/**
	*@since 2019.02.12 nonce验证
	*/
	// $action  = $post_id.'_paid_download_key';
	// if(wp_verify_nonce( $_REQUEST['_download_key'], $action )){
	// 	return wnd_download_file($file, $post_id);
	// }

	/**
	*@since 2019.02.12 重复权限验证
	*/
	$user_id = get_current_user_id();
	//1、免费，或者已付费
	if (!$price or wnd_user_has_paid($user_id, $post_id)) {
		return wnd_download_file($file, $post_id);
	}

	//2、 作者直接下载
	if (get_post_field('post_author', $post_id) == get_current_user_id()) {
		return wnd_download_file($file, $post_id);
	}

	// 校验失败
	wp_die('下载权限校验失败！', get_option('blogname'));

}