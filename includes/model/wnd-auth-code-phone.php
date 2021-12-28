<?php
namespace Wnd\Model;

use Wnd\Getway\Wnd_Sms;

/**
 * 验证授权
 * 短信验证码
 * @since 2019.12.19
 */
class Wnd_Auth_Code_Phone extends Wnd_Auth_Code {

	// 数据库字段：phone
	protected $identity_type = 'phone';

	// 验证码有效时间（秒）
	protected $valid_time = 600;

	public function __construct($identifier) {
		parent::__construct($identifier);

		$this->template      = wnd_get_config('sms_template_v');
		$this->identity_name = __('手机', 'wnd');
	}

	/**
	 * 通过ajax发送短信
	 * 点击发送按钮，通过js获取表单填写的手机号，检测并发送短信
	 */
	protected function send_code() {
		$sms = Wnd_Sms::get_instance();
		$sms->set_phone($this->identifier);
		$sms->set_code($this->auth_code);
		$sms->set_valid_time($this->valid_time / 60);
		$sms->set_template($this->template);
		$sms->send();
	}
}
