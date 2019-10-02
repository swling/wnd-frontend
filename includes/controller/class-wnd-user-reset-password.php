<?php
namespace Wnd\Controller;

use Wnd\Model\Wnd_Auth;
use \Exception;

/**
 *@since 2019.02.10 用户找回密码
 *@param $_POST['phone'] ?? $_POST['_user_user_email'];
 *@param $_POST['auth_code']
 *@param $_POST['_user_new_pass']
 *@param $_POST['_user_new_pass_repeat']
 */
class Wnd_User_Reset_Password extends Wnd_Controller {

	public static function execute() {
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
			$auth->set_verify_user_id($user->ID);
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
}
