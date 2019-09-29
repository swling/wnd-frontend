<?php
/**
 *@since 2019.09.25
 *短信抽象类
 */
abstract class Wnd_Sms {
	// api属性
	protected $app_id;
	protected $app_key;
	protected $sign_name;

	// 短信实例属性
	protected $phone;
	protected $code;
	protected $template;

	public function __construct() {
		$this->app_id    = wnd_get_option('wnd', 'wnd_sms_appid');
		$this->app_key   = wnd_get_option('wnd', 'wnd_sms_appkey');
		$this->sign_name = wnd_get_option('wnd', 'wnd_sms_sign');
	}

	public function set_phone($phone) {
		$this->phone = $phone;
	}

	public function set_code($code) {
		$this->code = $code;
	}

	public function set_template($template) {
		$this->template = $template;
	}

	/**
	 * 发送短信
	 */
	abstract public function send();
}
