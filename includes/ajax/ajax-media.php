<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}
/**
 *媒体处理
 */

/**
 *@since 2019.01.20
 *ajax文件上传
 *@param $_FILES
 *@param $_POST["save_width"]
 *@param $_POST["save_height"]
 *@param $_POST["thumbnail_width"]
 *@param $_POST["thumbnail_height"]
 *@param $_POST['meta_key']
 *@param $_POST['_meta_key_nonce']
 *@param $_POST['post_parent']
 *
 *@return $return_array array 二维数组
 *	array(
 *		array(
 *			'status' => 1,
 *			'data' => array('url' => $url, 'thumbnail' => $thumbnail ?? 0, 'id' => $file_id),
 *			'msg' => '上传成功！',
 *		),
 *	);
 *
 */
function wnd_ajax_upload_file() {

	//$_FILES['wnd_file']需要与input name 值匹配
	if (empty($_FILES['wnd_file'])) {
		return array('status' => 0, 'msg' => '获取上传文件失败！');
	}

	$save_width = $_POST["save_width"] ?? 0;
	$save_height = $_POST["save_height"] ?? 0;
	$thumbnail_height = $_POST["thumbnail_height"] ?? 0;
	$thumbnail_width = $_POST["thumbnail_width"] ?? 0;
	$meta_key = $_POST['meta_key'] ?? null;
	$post_parent = $_POST['post_parent'] ?? 0;
	$user_id = get_current_user_id();

	// 上传信息校验
	if (!$user_id and !$post_parent) {
		return array('status' => 0, 'msg' => '错误：user ID及post ID均为空！');
	}

	/**
	 *@since 2019.05.08 上传文件meta_key post_parent校验
	 *meta_key 及 post_parent同时为空时，上传文件将成为孤立的的文件，在前端上传附件应该具有明确的用途，应避免这种情况
	 */
	if (!$meta_key and !$post_parent) {
		return array('status' => 0, 'msg' => '错误：meta_key 与 post_parent 同时为空！');
	}

	if (!wnd_verify_nonce($_POST['meta_key_nonce'], $meta_key)) {
		return array('status' => 0, 'msg' => '错误：未经允许的meta_key！');
	}

	if ($post_parent and !get_post($post_parent)) {
		return array('status' => 0, 'msg' => 'post_parent无效！');
	}

	/**
	 *@since 2019.04.16
	 *上传权限过滤
	 */
	$can_upload_file = apply_filters('wnd_can_upload_file', array('status' => 1, 'msg' => '默认通过'), $post_parent, $meta_key);
	if ($can_upload_file['status'] === 0) {
		return $can_upload_file;
	}

	// These files need to be included as dependencies when on the front end.
	if (!is_admin()) {
		require ABSPATH . 'wp-admin/includes/image.php';
		require ABSPATH . 'wp-admin/includes/file.php';
		require ABSPATH . 'wp-admin/includes/media.php';
	}

	/**
	 *@since 2019.05.06 改写
	 *遍历文件上传
	 */
	$return_array = array(); // 定义图片信息返回数组
	$files = $_FILES['wnd_file']; //暂存原始上传信息，后续将重写$_FILES全局变量以适配WordPress上传方式

	foreach ($files['name'] as $key => $value) {

		// 将多文件上传数据遍历循环后，重写为适配 media_handle_upload 的单文件模式
		$file = array(
			'name' => $files['name'][$key],
			'type' => $files['type'][$key],
			'tmp_name' => $files['tmp_name'][$key],
			'error' => $files['error'][$key],
			'size' => $files['size'][$key],
		);
		$_FILES = array('temp_key' => $file);

		// 单文件错误检测
		if ($_FILES['temp_key']['error'] > 0) {
			array_push($return_array, array('status' => 0, 'msg' => 'Error: ' . $_FILES['temp_key']['error']));
			continue;
		}

		//上传文件并附属到对应的post parent 默认为0 即孤立文件
		$file_id = media_handle_upload('temp_key', $post_parent);

		// 上传失败
		if (is_wp_error($file_id)) {
			array_push($return_array, array('status' => 0, 'msg' => $file_id->get_error_message()));
			continue;
		}

		$url = wp_get_attachment_url($file_id);

		// 判断是否为图片
		if (strrpos($file['type'], 'image') !== false) {

			//根据尺寸进行图片裁剪
			if ($save_width or $save_height) {
				//获取文件服务器路径
				$image_file = get_attached_file($file_id);
				$image = wp_get_image_editor($image_file);
				if (!is_wp_error($image)) {
					$image->resize($save_width, $save_height, array('center', 'center'));
					$image->save($image_file);
				}
			}

			// 返回缩略图
			$thumbnail = wnd_get_thumbnail_url($url, $thumbnail_width, $thumbnail_height);
		}

		// 将当前上传的图片信息写入数组
		$temp_array = array(
			'status' => 1,
			'data' => array(
				'url' => $url,
				'thumbnail' => $thumbnail ?? 0,
				'id' => $file_id,
				'post' => get_post($file_id),
			),
			'msg' => '上传成功！',
		);
		array_push($return_array, $temp_array);

		/**
		 *@since 2019.02.13 当存在meta key时，表明上传文件为特定用途存储，仅允许上传单个文件
		 *@since 2019.05.05 当meta key == gallery 表示为上传图集相册 允许上传多个文件
		 */
		if ($meta_key != 'gallery') {
			//处理完成根据用途做下一步处理
			do_action('wnd_upload_file', $file_id, $post_parent, $meta_key);
			break;
		}

	}

	/**
	 *@since 2019.05.05 当meta key == gallery 表示为上传图集相册 允许上传多个文件
	 */
	if ($meta_key == 'gallery') {
		do_action('wnd_upload_gallery', $return_array, $post_parent);
	}

	// 返回上传信息二维数组合集
	return $return_array;
}

