<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.09.25
 *随机生成用户名
 *@return string
 */
function wnd_generate_login() {
	return 'user_' . uniqid();
}

/**
 *@since 2019.01.26 根据用户id获取号码
 *@param 	int 			$user_id
 *@return 	string|false 	用户手机号或false
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
		wnd_update_user_meta($user_id, 'phone', $phone);
		return $phone;
	} else {
		return false;
	}
}

/**
 *@since 2019.01.28 根据邮箱，手机，或用户名查询用户
 *@param 	string 			$email_or_phone_or_login
 *@return 	object|false	WordPress user object on success
 */
function wnd_get_user_by($email_or_phone_or_login) {
	if (!$email_or_phone_or_login) {
		return false;
	}

	/**
	 *邮箱
	 */
	if (is_email($email_or_phone_or_login)) {
		return get_user_by('email', $email_or_phone_or_login);

	}

	/**
	 *手机或登录名
	 *
	 *若当前字符匹配手机号码格式，则优先使用手机号查询
	 *若查询到用户即返回
	 *最后返回用户名查询结果
	 *
	 *注意：
	 *强烈建议禁止用户使用纯数字作为用户名
	 *否则可能出现手机号码与用户名的混乱，造成同一个登录名，对应过个账户信息的问题
	 *
	 *本插件已禁用纯数字用户名：@see wnd_ajax_reg()
	 */
	if (wnd_is_phone($email_or_phone_or_login)) {
		global $wpdb;
		$user_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$wpdb->wnd_users} WHERE phone = %s;", $email_or_phone_or_login));
		$user    = $user_id ? get_user_by('ID', $user_id) : false;
		if ($user) {
			return $user;
		}

	} else {
		return get_user_by('login', $email_or_phone_or_login);
	}

}

/**
 *@since 2019.07.11
 *根据openID获取WordPress用户，用于第三方账户登录
 *@param 	openID
 *@return 	object|false 	（WordPress：get_user_by）
 */
function wnd_get_user_by_openid($openid) {
	global $wpdb;

	$user_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$wpdb->wnd_users} WHERE open_id = %s;", $openid));
	return !$user_id ? false : get_user_by('ID', $user_id);
}

/**
 *@since 2019.07.11
 *写入用户open id
 *@param 	int 	$user_id
 *@param 	string 	$open_id
 *@return 	int 	$wpdb->insert
 */
function wnd_update_user_openid($user_id, $openid) {
	global $wpdb;

	// 查询
	$ID = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->wnd_users} WHERE user_id = %d LIMIT 1", $user_id));

	// 更新
	if ($ID) {
		$db = $wpdb->update(
			$wpdb->wnd_users,
			array('open_id' => $openid, 'time' => time()),
			array('ID' => $ID),
			array('%s', '%d'),
			array('%d')
		);

		// 写入
	} else {
		$db = $wpdb->insert(
			$wpdb->wnd_users,
			array('user_id' => $user_id, 'open_id' => $openid, 'time' => time()),
			array('%d', '%s', '%d')
		);
	}

	return $db;
}

/**
 *@since 2019.07.11
 *更新用户电子邮箱 同时更新插件用户数据库email，及WordPress账户email
 *@param 	int 	$user_id
 *@param 	string 	$email
 *@return 	int 	$wpdb->insert
 */
function wnd_update_user_email($user_id, $email) {
	global $wpdb;

	// 查询
	$ID = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->wnd_users} WHERE user_id = %d LIMIT 1", $user_id));

	// 更新
	if ($ID) {
		$db = $wpdb->update(
			$wpdb->wnd_users,
			array('email' => $email, 'time' => time()),
			array('ID' => $ID),
			array('%s', '%d'),
			array('%d')
		);

		// 写入
	} else {
		$db = $wpdb->insert(
			$wpdb->wnd_users,
			array('user_id' => $user_id, 'email' => $email, 'time' => time()),
			array('%d', '%s', '%d')
		);
	}

	// 更新WordPress账户email
	if ($db) {
		wp_update_user(array('ID' => $user_id, 'user_email' => $email));
	}

	return $db;
}

/**
 *@since 2019.07.11
 *写入用户手机号码
 *@param 	int 	$user_id
 *@param 	string 	$phone
 *@return 	int 	$wpdb->insert
 */
