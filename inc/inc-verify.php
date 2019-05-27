<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.02.21 发送验证码给匿名用户
 */
function wnd_send_code_to_anonymous($email_or_phone, $verify_type, $template) {

	// 权限检测
	$wnd_can_send_code = wnd_can_send_code($email_or_phone, $verify_type);
	if ($wnd_can_send_code['status'] === 0) {
		return $wnd_can_send_code;
	}

	// 邮箱验证
	if (is_email($email_or_phone)) {
		return wnd_send_mail_code($email_or_phone, $template);

		//手机验证
	} else {
		return wnd_send_sms_code($email_or_phone, $template);
	}

}

/**
 *@since 2019.02.22 发送验证码给已知用户
 */
function wnd_send_code_to_user($send_type, $verify_type, $template) {

	if (!is_user_logged_in()) {
		return array('status' => 0, 'msg' => '用户未登录！');
	}

	$user = wp_get_current_user();

	// 根据发送类型获取当前用户邮箱或手机
	$email_or_phone = ($send_type == 'email') ? $user->user_email : wnd_get_user_phone($user->ID);
	if (!$email_or_phone) {
		return array('status' => 0, 'msg' => '获取发送对象失败！');
	}

	// 权限检测
	$wnd_can_send_code = wnd_can_send_code($email_or_phone, $verify_type);
	if ($wnd_can_send_code['status'] === 0) {
		return $wnd_can_send_code;
	}

	if ($send_type == 'email') {
		return wnd_send_mail_code($email_or_phone, $template);
	} else {
		return wnd_send_sms_code($email_or_phone, $template);
	}

}

/**
 *@since 2019.02.10 权限检测
 *此处的权限校验仅作为前端是否可以发送验证验证码的初级校验，较容易被绕过
 *在对验证码正确性进行校验时，应该再次进行类型权限校验
 */
function wnd_can_send_code($email_or_phone, $verify_type) {

	if (empty($email_or_phone) && !is_user_logged_in()) {
		return array('status' => 0, 'msg' => '发送地址为空！');
	}

	if (is_email($email_or_phone)) {
		$text = '邮箱';
	} elseif (wnd_is_phone($email_or_phone)) {
		$text = '手机';
	} else {
		return array('status' => 0, 'msg' => '格式不正确！');
	}

	// 检测是否为注册类型，注册类型去重
	if ($verify_type == 'reg' and wnd_get_user_by($email_or_phone)) {
		return array('status' => 0, 'msg' => '该' . $text . '已注册过！');
	}

	// 找回密码
	elseif ($verify_type == 'reset_pass' and !wnd_get_user_by($email_or_phone)) {
		return array('status' => 0, 'msg' => '该' . $text . '尚未注册！');
	}

	// 上次发送短信的时间，防止短信攻击
	$send_time = wnd_get_code_sendtime($email_or_phone);
	if ($send_time and (time() - $send_time < 90)) {
		return array('status' => 0, 'msg' => '操作太频繁，请' . (90 - (time() - $send_time)) . '秒后重试！');
	}

	return array('status' => 1, 'msg' => '校验通过！');

}

/**
 *@since 2019.01.28 发送邮箱验证码
 */
function wnd_send_mail_code($email, $template = '') {

	// $user = get_user_by('email', $email);
	$code = wnd_random_code($length = 6);
	$action = wnd_insert_code($email, $code);
	if (!$action) {
		return array('status' => 0, 'msg' => '写入数据库失败！');
	}

	$message = '邮箱验证秘钥【' . $code . '】（不含括号），关键凭证，请勿泄露！';
	$action = wp_mail($email, '验证邮箱', $message);
	if ($action) {
		return array('status' => 1, 'msg' => '发送成功，请注意查收！');
	} else {
		return array('status' => 0, 'msg' => '发送失败，请稍后重试！');
	}

}

/**
 *@since 初始化
 *通过ajax发送短信
 *点击发送按钮，通过js获取表单填写的手机号，检测并发送短信
 */
