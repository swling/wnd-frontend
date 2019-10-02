<?php
namespace Wnd\Controller;

use Wnd\Model\Wnd_Auth;
use \Exception;

/**
 *@since 2019.07.23 已登录用户设置邮箱
 *@param $_POST['_user_user_email'];
 *@param $_POST['auth_code']
 */
class Wnd_User_Bind_Email extends Wnd_Controller {

	public static function execute() {
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
}
