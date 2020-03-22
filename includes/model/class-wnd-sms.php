<?php
namespace Wnd\Model;

use Exception;

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

	// 实例化
	public static function get_instance() {
		// 获取短信服务商
		$sms_sp = wnd_get_option('wnd', 'wnd_sms_sp');

		if ('tx' == $sms_sp) {
			return new Wnd_Sms_TX();
		} elseif ('ali' == $sms_sp) {
			return new Wnd_Sms_Ali();
		} else {
			throw new Exception(__('指定短信服务商未完成配置', 'wnd'));
		}
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