function wnd_update_user_phone($user_id, $phone) {
	global $wpdb;

	// 查询
	$ID = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->wnd_users} WHERE user_id = %d LIMIT 1", $user_id));

	// 更新
	if ($ID) {
		$db = $wpdb->update(
			$wpdb->wnd_users,
			array('phone' => $phone, 'time' => time()),
			array('ID' => $ID),
			array('%s', '%d'),
			array('%d')
		);

		// 写入
	} else {
		$db = $wpdb->insert(
			$wpdb->wnd_users,
			array('user_id' => $user_id, 'phone' => $phone, 'time' => time()),
			array('%d', '%s', '%d')
		);
	}

	// 更新字段
	if ($db) {
		wnd_update_user_meta($user_id, 'phone', $phone);
	}

	return $db;
}

/**
 *@since 2019.07.23
 *根据第三方网站获取的用户信息，注册或者登录到WordPress站点
 *@param string $open_id 		第三方账号openID
 *@param string $display_name 	用户名称
 *@param string $avatar_url 	用户外链头像
 *
 **/
function wnd_social_login($open_id, $display_name = '', $avatar_url = '') {
	//当前用户已登录，同步信息
	if (is_user_logged_in()) {

		$this_user   = wp_get_current_user();
		$may_be_user = wnd_get_user_by_openid($open_id);
		if ($may_be_user and $may_be_user->ID != $this_user->ID) {
			exit('第三方账户已被占用！');
		}

		if ($avatar_url) {
			wnd_update_user_meta($this_user->ID, "avatar_url", $avatar_url);
		}
		if ($open_id) {
			wnd_update_user_openid($this_user->ID, $open_id);
		}
		wp_redirect(wnd_get_option('wnd', 'wnd_reg_redirect_url') ?: home_url());
		exit;
	}

	//当前用户未登录，注册或者登录 检测是否已注册
	$user = wnd_get_user_by_openid($open_id);
	if (!$user) {

		// 自定义随机用户名
		$user_login = wnd_generate_login();
		$user_pass  = wp_generate_password();
		$user_array = array('user_login' => $user_login, 'user_pass' => $user_pass, 'display_name' => $display_name);
		$user_id    = wp_insert_user($user_array);

		// 注册失败
		if (is_wp_error($user_id)) {
			wp_die($user_id->get_error_message(), get_option('blogname'));

			// 注册成功，记录用户open id
		} else {
			wnd_update_user_openid($user_id, $open_id);
		}

	}

	// 获取用户id
	$user_id = $user ? $user->ID : $user_id;

	wnd_update_user_meta($user_id, "avatar_url", $avatar_url);
	wp_set_auth_cookie($user_id, 1);
	wp_redirect(wnd_get_option('wnd', 'wnd_reg_redirect_url') ?: home_url());
}

/**
 *@since 初始化 判断当前用户是否为管理员
 *@param 	int 	$user_id
 *@return 	bool
 *用户角色为：管理员或编辑 返回 true
 */
function wnd_is_manager($user_id = 0) {
	$user = $user_id ? get_user_by('id', $user_id) : wp_get_current_user();

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
 *@param 	string 		$display_name
 *@param 	int 		$exclude_id
 *@return 	int|false
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
 *@since 2019.02.25
 *发送站内信
 *@param 	int 	$to 		收件人ID
 *@param 	string 	$subject 	邮件主题
 *@param 	string 	$message 	邮件内容
 *@return 	bool 	true on success
 */
function wnd_mail($to, $subject, $message) {
	if (!get_user_by('id', $to)) {
		return array('status' => 0, 'msg' => '用户不存在！');
	}

	$postarr = array(
		'post_type'    => 'mail',
		'post_author'  => $to,
		'post_title'   => $subject,
		'post_content' => $message,
		'post_status'  => 'pending',
		'post_name'    => uniqid(),
	);

	$mail_id = wp_insert_post($postarr);

	if (is_wp_error($mail_id)) {
		return false;
	} else {
		wp_cache_delete($to, 'wnd_mail_count');
		return true;
	}
}

/**
 *获取最近的10封未读邮件
 *@since 2019.04.11
 *@return 	int 	用户未读邮件
 */
function wnd_get_mail_count() {
	$user_id         = get_current_user_id();
	$user_mail_count = wp_cache_get($user_id, 'wnd_mail_count');

	if (false === $user_mail_count) {
		$args = array(
			'posts_per_page' => 11,
			'author'         => $user_id,
			'post_type'      => 'mail',
			'post_status'    => 'pending',
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
 *@return array 	文章类型数组
 */
function wnd_get_user_panel_post_types() {
	$post_types = get_post_types(array('public' => true), 'names', 'and');
	// 排除页面/附件/站内信
	unset($post_types['page'], $post_types['attachment'], $post_types['mail']);
	return apply_filters('wnd_user_panel_post_types', $post_types);
}
