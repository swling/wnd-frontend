<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 初始化
 *下载文件
 *通过php脚本的方式将文件发送到浏览器下载，避免保留文件的真实路径
 *然而，用户仍然可能通过文件名和网站结构，猜测到可能的真实路径，
 *因此建议将$file定义在网站目录之外，这样通过任何url都无法访问到文件存储目录
 *主要用户付费下载
 *@param $the_file string 本地或远程完整文件地址
 */
function wnd_download_file($the_file, $rename = 'download') {

	// 获取文件后缀信息
	$ext = '.' . pathinfo($the_file)['extension'];	

	// Force download
	header("Content-type: application/x-file-to-save");
	header("Content-Disposition: attachment; filename=" . get_option('blogname') . '-' . $rename . $ext);
	ob_end_clean();
	readfile($the_file);
	exit;

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
