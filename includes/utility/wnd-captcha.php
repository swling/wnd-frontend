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

	protected $user_ip;

	protected $captcha;

	protected $captcha_nonce;

	public static $captcha_name = 'captcha';

	public static $captcha_nonce_name = 'captcha_nonce';

	public function __construct() {
		$this->user_ip = wnd_get_user_ip();
		$this->appid   = wnd_get_config('captcha_appid');
		$this->appkey  = wnd_get_config('captcha_appkey');
	}

	/**
	 *自动选择子类处理当前业务
	 */
	public static function get_instance(): Wnd_Captcha{
		$service = wnd_get_config('captcha_service') ?: '';
		if (!$service) {
			throw new Exception(__('未配置 Captcha Service', 'wnd'));
		}

		$class_name = '\Wnd\Getway\Captcha\\' . $service;
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
	 *设置captcha nonce
	 */
	public function set_captcha_nonce($captcha_nonce) {
		$this->captcha_nonce = $captcha_nonce;
	}

	/**
	 * 请求服务器验证
	 */
	abstract public function validate();

	/**
	 *JavaScript
	 *构建手机及邮箱类发送人机校验脚本
	 */
	abstract public function render_send_code_script(): string;

	/**
	 *@since 0.8.64
	 *构建表单提交人机校验脚本
	 */
	abstract public function render_submit_form_script(): string;
}