/**
 *删除附件
 *@since 2019.01.23
 *@param $_POST['meta_key'];
 *@param $_POST["post_parent"];
 *@param $_POST["file_id"];
 */
function wnd_ajax_delete_file() {
	$meta_key = $_POST['meta_key'];
	$post_parent = $_POST["post_parent"];
	$file_id = $_POST["file_id"];

	if (!$file_id) {
		return array('status' => 0, 'msg' => '文件不存在！');
	}

	if (!current_user_can('edit_post', $file_id)) {
		return array('status' => 0, 'msg' => '权限错误或文件不存在！');
	}

	// 执行删除
	if (wp_delete_attachment($file_id, true)) {

		do_action('wnd_delete_file', $file_id, $post_parent, $meta_key);
		return array('status' => 1, 'msg' => $file_id);

		//删除失败
	} else {

		return array('status' => 0, 'msg' => '权限错误！');

	}
}

/**
 *@since 2019.02.12 文件校验下载
 *@param $_REQUEST['post_id']
 */
function wnd_ajax_paid_download() {
	$post_id = (int) $_REQUEST['post_id'];
	$price = get_post_meta($post_id, 'price', 1);
	$file_id = wnd_get_post_meta($post_id, 'file') ?: get_post_meta($post_id, 'file');

	$file = get_attached_file($file_id, $unfiltered = false);
	if (!$file) {
		wp_die('获取文件失败！', get_option('blogname'));
	}

	/**
	 *@since 2019.02.12
	 *此处必须再次校验用户下载权限。
	 *否则用户下载一次后即可获得ajax_nonce，从而在24小时内可以通过ajax校验
	 *此期间通过修改 post_id 可参数下载其他为经过权限校验的文件
	 *校验方式：
	 *1、通过生成特定nonce，$action必须包含 $post_id 或$file_id以确保文件唯一性，且改nonce不得通过其他任何获得
	 *（wp_nonce已包含了当前用户数据）
	 *2、再次完整验证用户权限
	 *（安全性更高，但重复验证稍显繁琐）
	 */

	/**
	 *@since 2019.02.12 nonce验证
	 */
	// $action  = $post_id.'_paid_download_key';
	// if(wnd_verify_nonce( $_REQUEST['_download_key'], $action )){
	// 	return wnd_download_file($file, $post_id);
	// }

	/**
	 *@since 2019.02.12 重复权限验证
	 */
	$user_id = get_current_user_id();
	//1、免费，或者已付费
	if (!$price or wnd_user_has_paid($user_id, $post_id)) {
		wnd_inc_wnd_post_meta($post_id, 'download_count', 1);
		return wnd_download_file($file, $post_id);
	}

	//2、 作者直接下载
	if (get_post_field('post_author', $post_id) == get_current_user_id()) {
		return wnd_download_file($file, $post_id);
	}

	// 校验失败
	wp_die('下载权限校验失败！', get_option('blogname'));
}
