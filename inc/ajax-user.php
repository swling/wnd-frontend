<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 初始化
 *用户注册
 */
function wnd_reg() {

	// 1、数据组成
	if (empty($_POST)) {
		return array('status' => 0, 'msg' => '注册信息为空');
	}

	$user_login = $_POST['_user_user_login'] ?? $_POST['sms_phone'];
	$user_pass = $_POST['_user_user_pass'];
	$user_pass_repeat = $_POST['_user_user_pass_repeat'] ?? $_POST['_user_user_pass'];
	$user_email = $_POST['_user_user_email'];
	$display_name = $_POST['_user_display_name'] ?? '';
	$description = $_POST['_wpusermeta_description'] ?? '';
	$role = get_option('default_role');

	/*处于安全考虑，form自动组合函数屏蔽了用户敏感字段，此处不可通过 form自动组合，应该手动控制用户数据*/
	$userdata = array(
		'user_login' => $user_login,
		'user_email' => $user_email,
		'user_pass' => $user_pass,
		'display_name' => $display_name,
		'description' => $description,
		'role' => $role,
	);

	// 2、数据正确性检测
	if (strlen($user_login) < 4) {
		$reg_errors = '用户名不能低于4位！';
		return $value = array('status' => 0, 'msg' => $reg_errors);
	}
	if (strlen($user_pass) < 6) {
		$reg_errors = '密码不能低于6位！';
		return $value = array('status' => 0, 'msg' => $reg_errors);
	}
	if (!empty($user_pass_repeat) && $user_pass_repeat !== $user_pass_repeat) {
		$reg_errors = '两次输入的新密码不匹配！';
		return $value = array('status' => 0, 'msg' => $reg_errors);
	}
	if (!is_email($user_email)) {
		$reg_errors = '邮箱地址无效！';
		return $value = array('status' => 0, 'msg' => $reg_errors);
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

	// 写入用户自定义数组meta
	$user_meta_array = wnd_get_form_data($form_date_type = 'user', 'user_meta_array');
	if (!empty($user_meta_array)) {
		wnd_update_user_meta_array($user_id, $user_meta_array);
	}

	// 写入WordPress原生用户字段
	$wp_user_meta_array_temp = wnd_get_form_data($form_date_type = 'user', 'wp_user_meta_array');
	$wp_user_meta_array = array_merge($wp_user_meta_array, $wp_user_meta_array_temp);
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
		$redirect_to = $_REQUEST['redirect_to'] ?? wnd_get_option('wndwp', 'wnd_reg_redirect_url') ?: home_url();
		$return_array = apply_filters('wnd_reg_return', array('status' => 3, 'msg' => $redirect_to), $user_id);
		return $return_array;

		//注册失败
	} else {
		return array('status' => 0, 'msg' => '注册失败！');
	}

}

/**
 *@since 2019.1.13
 *用户登录
 */
function wnd_login() {

	$username = trim($_POST['_user_user_login']);
	$password = $_POST['_user_user_pass'];
	$remember = $_POST['remember'] ?? 0;
	$remember = $remember == 1 ? true : false;
	$redirect_to = $_REQUEST['redirect_to'] ?? home_url();

	// 登录过滤挂钩
	$wnd_can_login = apply_filters('wnd_can_login', array('status' => 1, 'msg' => '默认通过'));
	if ($wnd_can_login['status'] === 0) {
		return $wnd_can_login;
	}

	if (is_email($username)) {
		$user = get_user_by('email', $username);
	} else {
		$user = get_user_by('login', $username);
	}

	if (!$user) {

		return array('status' => 0, 'msg' => '用户不存在');

	} elseif (wp_check_password($password, $user->data->user_pass, $user->ID)) {

		wp_set_current_user($user->ID, $user->user_login);
		wp_set_auth_cookie($user->ID, $remember);

		if ($redirect_to) {
			return array('status' => 3, 'msg' => $redirect_to);
		} else {
			return array('status' => 1, 'msg' => '登录成功！');
		}

	} else {

		return array('status' => 0, 'msg' => '账户密码不匹配！');
	}

}

/**
 *@since 初始化
 *用户资料修改：昵称，简介，字段等
 *修改账户密码、邮箱，请使用：wnd_wpdate_account
 */
