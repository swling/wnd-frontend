<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@see README.md
 *ajax user POST name规则：
 *user field：_user_{field}
 *user meta：
 *_usermeta_{key} （*自定义数组字段）
 *_wpusermeta_{key} （*WordPress原生字段）
 *
 *@since 初始化 用户注册
 *@param $_POST['_user_user_login']
 *@param $_POST['_user_user_pass']
 *@param $_POST['_user_user_pass_repeat']
 *
 *@param $_POST['_user_user_email']
 *@param $_POST['_user_display_name']
 *@param $_POST['_wpusermeta_description']
 */
function wnd_ajax_reg() {

	// 1、数据组成
	if (empty($_POST)) {
		return array('status' => 0, 'msg' => '注册信息为空');
	}

	$user_login       = $_POST['_user_user_login'] ?? wnd_generate_login();
	$user_login       = sanitize_user($user_login, true); //移除特殊字符
	$user_pass        = $_POST['_user_user_pass'] ?? null;
	$user_pass_repeat = $_POST['_user_user_pass_repeat'] ?? null;
	$user_email       = $_POST['_user_user_email'] ?? null;
	$display_name     = $_POST['_user_display_name'] ?? null;
	$description      = $_POST['_wpusermeta_description'] ?? null;
	$role             = get_option('default_role');

	/*处于安全考虑，form自动组合函数屏蔽了用户敏感字段，此处不可通过 form自动组合，应该手动控制用户数据*/
	$userdata = array(
		'user_login'   => $user_login,
		'user_email'   => $user_email,
		'user_pass'    => $user_pass,
		'display_name' => $display_name,
		'description'  => $description,
		'role'         => $role,
	);

	// 2、数据正确性检测
	if (strlen($user_login) < 3) {
		return $value = array('status' => 0, 'msg' => '用户名不能低于3位！');
	}
	if (is_numeric($user_login)) {
		return $value = array('status' => 0, 'msg' => '用户名不能是纯数字！');
	}
	if (strlen($user_pass) < 6) {
		return $value = array('status' => 0, 'msg' => '密码不能低于6位！');
	}
	if (!empty($user_pass_repeat) and $user_pass_repeat !== $user_pass_repeat) {
		return $value = array('status' => 0, 'msg' => '两次输入的密码不匹配！');
	}

	// 注册权限过滤挂钩
	$user_can_reg = apply_filters('wnd_can_reg', array('status' => 1, 'msg' => '默认通过'));
	if ($user_can_reg['status'] === 0) {
		return $user_can_reg;
	}

	//3、注册新用户
	$user_id = wp_insert_user($userdata);

	//注册账户失败
	if (is_wp_error($user_id)) {
		return array('status' => 0, 'msg' => $user_id->get_error_message());
	}

	// 实例化WndWP表单数据处理对象
	try {
		$form_data = new Wnd_Form_Data();
	} catch (Exception $e) {
		return array('status' => 0, 'msg' => $e->getMessage());
	}

	// 写入用户自定义数组meta
	$user_meta_array = $form_data->get_user_meta_array();
	if (!empty($user_meta_array)) {
		wnd_update_user_meta_array($user_id, $user_meta_array);
	}

	// 写入WordPress原生用户字段
	$wp_user_meta_array = $form_data->get_wp_user_meta_array();
	if (!empty($wp_user_meta_array)) {
		foreach ($wp_user_meta_array as $key => $value) {
			// 下拉菜单默认未选择时，值为 -1 。过滤
			if ($value !== '-1') {
				update_user_meta($user_id, $key, $value);
			}
		}
		unset($key, $value);
	}

	// 用户注册完成，自动登录
	$user = get_user_by('id', $user_id);
	if ($user) {
		wp_set_current_user($user_id, $user->user_login);
		wp_set_auth_cookie($user_id, 1);
		// 注册后跳转地址
		$redirect_to  = $_REQUEST['redirect_to'] ?? wnd_get_option('wnd', 'wnd_reg_redirect_url') ?: home_url();
		$return_array = apply_filters(
			'wnd_reg_return',
			array('status' => 3, 'msg' => '注册成功！', 'data' => array('redirect_to' => $redirect_to, 'user_id' => $user_id)),
			$user_id
		);
		return $return_array;

		//注册失败
	} else {
		return array('status' => 0, 'msg' => '注册失败！');
	}
}

