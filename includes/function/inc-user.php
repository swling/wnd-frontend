<?php
use Wnd\Model\Wnd_Mail;
use Wnd\Utility\Wnd_language;
use Wnd\WPDB\Wnd_Auth;
use Wnd\WPDB\Wnd_User;

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
 * 获取自定义用户对象
 * - Users 主要数据：balance、role、attribute、last_login、client_ip
 */
function wnd_get_wnd_user(int $user_id): object {
	return Wnd_User::get_wnd_user($user_id);
}

/**
 * 获取自定义用户对象
 * - Auths 主要数据：user_id、email、phone……
 */
function wnd_get_user_auths(int $user_id): object {
	return Wnd_Auth::get_user_auths($user_id);
}

/**
 * @since 2019.01.26 根据用户id获取号码
 *
 * @param  	int          			$user_id
 * @return 	string|false 	用户手机号或false
 */
function wnd_get_user_phone($user_id) {
	return Wnd_Auth::get_user_openid($user_id, 'phone');
}

/**
 * @since 2019.01.26 根据用户id获取openid
 *
 * @param  	int          			$user_id
 * @param  	string       			$type
 * @return 	string|false 	用户openid或false
 */
function wnd_get_user_openid($user_id, $type) {
	return Wnd_Auth::get_user_openid($user_id, $type);
}

/**
 * @since 2019.01.28 根据邮箱，手机，或用户名查询用户
 *
 * @param  	string                 $email_or_phone_or_login
 * @return 	object|false	WordPress user object on success
 */
function wnd_get_user_by($email_or_phone_or_login) {
	if (!$email_or_phone_or_login) {
		return false;
	}

	/**
	 * 邮箱
	 */
	if (is_email($email_or_phone_or_login)) {
		return get_user_by('email', $email_or_phone_or_login);
	}

	/**
	 * 手机或登录名
	 *
	 * 若当前字符匹配手机号码格式，则优先使用手机号查询
	 * 若查询到用户即返回
	 * 最后返回用户名查询结果
	 *
	 * 注意：
	 * 强烈建议禁止用户使用纯数字作为用户名
	 * 否则可能出现手机号码与用户名的混乱，造成同一个登录名，对应过个账户信息的问题
	 *
	 * 本插件已禁用纯数字用户名：@see wnd_ajax_reg()
	 */
	if (wnd_is_mobile($email_or_phone_or_login)) {
		return Wnd_Auth::get_user_by_openid('phone', $email_or_phone_or_login);
	}

	return get_user_by('login', $email_or_phone_or_login);
}

/**
 * 根据openID获取WordPress用户，用于第三方账户登录
 * @since 2019.07.11
 *
 * @param  	string       	$type
 * @param  	int          	openID
 * @return 	object|false 	（WordPress：get_user_by）
 */
function wnd_get_user_by_openid(string $type, string $open_id) {
	return Wnd_Auth::get_user_by_openid($type, $open_id);
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
	return Wnd_Auth::update_user_openid($user_id, $type, $openid);
}

/**
 * 删除用户 open id
 * @since 0.9.4
 *
 * @param  int    $user_id
 * @param  string $type           第三方账号类型
 * @return int    $wpdb->delete
 */
function wnd_delete_user_openid($user_id, $type) {
	return Wnd_Auth::delete_user_openid($user_id, $type);
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
	$db = Wnd_Auth::update_user_openid($user_id, 'email', $email);

	// 更新WordPress账户email
	if ($db) {
		$db = wp_update_user(['ID' => $user_id, 'user_email' => $email]);
	}

	return $db;
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
	return Wnd_Auth::update_user_openid($user_id, 'phone', $phone);
}

/**
 * 用户角色为：管理员或编辑 返回 true
 * @since 初始化 判断当前用户是否为管理员
 *
 * @param  	int    	$user_id
 * @return 	bool
 */
function wnd_is_manager($user_id = 0) {
	$user = $user_id ? get_user_by('id', $user_id) : wp_get_current_user();

	$user_role = $user->roles[0] ?? false;
	if ('administrator' == $user_role or 'editor' == $user_role) {
		return true;
	} else {
		return false;
	}
}

/**
 * @since 2020.04.30 判断当前用户是否已被锁定
 *
 * @param  	int    	$user_id
 * @return 	bool
 */
function wnd_has_been_banned($user_id = 0) {
	$user_id = $user_id ?: get_current_user_id();
	$status  = get_user_meta($user_id, 'status', true);

	return 'banned' == $status ? true : false;
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
	$post_types = get_post_types(['public' => true], 'names', 'and');
	// 排除页面/附件/站内信
	unset($post_types['page'], $post_types['attachment'], $post_types['mail']);
	return apply_filters('wnd_user_panel_post_types', $post_types);
}

/**
 * @since 2020.04.11
 *
 * @param  int    user_id
 * @return string 用户语言字段值，若无效用户或未设置语言，则返回当前站点语言
 */
function wnd_get_user_locale($user_id) {
	return wnd_get_user_meta($user_id, 'locale') ?: 'default';
}

/**
 * 获取注册后跳转地址
 * 若设置有语言，则保留语言参数
 *
 * @since 2020.04.11
 */
function wnd_get_reg_redirect_url(string $lang = ''): string{
	$url  = wnd_get_config('reg_redirect_url') ?: home_url();
	$lang = $lang ?: Wnd_language::parse_locale();

	// 英语类 en_US, en_GB, en_CA 等统一设置 为 en
	if (!$lang) {
		return $url;
	}

	if (str_starts_with($lang, 'en_')) {
		$lang = 'en';
	}

	return add_query_arg(WND_LANG_KEY, $lang, $url);
}

/**
 * 获取用户头像图片地址
 *
 * @since 0.9.59.10
 */
function wnd_get_avatar_url(int $user_id, int $size = 200): string{
	$avatar_url = wnd_get_config('default_avatar_url') ?: WND_URL . 'static/images/avatar.jpg';

	if (wnd_get_user_meta($user_id, 'avatar')) {
		$avatar_id  = wnd_get_user_meta($user_id, 'avatar');
		$avatar_url = wp_get_attachment_url($avatar_id) ?: $avatar_url;

		/**
		 * 统一按阿里云oss裁剪缩略图
		 * @since 2019.07.23
		 */
		$avatar_url = wnd_get_thumbnail_url($avatar_url, $size, $size);
	} elseif (wnd_get_user_meta($user_id, 'avatar_url')) {
		$avatar_url = wnd_get_user_meta($user_id, 'avatar_url') ?: $avatar_url;
	}

	return $avatar_url;
}
