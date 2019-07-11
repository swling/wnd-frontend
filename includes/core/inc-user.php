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
 *@since 2019.01.28 根据邮箱，手机，或用户名查询用户
 *@param $email_or_phone_or_login
 *@return WordPress user object or false
 */
function wnd_get_user_by($email_or_phone_or_login) {

	global $wpdb;

	if (is_email($email_or_phone_or_login)) {
		$user = get_user_by('email', $email_or_phone_or_login);

	} elseif (wnd_is_phone($email_or_phone_or_login)) {
		$user_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$wpdb->wnd_users} WHERE phone = %s;", $email_or_phone_or_login));
		$user = !$user_id ? false : get_user_by('ID', $user_id);

	} else {
		$user = get_user_by('login', $email_or_phone_or_login);
	}

	return $user;

}

/**
 *@since 2019.07.11
 *根据openID获取WordPress用户，用于第三方账户登录
 *@param openID
 *@return user object or false（WordPress：get_user_by）
 */
function wnd_get_user_by_openid($openid) {

	global $wpdb;

	$user_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$wpdb->wnd_users} WHERE open_id = %s;", $openid));
	return !$user_id ? false : get_user_by('ID', $user_id);

}

/**
 *@since 初始化 判断当前用户是否为管理员
 *@return bool
 *用户角色为：管理员或编辑 返回 true
 */
function wnd_is_manager($user_id = 0) {

	$user = wp_get_current_user();

	$user_role = $user->roles[0] ?? false;
	if ($user_role == 'administrator' or $user_role == 'editor') {
		return true;
	} else {
		return false;
	}

}

/**
 *@since 初始化
 *用户display name去重
 *@return int or false
 */
function wnd_is_name_repeated($display_name, $exclude_id = 0) {

	// 名称为空
	if (empty($display_name)) {
		return false;
	}

	global $wpdb;
	$results = $wpdb->get_var($wpdb->prepare(
		"SELECT ID FROM $wpdb->users WHERE display_name = %s AND  ID != %d  limit 1",
		$display_name,
		$exclude_id
	));

	return $results ?: false;
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
		wp_cache_delete($to, 'wnd_mail_count');
		return array('status' => 1, 'msg' => '发送成功！');
	}

}

/**
 *获取最近的10封未读邮件
 *@since 2019.04.11
 */
function wnd_get_mail_count() {

	$user_id = get_current_user_id();
	$user_mail_count = wp_cache_get($user_id, 'wnd_mail_count');

	if (false === $user_mail_count) {

		$args = array(
			'posts_per_page' => 11,
			'author' => $user_id,
			'post_type' => 'mail',
			'post_status' => 'pending',
		);

		$user_mail_count = count(get_posts($args));
		$user_mail_count = ($user_mail_count > 10) ? '10+' : $user_mail_count;

		wp_cache_set($user_id, $user_mail_count, 'wnd_mail_count');

	}

	return $user_mail_count ?: 0;

}

/**
 *@since 2019.06.10
 *获取用户面板允许的post types
 */
function wnd_get_user_panel_post_types() {

	$post_types = get_post_types(array('public' => true), 'names', 'and');
	// 排除页面/附件/站内信
	unset($post_types['page'], $post_types['attachment'], $post_types['mail']);

	return apply_filters('wnd_user_panel_post_types', $post_types);
}