/**
 *@since 2019.1.13 用户登录
 *@param $username = trim($_POST['_user_user_login']);
 *@param $password = $_POST['_user_user_pass'];
 *
 *@param $remember = $_POST['remember'] ?? 0;
 *@param $redirect_to = $_REQUEST['redirect_to'] ?? home_url();
 */
function wnd_ajax_login() {
	$username    = trim($_POST['_user_user_login']);
	$password    = $_POST['_user_user_pass'];
	$remember    = $_POST['remember'] ?? 0;
	$remember    = $remember == 1 ? true : false;
	$redirect_to = $_REQUEST['redirect_to'] ?? home_url();

	// 登录过滤挂钩
	$wnd_can_login = apply_filters('wnd_can_login', array('status' => 1, 'msg' => '默认通过'));
	if ($wnd_can_login['status'] === 0) {
		return $wnd_can_login;
	}

	// 可根据邮箱，手机，或用户名查询用户
	$user = wnd_get_user_by($username);

	if (!$user) {
		return array('status' => 0, 'msg' => '用户不存在');

	} elseif (wp_check_password($password, $user->data->user_pass, $user->ID)) {
		wp_set_current_user($user->ID, $user->user_login);
		wp_set_auth_cookie($user->ID, $remember);
		if ($redirect_to) {
			return array('status' => 3, 'msg' => '登录成功！', 'data' => array('redirect_to' => $redirect_to, 'user_id' => $user->ID));
		} else {
			return array('status' => 1, 'msg' => '登录成功！');
		}

	} else {
		return array('status' => 0, 'msg' => '账户密码不匹配！');
	}
}

/**
 *@since 初始化
 *用户资料修改：昵称，简介，字段等 修改账户密码、邮箱，请使用：wnd_wpdate_account
 *@param $_POST 	用户资料表单数据
 *
 *@see README.md
 *ajax user POST name规则：
 *user field：_user_{field}
 *user meta：
 *_usermeta_{key} （*自定义数组字段）
 *_wpusermeta_{key} （*WordPress原生字段）
 *
 */
function wnd_ajax_update_profile() {
	if (empty($_POST)) {
		return array('status' => 0, 'msg' => '获取用户数据失败！');
	}

	// 实例化WndWP表单数据处理对象
	try {
		$form_data = new Wnd_Form_Data();
	} catch (Exception $e) {
		return array('status' => 0, 'msg' => $e->getMessage());
	}
	$user_array         = $form_data->get_user_array();
	$user_meta_array    = $form_data->get_user_meta_array();
	$wp_user_meta_array = $form_data->get_wp_user_meta_array();

	// ################### 组成用户数据
	$user    = wp_get_current_user();
	$user_id = $user->ID;
	if (!$user_id) {
		return array('status' => 0, 'msg' => '获取用户ID失败！');
	}
	$user_array = array('ID' => $user_id, 'display_name' => $user_array['display_name'], 'user_url' => $user_array['user_url']);

	// 更新权限过滤挂钩
	$user_can_update_profile = apply_filters('wnd_can_update_profile', array('status' => 1, 'msg' => '默认通过'));
	if ($user_can_update_profile['status'] === 0) {
		return $user_can_update_profile;
	}

	//################### 没有错误 更新用户
	$user_id = wp_update_user($user_array);
	if (is_wp_error($user_id)) {
		$msg = $user_id->get_error_message();
		return array('status' => 0, 'msg' => $msg);
	}

	//################### 用户更新成功，写入meta 及profile数据
	if (!empty($user_meta_array)) {
		wnd_update_user_meta_array($user_id, $user_meta_array);
	}

	if (!empty($wp_user_meta_array)) {
		foreach ($wp_user_meta_array as $key => $value) {
			// 下拉菜单默认未选择时，值为 -1 。过滤
			if ($value !== '-1') {
				update_user_meta($user_id, $key, $value);
			}
		}
		unset($key, $value);
	}

	do_action('wnd_update_profile', $user_id);

	// 返回值过滤
	$return_array = apply_filters('wnd_update_profile_return', array('status' => 1, 'msg' => '更新成功！'), $user_id);
	return $return_array;

}

