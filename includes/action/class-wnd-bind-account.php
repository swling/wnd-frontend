<?php
namespace Wnd\Action;

use Exception;
use Wnd\Model\Wnd_User_Bind;

/**
 *@since 2019.07.23 已登录用户绑定邮箱或手机
 *@param $_POST['_user_user_email']; 	邮箱地址
 *@param $_POST['phone'];				手机号码
 *@param $_POST['auth_code'] 		 	验证码
 *@param $_POST['_user_user_pass'] 		当前密码
 */
class Wnd_Bind_Account extends Wnd_Action_Ajax {

	public static function execute(): array{
		$email_or_phone = $_POST['_user_user_email'] ?? ($_POST['phone'] ?? null);
		$auth_code      = $_POST['auth_code'] ?? null;
		$password       = $_POST['_user_user_pass'] ?? null;

		// 绑定
		try {
			$bind = new Wnd_User_Bind;
			$bind->set_password($password);
			$bind->set_auth_code($auth_code);
			$bind->set_email_or_phone($email_or_phone);
			$bind->bind();
			return array('status' => 1, 'msg' => '绑定成功！');
		} catch (Exception $e) {
			return array('status' => 0, 'msg' => $e->getMessage());
		}
	}
}
