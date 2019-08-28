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
	$div_id    = 'wnd-embed-' . uniqid();
	$args      = wp_parse_args($args);
	$ajax_args = http_build_query($args);

	echo '<div id="' . $div_id . '">';
	echo '<script>wnd_ajax_embed(\'#' . $div_id . '\',\'' . $template . '\',\'' . $ajax_args . '\')</script>';
	echo '</div>';
}

/**
 *@since 2019.01.28 ajax 发送手机或邮箱验证码
 *@param $_POST['type']							验证类型
 *@param $_POST['is_email']						发送类型（邮件，短信等）
 *@param $_POST['template']						信息模板
 *@param $_POST['phone'] or $_POST['email']		手机或邮件
 */
function wnd_ajax_send_code() {
	$type           = $_POST['type'] ?? '';
	$is_email       = $_POST['is_email'] ?: false;
	$template       = $_POST['template'] ?: wnd_get_option('wnd', 'wnd_sms_template');
	$email_or_phone = $_POST['email'] ?? $_POST['phone'] ?? null;

	try {
		$auth = new Wnd_Auth;
		$auth->set_type($type);
		$auth->set_email_or_phone($email_or_phone);
		$auth->set_template($template);

		if (is_user_logged_in() and $type != 'bind') {
			$auth->send_to_current_user($is_email);
		} else {
			$auth->send();
		}

		return array('status' => 1, 'msg' => '发送成功，请注意查收！');
	} catch (Exception $e) {
		return array('status' => 0, 'msg' => $e->getMessage());
	}
}

/**
 *@since 初始化 ajax标题去重
 *@param $_POST['post_title']
 *@param $_POST['post_id']
 *@param $_POST['post_type']
 */
function _wnd_ajax_is_title_duplicated() {
	$title      = $_POST['post_title'];
	$exclude_id = $_POST['post_id'];
	$post_type  = $_POST['post_type'];

	if (wnd_is_title_duplicated($title, $exclude_id, $post_type)) {
		return array('status' => 1, 'msg' => '标题重复！');
	} else {
		return array('status' => 0, 'msg' => '标题唯一！');
	}
}

/**
 *@since 2019.02.22
 *管理员ajax手动新增用户金额
 *@param $_POST['user_field']
 *@param $_POST['total_amount']
 *@param $_POST['remarks']
 */
function wnd_ajax_admin_recharge() {
	if (!is_super_admin()) {
		return array('status' => 0, 'msg' => '仅超级管理员可执行当前操作！');
	}

	$user_field = $_POST['user_field'];
	$money      = $_POST['total_amount'];
	$remarks    = $_POST['remarks'] ?: '管理员充值';

	return wnd_admin_recharge($user_field, $money, $remarks);
}

/**
 *@since 2019.01.16
 *@param $_GET['post_id']
 *@param $_GET['useragent']
 */
function _wnd_ajax_update_views() {
	$post_id = (int) $_GET['param'];
	if (!$post_id) {
		return;
	}
	$useragent    = $_GET['useragent'];
	$should_count = true;

	// 根据 useragent 排除搜索引擎
	$bots = array(
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
		'msg'    => '测试函数触发成功!',
		'data'   => $_REQUEST,
	);
}
