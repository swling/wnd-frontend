<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.01.21 获取do page地址
 */
function wnd_get_do_url() {

	$do_page = wnd_get_option('wnd', 'wnd_do_page');
	$do_url = $do_page ? get_the_permalink($do_page) : WNDWP_URL . 'do.php';
	return $do_url;
}

/**
 *@since 2019.04.07
 */
function wnd_doing_ajax() {

	if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest") {
		return true;
	} else {
		return false;
	}
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

	return (
		isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/bot|crawl|slurp|spider|mediapartners/i', $_SERVER['HTTP_USER_AGENT'])
	);

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
 *@since 2019.03.04
 *生成包含当前日期信息的高强度的唯一性ID
 */
function wnd_generate_order_NO() {
	$today = date("Ymd");
	$rand = substr(hash('sha256', uniqid(rand(), TRUE)), 0, 10);
	return $today . $rand;
}

/**
 *@since 2019.03.04
 *基于当前站点的首页地址，生成四位字符站点前缀标识符
 */
function wnd_get_site_prefix() {
	return strtoupper(substr(md5(home_url()), 0, 4));
}

/**
 * @since 2019.02.09  验证是否为手机号
 */
function wnd_is_phone($phone) {
	if ((empty($phone) || !preg_match("/^(((13[0-9]{1})|(15[0-9]{1})|(17[0-9]{1})|(18[0-9]{1}))+\d{8})$/", $phone))) {
		return false;
	} else {
		return true;
	}

}
