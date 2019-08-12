<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *增强版nonce校验，在nonce校验中加入秘钥
 *@since 2019.05.12
 **/
function wnd_create_nonce($action) {

	$secret_key = wnd_get_option('wnd', 'wnd_secret_key');
	return wp_create_nonce(md5($action . $secret_key));
}

/**
 *校验nonce
 *@since 2019.05.12
 **/
function wnd_verify_nonce($nonce, $action) {

	$secret_key = wnd_get_option('wnd', 'wnd_secret_key');
	return wp_verify_nonce($nonce, md5($action . $secret_key));

}

/**
 *@since 2019.01.21 获取do page地址
 *一个没有空白的WordPress环境，接收或执行一些操作
 */
function wnd_get_do_url() {

	return WND_URL . 'do.php';
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
 * @since 2019.02.09  验证是否为手机号
 */
function wnd_is_phone($phone) {
	if ((empty($phone) || !preg_match("/^(((13[0-9]{1})|(15[0-9]{1})|(17[0-9]{1})|(18[0-9]{1}))+\d{8})$/", $phone))) {
		return false;
	} else {
		return true;
	}

}

/**
 *复制taxonomy term数据到 另一个 taxonomy下
 *@since 2019.04.30
 */
function wnd_copy_taxonomy($old_taxonomy, $new_taxonomy) {

	$terms = get_terms($old_taxonomy, 'hide_empty=0');

	if (!empty($terms) && !is_wp_error($terms)) {

		foreach ($terms as $term) {

			wp_insert_term($term->name, $new_taxonomy);

		}
		unset($term);

	}

}

/**
 * @since 2019.06.12
 * 获取当前页面查询类型基本信息
 * 通常用于在ajax请求中传递请求页面信息以供后端判断请求来源
 * */
function wnd_get_queried_type() {

	if (is_single()) {
		return array('type' => 'single', 'ID' => get_queried_object()->ID);

	} elseif (is_page()) {
		return array('type' => 'page', 'ID' => get_queried_object()->ID);

	} elseif (is_tax()) {
		return array('type' => 'tax', 'ID' => get_queried_object()->term_id);

	} elseif (is_home()) {
		return array('type' => 'home');

	} else {

		return array('type' => 'ajax');
	}
}

/**
 *@since 2019.07.17
 *设置默认的异常处理函数
 */
set_exception_handler('wnd_exception_handler');
function wnd_exception_handler($exception) {

	$html = '<article class="column message is-danger">';
	$html .= '<div class="message-header">';
	$html .= '<p>异常</p>';
	$html .= '</div>';
	$html .= '<div class="message-body">' . $exception->getMessage() . '</div>';
	$html .= '</article>';

	echo $html;
}
