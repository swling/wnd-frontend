<?php
namespace Wnd\Utility;

use Exception;

/**
 *@since 2020.08.11
 *验证码后端校验
 */
abstract class Wnd_Captcha {

	protected $url;

	protected $appid;

	protected $appkey;

	protected $captcha;

	protected $user_ip;

	public function __construct() {
		$this->user_ip = wnd_get_user_ip();
		$this->appid   = wnd_get_config('captcha_appid');
		$this->appkey  = wnd_get_config('captcha_appkey');
	}

	/**
	 *自动选择子类处理当前业务
	 */
	public static function get_instance(): Wnd_Captcha{
		$service    = wnd_get_config('captcha_service') ?: 'Tencent';
		$class_name = __NAMESPACE__ . '\\' . 'Wnd_Captcha_' . $service;
		if (class_exists($class_name)) {
			return new $class_name();
		} else {
			throw new Exception(__('未定义子类', 'wnd') . ':' . $class_name);
		}
	}

	/**
	 *设置captcha
	 */
	public function set_captcha($captcha) {
		$this->captcha = $captcha;
	}

	/**
	 * 请求服务器验证
	 */
	abstract public function validate();

	/**
	 *JavaScript
	 */
	abstract public function render_script(): string;
}
