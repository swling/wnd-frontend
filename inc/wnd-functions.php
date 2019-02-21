<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.01.21 获取do page地址
 */
function wnd_get_do_url() {

	$do_page = wnd_get_option('wndwp', 'wnd_do_page');
	$do_url = $do_page ? get_the_permalink($do_page) : WNDWP_URL . 'do.php';
	return $do_url;
}

/**
 *@since 初始化
 *获取用户ip
 */
function wnd_get_user_ip($hidden = false) {

	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	if ($hidden) {
		return preg_replace('/(\d+)\.(\d+)\.(\d+)\.(\d+)/is', "$1.$2.$3.*", $ip);
	} else {
		return $ip;
	}

}

/**
 *@since 初始化
 *搜索引擎判断
 */
function wnd_is_robot() {

	$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
	$spiders = array(
		'Googlebot', // Google 爬虫
		'Baiduspider', // 百度爬虫
		'spider',
		// 更多爬虫关键字
	);

	foreach ($spiders as $spider) {
		$spider = strtolower($spider);
		if (strpos($userAgent, $spider) !== false) {
			return true;
		}
	}

	return false;

}

/**
 *@since 2019.01.30
 *获取随机大小写字母和数字组合字符串
 */
function wnd_random($length) {
	$chars = '123456789abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ';
	$hash = '';
	$max = strlen($chars) - 1;
	for ($i = 0; $i < $length; $i++) {
		$hash .= $chars[mt_rand(0, $max)];
	}
	return $hash;
}

/**
 *@since 初始化
 *生成N位随机数字
 */
function wnd_random_code($length = 6) {

	$No = '';
	for ($i = 0; $i < $length; $i++) {
		$No .= mt_rand(0, 9);
	}
	return $No;

}

/**
 * @since 2019.02.09  验证是否为手机号
 */
function wnd_is_phone($phone) {
	if ((empty($phone) || !preg_match("/^(((13[0-9]{1})|(15[0-9]{1})|(17[0-9]{1})|(18[0-9]{1}))+\d{8})$/", $phone))) {
		return 0;
	} else {
		return 1;
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
function wnd_download_file($file, $rename = '') {

	//检查文件是否存在
	if (!file_exists($file)) {
		echo '文件不存在';
		exit();
	}

	// 获取文件信息
	$ext = '.' . pathinfo($file)['extension'];

	//打开文件
	$the_file = fopen($file, "r");
	Header("Content-type: application/octet-stream");
	Header("Accept-Ranges: bytes");
	Header("Accept-Length: " . filesize($file));

	/**
	 * 重命名文件名，防止当文件上传到网站公共目录下时，用户可通过文件名猜测路径绕道直接下载
	 *（上传时已通过filter wp_handle_upload_prefilter 	md5加密文件名）
	 */
	Header("Content-Disposition: attachment; filename=" . get_option('blogname') . '-' . $rename . $ext);

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