function wnd_send_sms_code($phone, $template = '') {

	require WND_PATH . 'components/tencent-sms/sendSms.php'; //阿里云短信

	$template = $template ?: wnd_get_option('wnd', 'wnd_sms_template');
	$code = wnd_random_code($length = 6);

	// 写入手机记录
	if (!wnd_insert_code($phone, $code)) {
		return array('status' => 0, 'msg' => '数据库写入失败！');
	}

	$send_status = wnd_send_sms($phone, $code, $template);
	if ($send_status->result == 0) {
		return array('status' => 1, 'msg' => '发送成功！');
	} else {
		return array('status' => 0, 'msg' => '系统错误，请联系客服处理！');
	}

}

/**
 *@since 2019.02.09 手机及邮箱验证模块
 */
function wnd_insert_code($email_or_phone, $code) {
	global $wpdb;
	$field = is_email($email_or_phone) ? 'email' : 'phone';

	$ID = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->wnd_users} WHERE {$field} = %s", $email_or_phone));

	if ($ID) {
		$db = $wpdb->update($wpdb->wnd_users, array('code' => $code, 'time' => time()), array($field => $email_or_phone), array('%s', '%d'), array('%s'));
	} else {
		$db = $wpdb->insert($wpdb->wnd_users, array($field => $email_or_phone, 'code' => $code, 'time' => time()), array('%s', '%s', '%d'));
	}

	return $db;

}

/**
 *校验短信验证
 *@since 初始化
 *@return array
 */
function wnd_verify_code($email_or_phone, $code, $verify_type) {

	global $wpdb;
	$field = is_email($email_or_phone, $deprecated = false) ? 'email' : 'phone';
	$text = $field == 'phone' ? '手机' : '邮箱';

	if (empty($code)) {
		return array('status' => 0, 'msg' => '校验失败：请填写验证码！');
	}

	if (empty($email_or_phone)) {
		return array('status' => 0, 'msg' => '校验失败：请填写' . $text . '！');
	}

	if ($verify_type == 'reg' && wnd_get_user_by($email_or_phone)) {
		return array('status' => 0, 'msg' => '校验失败：' . $text . '已注册过！');
	}

	// 过期时间设置
	$intervals = $field == 'phone' ? 600 : 3600;

	$data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->wnd_users WHERE {$field} = %s;", $email_or_phone));

	if (!$data) {
		return array('status' => 0, 'msg' => '校验失败：请先获取验证码！');
	}

	if (time() - $data->time > $intervals) {
		return array('status' => 0, 'msg' => '验证码已失效请重新获取！');
	}

	if ($code != $data->code) {
		return array('status' => 0, 'msg' => '校验失败：验证码不正确！');
	}

	/**
	 *@since 2019.01.22 清空当前验证码
	 */
	// wnd_reset_code($email_or_phone, 0);

	return array('status' => 1, 'msg' => '验证通过！');

}

/**
 *@since 2019.02.09 获取验证码发送时间
 */
function wnd_get_code_sendtime($email_or_phone) {

	global $wpdb;
	$field = is_email($email_or_phone, $deprecated = false) ? 'email' : 'phone';
	$time = $wpdb->get_var($wpdb->prepare("SELECT time FROM {$wpdb->wnd_users} WHERE {$field} = %s;", $email_or_phone));
	if ($time) {
		return $time;
	} else {
		return 0;
	}

}

// 重置验证码
function wnd_reset_code($email_or_phone, $reg_user_id = 0) {
	global $wpdb;
	$field = is_email($email_or_phone, $deprecated = false) ? 'email' : 'phone';

	// 手机注册用户
	if ($reg_user_id) {
		$wpdb->update(
			$wpdb->wnd_users,
			array('code' => '', 'time' => time(), 'user_id' => $reg_user_id),
			array($field => $email_or_phone),
			array('%s', '%d', '%d'),
			array('%s')
		);
		//其他操作
	} else {
		$wpdb->update(
			$wpdb->wnd_users,
			array('code' => '', 'time' => time()),
			array($field => $email_or_phone),
			array('%s', '%d'),
			array('%s')
		);
	}

}
