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
	$text           = $is_email ? '邮箱' : '手机';
	$template       = $_POST['template'] ?: wnd_get_option('wnd', 'wnd_sms_template');
	$email_or_phone = $_POST['email'] ?? $_POST['phone'] ?? null;
	$current_user   = wp_get_current_user();

	// 此处需要严格区分邮箱和手机：因此需要对一致性做校验
	if (!wnd_verify_nonce($_POST['type_nonce'], $is_email ? 'email' : 'sms')) {
		return array('status' => 0, 'msg' => '验证设备类型校验失败！');
	}

	// 检测对应手机或邮箱格式：防止在邮箱绑定中输入手机号，反之亦然
	if ($is_email and !is_email($email_or_phone)) {
		return array('status' => 0, 'msg' => '邮箱地址无效！');
	} elseif (!$is_email and !wnd_is_phone($email_or_phone)) {
		return array('status' => 0, 'msg' => '手机号码无效！');
	}

	/**
	 *已登录用户，且账户已绑定邮箱/手机，且验证类型不为bind（切换绑定邮箱）
	 *发送验证码给当前账户
	 */
	if ($current_user->ID and $type != 'bind') {
		$email_or_phone = $is_email ? $current_user->user_email : wnd_get_user_phone($current_user->ID);
		if (!$email_or_phone) {
			return array('status' => 0, 'msg' => '当前账户未绑定' . $text);
		}
	}

	try {
		$auth = new Wnd_Auth;
		$auth->set_type($type);
		$auth->set_email_or_phone($email_or_phone);
		$auth->set_template($template);
		$auth->send();
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

	$user_field   = $_POST['user_field'];
	$total_amount = $_POST['total_amount'];
	$remarks      = $_POST['remarks'] ?: '人工充值';

	// 根据邮箱，手机，或用户名查询用户
	$user = wnd_get_user_by($user_field);
	if (!$user) {
		return array('status' => 0, 'msg' => '用户不存在！');
	}

	if (!is_numeric($total_amount)) {
		return array('status' => 0, 'msg' => '请输入一个有效的充值金额！');
	}

	// 写入充值记录
	try {
		$recharge = new Wnd_Recharge();
		$recharge->set_user_id($user->ID);
		$recharge->set_total_amount($total_amount);
		$recharge->set_subject($remarks);
		$recharge->create(true); // 直接写入余额
	} catch (Exception $e) {
		return array('status' => 0, 'msg' => $e->getMessage());
	}

	return array('status' => 1, 'msg' => $user->display_name . ' 充值：¥' . $total_amount);
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
		if (!empty($useragent) and (stristr($useragent, $lookfor) !== false)) {
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
