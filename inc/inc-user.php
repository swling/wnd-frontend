<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.01.26 根据用户id获取号码
 */
function wnd_get_user_phone($user_id) {

	if (!$user_id) {
		return false;
	}

	$phone = wnd_get_user_meta(get_current_user_id(), 'phone');
	if ($phone) {
		return $phone;
	}

	global $wpdb;
	$phone = $wpdb->get_var($wpdb->prepare("SELECT phone FROM $wpdb->wnd_users WHERE user_id = %d;", $user_id));
	if ($phone) {
		return $phone;
	} else {
		return false;
	}

}

/**
 *@since 2019.01.28 根据号码或邮箱查询用户(用于验证码)
 */
function wnd_get_user_by($email_or_phone) {

	global $wpdb;
	
	$field = is_email($email_or_phone, $deprecated = false) ? 'email' : 'phone';
	$user_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$wpdb->wnd_users} WHERE {$field} = %s;", $email_or_phone));

	if ($user_id) {
		return $user_id;
	} else {
		return false;
	}

}

/**
 *@since 初始化 判断当前用户是否为管理员
 *@return bool
 *用户角色为：管理员或编辑 返回 true
 */
function wnd_is_manager($user_id = 0) {

	$user_id = $user_id ?: get_current_user_id();

	if (!$user_id) {
		return false;
	}

	$user = get_user_by('id', $user_id);
	if (!$user) {
		return false;
	}

	$user_role = $user->roles[0];
	if ($user_role == 'administrator' or $user_role == 'editor') {
		return true;
	} else {
		return false;
	}

}

/**
 *@since 初始化
 *用户display name去重
 */
function wnd_is_name_repeated($display_name, $exclude_id = 0) {

	// 名称为空
	if (empty($display_name)) {
		return array('status' => 0, 'msg' => '名称为空');
	}

	global $wpdb;
	$results = $wpdb->get_var($wpdb->prepare(
		"SELECT ID FROM $wpdb->users WHERE display_name = %s AND  ID != %d  limit 1",
		$display_name,
		$exclude_id
	));

	if ($results) {
		$value = array('status' => 1, 'msg' => $results);
	} else {
		$value = array('status' => 0, 'msg' => '昵称唯一');
	}

	return $value;
}

/**
 *@since 2019.02.25
 *发送站内信
 */
function wnd_mail($to, $subject, $message) {

	if (!get_user_by('id', $to)) {
		return array('status' => 0, 'msg' => '用户不存在！');
	}

	$postarr = array(
		'post_type' => 'mail',
		'post_author' => $to,
		'post_title' => $subject,
		'post_content' => $message,
		'post_status' => 'pending',
		'post_name' => uniqid(),
	);

	$mail_id = wp_insert_post($postarr);

	if (is_wp_error($mail_id)) {
		return array('status' => 0, 'msg' => $mail_id->get_error_message());
	} else {
		return array('status' => 1, 'msg' => '发送成功！');
	}

}