<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.02.19 在当前位置自动生成一个容器，以供ajax嵌入模板
 *@param $template 	string  			被调用函数(必须以 _wnd为前缀)
 *@param $args 		array or string 	传递给被调用模板函数的参数
 */
function _wnd_ajax_embed($template, $args = '') {

	$function_name = $template;

	$div_id = 'wnd-filter-' . uniqid();
	$args = wp_parse_args($args);
	$ajax_args = http_build_query($args);

	echo '<div id="' . $div_id . '">';
	echo '<script>wnd_ajax_embed(\'#' . $div_id . '\',\'' . $template . '\',\'' . $ajax_args . '\')</script>';
	echo '</div>';
}

/**
 *@since 2019.01.28 ajax 发送手机或邮箱验证码
 *@param $_POST['verify_type']				验证类型（注册，非注册等）
 *@param $_POST['send_type']				发送类型（邮件，短信等）
 *@param $_POST['template']					信息模板
 *@param $_POST['phone'] or $_POST['email']	手机或邮件
 */
function wnd_ajax_send_code() {

	$verify_type = $_POST['verify_type'] ?? '';
	$send_type = $_POST['send_type'] ?? ''; // email or sms, to login user
	$template = $_POST['template'] ?? '';

	$phone = $_POST['phone'] ?? '';
	$email = $_POST['email'] ?? '';
	$email_or_phone = $phone ?: $email;

	if (is_user_logged_in()) {
		return wnd_send_code_to_user($send_type, $verify_type, $template);
	} else {
		return wnd_send_code_to_anonymous($email_or_phone, $verify_type, $template);
	}

}

/**
 *@since 初始化 ajax标题去重
 *@param $_POST['post_title']
 *@param $_POST['post_id']
 *@param $_POST['post_type']
 */
function _wnd_ajax_is_title_repeated() {

	$title = $_POST['post_title'];
	$exclude_id = $_POST['post_id'];
	$post_type = $_POST['post_type'];
	return wnd_is_title_repeated($title, $exclude_id, $post_type);

}

/**
 *@since 2019.02.22
 *管理员ajax手动新增用户金额
 *@param $_POST['user_field']
 *@param $_POST['money']
 *@param $_POST['remarks']
 */
function wnd_ajax_admin_recharge() {

	if (!is_super_admin()) {
		return array('status' => 0, 'msg' => '仅超级管理员可执行当前操作！');
	}

	$user_field = $_POST['user_field'];
	$money = $_POST['money'];
	$remarks = $_POST['remarks'] ?: '管理员充值';

	return wnd_admin_recharge($user_field, $money, $remarks);
}

/**
 *@since 2019.01.16
 *@param $_POST['post_id']
 *@param $_POST['useragent']
 */
function _wnd_ajax_update_views() {

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
	if ($should_count) {

		if (wnd_inc_post_meta($post_id, 'views', 1)) {

			// 完成统计时附加动作
			do_action('wnd_ajax_update_views', $post_id);
			// 统计更新成功
			return array('status' => 1, 'msg' => time());

			//字段写入失败，清除对象缓存
		} else {
			wp_cache_delete($post_id, 'post_meta');
		}

	} else {

		// 未更新
		return array('status' => 0, 'msg' => time());

	}

}

/**
 *@since 2019.05.09  测试函数
 */
function wnd_ajax_test() {
	return array(
		'status' => 0,
		'msg' => '测试函数触发成功!',
		'data' => $_REQUEST,
	);
}
