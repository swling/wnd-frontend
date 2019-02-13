<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.01.28 ajax 发送手机或邮箱验证码
 */
function wnd_send_code() {

	$type = $_POST['type'] ?? '';
	$template = $_POST['template'] ?? '';
	$phone = $_POST['phone'];
	$email = $_POST['email'];
	$email_or_phone = $phone ?: $email;

	// 权限检测
	$wnd_can_send_code = wnd_can_send_code($email_or_phone, $type);
	if ($wnd_can_send_code['status'] === 0) {
		return $wnd_can_send_code;
	}

	// 邮箱验证
	if ($email) {

		if (is_user_logged_in()) {
			return wnd_send_mail_code_to_user($type, $template);
		} else {
			return wnd_send_mail_code($email, $type, $template);
		}

		//手机验证
	} elseif ($phone) {

		if (is_user_logged_in()) {
			return wnd_send_sms_code_to_user($type, $template);
		} else {
			return wnd_send_sms_code($phone, $type, $template);
		}

		// 既不是手机也不是邮箱
	} else {
		return array('status' => 0, 'msg' => '手机或邮箱格式不正确！');
	}

}