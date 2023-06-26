<?php
namespace Wnd\Utility;

use Wnd\Getway\Wnd_Captcha;
use Wnd\Model\Wnd_Auth_Code;

/**
 * 验证器
 * @since 0.8.61
 */
class Wnd_Validator {

	/**
	 * 封装手机或邮箱验证
	 */
	public static function validate_auth_code(string $type, array $data = []) {
		$data           = $data ?: wnd_get_json_request();
		$auth_code      = $data['auth_code'] ?? '';
		$email_or_phone = $data['phone'] ?? $data['_user_user_email'] ?? '';
		if (!$email_or_phone) {
			return true;
		}

		$auth = Wnd_Auth_Code::get_instance($email_or_phone);
		$auth->set_type($type);
		$auth->set_auth_code($auth_code);
		$auth->verify();
	}

	/**
	 * 发送短信或邮件验证码时，进行人机验证
	 * @since 2020.08.13
	 */
	public static function validate_captcha(array $data = []) {
		// 禁用人机校验
		if (!wnd_get_config('captcha_service')) {
			return true;
		}

		$data = $data ?: wnd_get_json_request();
		$auth = Wnd_Captcha::get_instance();
		$auth->set_captcha($data[Wnd_Captcha::$captcha_name] ?? '');
		$auth->set_captcha_nonce($data[Wnd_Captcha::$captcha_nonce_name] ?? '');
		$auth->validate();
	}
}
