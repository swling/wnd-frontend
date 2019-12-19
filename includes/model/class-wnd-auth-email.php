<?php
namespace Wnd\Model;

use Exception;

/**
 *@since 2019.12.19
 *验证授权
 *
 *邮件验证码
 */
class Wnd_Auth_Email extends Wnd_Auth {

	// 数据库字段：Email
	protected $db_field = 'email';

	// 提示文字：邮箱
	protected $text = '邮箱';

	// 验证码有效时间（秒）
	protected $valid_time = 3600;

	public function __construct($auth_object) {
		parent::__construct();

		$this->auth_object    = $auth_object;
		$this->db_field_value = $auth_object;
	}

	/**
	 *@since 2019.01.28 发送邮箱验证码
	 *@param string $this->auth_object 	邮箱或手机
	 *@param string $this->auth_code  	验证码
	 *@return true|exception
	 */
	public function send() {
		// 权限检测
		$this->check_send();

		if (!$this->insert()) {
			throw new Exception('写入数据库失败');
		}

		$message = '邮箱验证秘钥【' . $this->auth_code . '】（不含括号），关键凭证，请勿泄露';
		$action  = wp_mail($this->auth_object, '验证邮箱', $message);
		if ($action) {
			return true;
		} else {
			throw new Exception('发送失败，请稍后重试');
		}
	}
}
