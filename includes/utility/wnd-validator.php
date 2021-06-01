<?php
namespace Wnd\Utility;

use Wnd\Getway\Wnd_Captcha;
use Wnd\Model\Wnd_Auth;

/**
 *验证器
 *@since 0.8.61
 */
class Wnd_Validator {

	/**
	 *封装手机或邮箱验证
	 */
	public static function validate_auth_code($type) {
		$auth_code      = $_POST['auth_code'] ?? '';
		$email_or_phone = $_POST['phone'] ?? $_POST['_user_user_email'] ?? '';

		$auth = Wnd_Auth::get_instance($email_or_phone);
		$auth->set_type($type);
		$auth->set_auth_code($auth_code);
		$auth->verify();
	}

	/**
	 *@since 2020.08.13
	 *发送短信或邮件验证码时，进行人机验证
	 */
	public static function validate_captcha() {
		// 禁用人机校验
		if (!wnd_get_config('captcha_service')) {
			return true;
		}

		$auth = Wnd_Captcha::get_instance();
		$auth->set_captcha($_POST[Wnd_Captcha::$captcha_name] ?? '');
		$auth->set_captcha_nonce($_POST[Wnd_Captcha::$captcha_nonce_name] ?? '');
		$auth->validate();
	}
}
