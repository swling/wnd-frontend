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
 * @since 2019.01.16
*/
function _wnd_update_post_views() {

	$post_id = (int) $_POST['post_id'];
	if (!$post_id) {
		return;
	}
	$useragent = $_POST['useragent'];
	$should_count = true;

	// 根据 useragent 排除搜索引擎
	$bots = array
		(
		'Google Bot' => 'google'
		, 'MSN' => 'msnbot'
		, 'Alex' => 'ia_archiver'
		, 'Lycos' => 'lycos'
		, 'Ask Jeeves' => 'jeeves'
		, 'Altavista' => 'scooter'
		, 'AllTheWeb' => 'fast-webcrawler'
		, 'Inktomi' => 'slurp@inktomi'
		, 'Turnitin.com' => 'turnitinbot'
		, 'Technorati' => 'technorati'
		, 'Yahoo' => 'yahoo'
		, 'Findexa' => 'findexa'
		, 'NextLinks' => 'findlinks'
		, 'Gais' => 'gaisbo'
		, 'WiseNut' => 'zyborg'
		, 'WhoisSource' => 'surveybot'
		, 'Bloglines' => 'bloglines'
		, 'BlogSearch' => 'blogsearch'
		, 'PubSub' => 'pubsub'
		, 'Syndic8' => 'syndic8'
		, 'RadioUserland' => 'userland'
		, 'Gigabot' => 'gigabot'
		, 'Become.com' => 'become.com'
		, 'Baidu' => 'baiduspider'
		, 'so.com' => '360spider'
		, 'Sogou' => 'spider'
		, 'soso.com' => 'sosospider'
		, 'Yandex' => 'yandex',
	);

	foreach ($bots as $name => $lookfor) {
		if (!empty($useragent) && (stristr($useragent, $lookfor) !== false)) {
			$should_count = false;
			break;
		}
	}

	// 统计
	if ($should_count) 	{

		wnd_inc_post_meta($post_id, 'views', 1);

		// 完成统计时附加动作
		do_action( 'wnd_update_post_views', $post_id );

		// 统计更新成功
		return array('status' => 1, 'msg' =>time() );

	}else{

		// 未更新
		return array('status' => 0, 'msg' =>time() );

	}

}
