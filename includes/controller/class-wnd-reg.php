<?php
namespace Wnd\Controller;

use Exception;
use Wnd\Controller\Wnd_Form_Handler;

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
class Wnd_Reg extends Wnd_Ajax_Controller {

	public static function execute() {
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
			$form_data = new Wnd_Form_Handler();
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
}
