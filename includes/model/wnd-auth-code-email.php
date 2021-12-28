<?php
namespace Wnd\Model;

use Exception;

/**
 * 验证授权
 * 邮件验证码
 * @since 2019.12.19
 */
class Wnd_Auth_Code_Email extends Wnd_Auth_Code {

	// 数据库字段：Email
	protected $identity_type = 'email';

	// 验证码有效时间（秒）
	protected $valid_time = 3600;

	public function __construct($identifier) {
		parent::__construct($identifier);

		$this->identity_name = __('邮箱', 'wnd');
	}

	/**
	 * @since 2019.01.28 发送邮箱验证码
	 */
	protected function send_code() {
		$message = __('邮箱验证秘钥') . '【' . $this->auth_code . '】' . __('（不含括号），关键凭证，请勿泄露', 'wnd');
		$action  = wp_mail($this->identifier, __('验证邮箱', 'wnd'), $message);
		if ($action) {
			return true;
		} else {
			throw new Exception(__('发送失败，请稍后重试', 'wnd'));
		}
	}
}