/**
 *@since 初始化
 *用户账户更新：修改密码，邮箱
 *@param $_POST['_user_user_pass']
 *@param $_POST['_user_new_pass']
 *@param $_POST['_user_new_pass_repeat']
 *@param $_POST['_user_user_email']
 */
function wnd_ajax_update_account() {
	$user    = wp_get_current_user();
	$user_id = $user->ID;
	if (!$user_id) {
		return array('status' => 0, 'msg' => '获取用户ID失败！');
	}

	$user_array          = array('ID' => $user_id);
	$user_pass           = $_POST['_user_user_pass'] ?? null;
	$new_password        = $_POST['_user_new_pass'] ?? null;
	$new_password_repeat = $_POST['_user_new_pass_repeat'] ?? null;
	$new_email           = $_POST['_user_user_email'] ?? null;

	// 修改密码
	if (!empty($new_password_repeat)) {
		if (strlen($new_password) < 6) {
			return array('status' => 0, 'msg' => '新密码不能低于6位！');

		} elseif ($new_password_repeat != $new_password) {
			return array('status' => 0, 'msg' => '两次输入的新密码不匹配！');

		} else {
			$user_array['user_pass'] = $new_password;
		}
	}

	// 修改邮箱
	if ($new_email and $new_email != $user->user_email) {
		if (!is_email($new_email)) {
			return array('status' => 0, 'msg' => '邮件格式错误！');
		} else {
			$user_array['user_email'] = $new_email;
		}
	}

	// 原始密码校验
	if (!wp_check_password($user_pass, $user->data->user_pass, $user->ID)) {
		return array('status' => 0, 'msg' => '初始密码错误！');
	}

	// 更新权限过滤挂钩
	$user_can_update_account = apply_filters('wnd_can_update_account', array('status' => 1, 'msg' => '默认通过'));
	if ($user_can_update_account['status'] === 0) {
		return $user_can_update_account;
	}

	// 更新用户
	$user_id = wp_update_user($user_array);

	// 更新失败，返回错误信息
	if (is_wp_error($user_id)) {
		return array('status' => 0, 'msg' => $user_id->get_error_message());
	}

	// 用户更新成功：更新账户会导致当前账户的wp nonce失效，需刷新页面
	$return_array = apply_filters('wnd_update_account_return', array('status' => 4, 'msg' => '更新成功'), $user_id);
	return $return_array;
}

/**
 *@since 2019.02.10 用户找回密码
 *@param $_POST['phone'] ?? $_POST['_user_user_email'];
 *@param $_POST['auth_code']
 *@param $_POST['_user_new_pass']
 *@param $_POST['_user_new_pass_repeat']
 */
function wnd_ajax_reset_password() {
	$email_or_phone      = $_POST['_user_user_email'] ?? $_POST['phone'] ?? null;
	$new_password        = $_POST['_user_new_pass'] ?? null;
	$new_password_repeat = $_POST['_user_new_pass_repeat'] ?? null;
	$auth_code           = $_POST['auth_code'];
	$is_user_logged_in   = is_user_logged_in();

	// 验证密码正确性
	if (strlen($new_password) < 6) {
		return array('status' => 0, 'msg' => '新密码不能低于6位！');

	} elseif ($new_password_repeat != $new_password) {
		return array('status' => 0, 'msg' => '两次输入的新密码不匹配！');
	}

	//获取用户
	$user = $is_user_logged_in ? wp_get_current_user() : wnd_get_user_by($email_or_phone);
	if (!$user) {
		return array('status' => 0, 'msg' => '账户未注册！');
	}

	// 核对验证码
	try {
		$auth = new Wnd_Auth;
		$auth->set_type('reset_password');
		$auth->set_auth_code($auth_code);
		$auth->set_email_or_phone($email_or_phone);
		$auth->verify();

		reset_password($user, $new_password);
		return array(
			'status' => $is_user_logged_in ? 4 : 1,
			'msg'    => '密码修改成功！<a onclick="wnd_ajax_modal(\'_wnd_login_form\');">登录</a>',
		);
	} catch (Exception $e) {
		return array('status' => 0, 'msg' => $e->getMessage());
	}
}

