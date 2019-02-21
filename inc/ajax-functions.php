<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.01.28 ajax 发送手机或邮箱验证码
 */
function wnd_ajax_send_code() {

	$type = $_POST['type'] ?? '';
	$template = $_POST['template'] ?? '';
	$phone = $_POST['phone'];
	$email = $_POST['email'];
	$email_or_phone = $phone ?: $email;

	return wnd_send_code($email_or_phone,$type,$template);

}

/**
*@since 2019.02.21 验证充值卡
*/
function wnd_ajax_verity_recharge_card(){

	return wnd_verity_recharge_card($_POST['card'], $_POST['password']);	

}