function wnd_update_profile() {

	if (empty($_POST)) {
		return array('status' => 0, 'msg' => '获取用户数据失败！');
	}
	//获取表单提交的用户字段并合并到对应数组
	$user_meta_array = wnd_get_form_data($form_date_type = 'user', 'user_meta_array');
	$wp_user_meta_array = wnd_get_form_data($form_date_type = 'user', 'wp_user_meta_array');

	// ################### 组成用户数据
	$user = wp_get_current_user();
	$user_id = $user->ID;
	if (!$user_id) {
		return array('status' => 0, 'msg' => '获取用户ID失败！');
	}

	$display_name = $_POST['_user_display_name'];
	$user_url = $_POST['_user_user_url'];

	// 初始化用户及profile字段数据
	$user_array = array('ID' => $user_id, 'display_name' => $display_name, 'user_url' => $user_url);
	$user_meta_array = array(); //自定义数组字段 wnd_user_meta
	$wp_user_meta_array = array(); //WordPress 原生用户字段

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
 */
function wnd_update_account() {

	$user = wp_get_current_user();
	$user_id = $user->ID;
	if (!$user_id) {
		return array('status' => 0, 'msg' => '获取用户ID失败！');
	}

	$user_array = array('ID' => $user_id);
	$user_pass = $_POST['_user_user_pass'];
	$new_password = $_POST['_user_new_pass'];
	$new_password_repeat = $_POST['_user_new_pass_repeat'];
	$email = $_POST['_user_user_email'];

	// 修改密码
	if (!empty($new_password_repeat)) {

		if (strlen($new_password) < 6) {
			return array('status' => 0, 'msg' => '新密码不能低于6位！');

		} elseif ($new_password_repeat != $new_password) {
			return array('status' => 0, 'msg' => '两次输入的新密码不匹配！');

		} else {
			// 新密码没有错误 更新数据中插入密码字段
			$array_temp = array('user_pass' => $new_password);
			$user_array = array_merge($user_array, $array_temp);
		}

	}

	// 修改邮箱
	if ($email != $user->user_email) {
		if (!is_email($email)) {
			return array('status' => 0, 'msg' => '邮件格式错误！');
		} else {
			// 新email没有错误 更新数据中插入email字段
			$array_temp = array('user_email' => $email);
			$user_array = array_merge($user_array, $array_temp);
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

	//################### 更新用户
	$user_id = wp_update_user($user_array);

	// 更新失败，返回错误信息
	if (is_wp_error($user_id)) {

		$msg = $user_id->get_error_message();
		return array('status' => 0, 'msg' => $msg);

	}

	// 用户更新成功
	$return_array = apply_filters('wnd_update_account_return', array('status' => 1, 'msg' => '更新成功'), $user_id);
	return $return_array;

}

/**
 *@since 2019.02.10 用户找回密码
 */
function wnd_reset_password() {

	// $email = $_POST['_user_user_email'];
	$email_or_phone = $_POST['_meta_phone'] ?? $_POST['_user_user_email'];
	$text = $field == 'phone' ? '手机' : '邮箱';

	$new_password = $_POST['_user_new_pass'];
	$new_password_repeat = $_POST['_user_new_pass_repeat'];
	$code = $_POST['v_code'];

	// 验证密码正确性
	if (strlen($new_password) < 6) {
		return array('status' => 0, 'msg' => '新密码不能低于6位！');

	} elseif ($new_password_repeat != $new_password) {
		return array('status' => 0, 'msg' => '两次输入的新密码不匹配！');
	}

	if (empty($code)) {
		return array('status' => 0, 'msg' => '请输入验证秘钥！');
	}

	//获取用户
	$user = wnd_get_user_by($email_or_phone);
	if (!$user) {
		return array('status' => 0, 'msg' => '该' . $text . '尚未注册！');
	}

	// 检查秘钥
	$check = wnd_verify_code($email_or_phone, $code, $type = 'v');
	if ($check['status'] === 0) {
		return $check;

	} else {
		reset_password($user, $new_password);
		return array('status' => 1, 'msg' => '密码修改成功！<a onclick="wnd_ajax_modal(\'login_form\');">登录</a>');
	}

}
