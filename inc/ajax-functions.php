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

/**
*@since 2019.02.21 ajax 批量生成充值卡
*/
function wnd_ajax_create_recharge_card() {

	$value =$_POST['value'];
	$num =$_POST['num'];

	return wnd_create_recharge_card($value, $num);
}

/**
 *@since 初始化
 *ajax标题去重
 */
function _wnd_ajax_is_title_repeated() {

	$title = $_POST['post_title'];
	$exclude_id = $_POST['post_id'];
	$post_type = $_POST['post_type'];
	return wnd_is_title_repeated($title, $exclude_id, $post_type);

}