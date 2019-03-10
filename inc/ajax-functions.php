<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.01.28 ajax 发送手机或邮箱验证码
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
 *@since 初始化
 *ajax标题去重
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
 */
function wnd_ajax_admin_recharge() {

	if (!is_super_admin()) {
		return array('status' => 0, 'msg' => '仅超级管理员可执行当前操作！');
	}

	$user_field = $_POST['user_field'];
	$money = $_POST['money'];
	$remarks = $_POST['remarks'] ?? '管理员手动充值';

	if(!is_numeric($money)){
		return array('status' => 0, 'msg' => '请输入一个有效的充值金额！');
	}

	return wnd_admin_recharge($user_field, $money, $remarks);
}