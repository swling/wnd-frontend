<?php
use Wnd\Model\Wnd_Mail;
use Wnd\Model\Wnd_User;

/**
 * 随机生成用户名
 * @since 2019.09.25
 *
 * @return string
 */
function wnd_generate_login() {
	return 'user_' . uniqid();
}

/**
 * @since 2019.01.26 根据用户id获取号码
 *
 * @param  	int          			$user_id
 * @return 	string|false 	用户手机号或false
 */
function wnd_get_user_phone($user_id) {
	return Wnd_User::get_user_phone($user_id);
}

/**
 * @since 2019.01.26 根据用户id获取openid
 *
 * @param  	int          			$user_id
 * @param  	string       			$type
 * @return 	string|false 	用户openid或false
 */
function wnd_get_user_openid($user_id, $type) {
	return Wnd_User::get_user_openid($user_id, $type);
}

/**
 * @since 2019.01.28 根据邮箱，手机，或用户名查询用户
 *
 * @param  	string                 $email_or_phone_or_login
 * @return 	object|false	WordPress user object on success
 */
function wnd_get_user_by($email_or_phone_or_login) {
	return Wnd_User::get_user_by($email_or_phone_or_login);
}

/**
 * 根据openID获取WordPress用户，用于第三方账户登录
 * @since 2019.07.11
 *
 * @param  	int          			openID
 * @param  	string       			$type
 * @return 	object|false 	（WordPress：get_user_by）
 */
function wnd_get_user_by_openid($openid, $type) {
	return Wnd_User::get_user_by_openid($openid, $type);
}

/**
 * 写入用户open id
 * @since 2019.07.11
 *
 * @param  	int    	$user_id
 * @param  	string 	$type
 * @param  	string 	$open_id
 * @return 	int    	$wpdb->insert
 */
function wnd_update_user_openid($user_id, $type, $openid) {
	return Wnd_User::update_user_openid($user_id, $type, $openid);
}

/**
 * 更新用户电子邮箱 同时更新插件用户数据库email，及WordPress账户email
 * @since 2019.07.11
 *
 * @param  	int    	$user_id
 * @param  	string 	$email
 * @return 	int    	$wpdb->insert
 */
function wnd_update_user_email($user_id, $email) {
	return Wnd_User::update_user_email($user_id, $email);
}

/**
 * 写入用户手机号码
 * @since 2019.07.11
 *
 * @param  	int    	$user_id
 * @param  	string 	$phone
 * @return 	int    	$wpdb->insert
 */
function wnd_update_user_phone($user_id, $phone) {
	return Wnd_User::update_user_phone($user_id, $phone);
}

/**
 * 用户角色为：管理员或编辑 返回 true
 * @since 初始化 判断当前用户是否为管理员
 *
 * @param  	int    	$user_id
 * @return 	bool
 */
function wnd_is_manager($user_id = 0) {
	return Wnd_User::is_manager($user_id);
}

/**
 * @since 2020.04.30 判断当前用户是否已被锁定
 *
 * @param  	int    	$user_id
 * @return 	bool
 */
function wnd_has_been_banned($user_id = 0) {
	return Wnd_User::has_been_banned($user_id);
}

/**
 * 用户display name去重
 * @since 初始化
 *
 * @param  	string      		$display_name
 * @param  	int         		$exclude_id
 * @return 	int|false
 */
function wnd_is_name_duplicated($display_name, $exclude_id = 0) {
	return Wnd_User::is_name_duplicated($display_name, $exclude_id);
}

/**
 * 发送站内信
 * @since 2019.02.25
 *
 * @param  	int    	$to      		收件人ID
 * @param  	string 	$subject 	邮件主题
 * @param  	string 	$message 	邮件内容
 * @return 	bool   	true on success
 */
function wnd_mail($to, $subject, $message) {
	return Wnd_Mail::mail($to, $subject, $message);
}

/**
 * 获取最近的10封未读邮件
 * @since 2019.04.11
 *
 * @return 	int 	用户未读邮件
 */
function wnd_get_mail_count() {
	return Wnd_Mail::get_mail_count();
}

/**
 * 获取用户面板允许的post types
 * @since 2019.06.10
 *
 * @return array 	文章类型数组
 */
function wnd_get_user_panel_post_types() {
	return Wnd_User::get_user_panel_post_types();
}

/**
 * @since 2020.04.11
 *
 * @param  int    user_id
 * @return string 用户语言字段值，若无效用户或未设置语言，则返回当前站点语言
 */
function wnd_get_user_locale($user_id) {
	return Wnd_User::get_user_locale($user_id);
}