/**
 *@since 2019.07.23 已登录用户设置邮箱
 *@param $_POST['_user_user_email'];
 *@param $_POST['auth_code']
 */
function wnd_ajax_bind_email() {
	$email     = $_POST['_user_user_email'] ?? null;
	$auth_code = $_POST['auth_code'] ?? null;
	$password  = $_POST['_user_user_pass'] ?? null;
	$user      = wp_get_current_user();

	// 更改邮箱需要验证当前密码、首次绑定不需要
	if (wp_get_current_user()->data->user_email and !wp_check_password($password, $user->data->user_pass, $user->ID)) {
		return array('status' => 0, 'msg' => '当前密码错误！');
	}

	// 核对验证码
	try {
		$auth = new Wnd_Auth;
		$auth->set_type('bind');
		$auth->set_auth_code($auth_code);
		$auth->set_email_or_phone($email);

		/**
		 * 通常，正常前端注册的用户，已通过了邮件或短信验证中的一种，已有数据记录，绑定成功后更新对应数据记录，并删除当前验证数据记录
		 * 删除时会验证该条记录是否绑定用户，只删除未绑定用户的记录
		 * 若当前用户没有任何验证绑定记录，删除本条验证记录后，会通过 wnd_update_user_email() 重新新增一条记录
		 */
		$auth->verify($delete_after_verified = true);

		if (wnd_update_user_email(get_current_user_id(), $email)) {
			return array('status' => 1, 'msg' => '邮箱绑定成功！');
		} else {
			return array('status' => 0, 'msg' => '未知错误！');
		}
	} catch (Exception $e) {
		return array('status' => 0, 'msg' => $e->getMessage());
	}
}

/**
 *@since 2019.02.10 已登录用户设置手机
 *@param $_POST['phone'];
 *@param $_POST['auth_code']
 */
function wnd_ajax_bind_phone() {
	$phone     = $_POST['phone'] ?? null;
	$auth_code = $_POST['auth_code'] ?? null;
	$password  = $_POST['_user_user_pass'] ?? null;
	$user      = wp_get_current_user();

	// 更改手机需要验证当前密码、首次绑定不需要
	if (wnd_get_user_phone(get_current_user_id()) and !wp_check_password($password, $user->data->user_pass, $user->ID)) {
		return array('status' => 0, 'msg' => '当前密码错误！');
	}

	// 核对验证码
	try {
		$auth = new Wnd_Auth;
		$auth->set_type('bind');
		$auth->set_auth_code($auth_code);
		$auth->set_email_or_phone($phone);

		/**
		 * 通常，正常前端注册的用户，已通过了邮件或短信验证中的一种，已有数据记录，绑定成功后更新对应数据记录，并删除当前验证数据记录
		 * 删除时会验证该条记录是否绑定用户，只删除未绑定用户的记录
		 * 若当前用户没有任何验证绑定记录，删除本条验证记录后，会通过 wnd_update_user_phone() 重新新增一条记录
		 */
		$auth->verify($delete_after_verified = true);

		if (wnd_update_user_phone(get_current_user_id(), $phone)) {
			return array('status' => 1, 'msg' => '手机绑定成功！');
		} else {
			return array('status' => 0, 'msg' => '未知错误！');
		}
	} catch (Exception $e) {
		return array('status' => 0, 'msg' => $e->getMessage());
	}
}
