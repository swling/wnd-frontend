<?php
/**
 *@since 2019.08.13
 *验证授权
 *
 *邮件验证码
 *短信验证码
 */
class Wnd_Auth {

	// string 电子邮件
	protected $email_or_phone;

	// bool 是否为邮件
	protected $is_email;

	// string 验证类型 register / reset_password / verify
	protected $type;

	// string 短信模板
	protected $template;

	// string 验证码
	protected $auth_code;

	// object 当前用户
	protected $user;

	/**
	 *@since 2019.08.13
	 *构造函数
	 **/
	public function __construct() {
		$this->auth_code = wnd_random_code(6);
		$this->template = wnd_get_option('wnd', 'wnd_sms_template');
		$user = wp_get_current_user();
	}

	/**
	 *设置邮件或手机号码
	 */
	public function set_email_or_phone($email_or_phone) {
		$this->email_or_phone = $email_or_phone;
		$this->is_email = is_email($this->email_or_phone);
	}

	/**
	 *设置邮件或手机号码
	 */
	public function set_auth_code($auth_code) {
		$this->auth_code = $auth_code;
	}

	/**
	 *设置验证类型
	 */
	public function set_type($type) {
		if (!in_array($type, array('register', 'reset_password', 'verify', 'bind'))) {
			throw new Exception('设定类型无效，请选择：register / reset_password / verify / bind');
		}

		$this->type = $type;
	}

	/**
	 *设置短信模板
	 */
	public function set_template($template) {
		$this->template = $template;
	}

	/**
	 *@since 2019.02.10 权限检测
	 *此处的权限校验仅作为前端是否可以发送验证验证码的初级校验，较容易被绕过
	 *在对验证码正确性进行校验时，应该再次进行类型权限校验
	 *
	 *@param string $this->email_or_phone 	邮箱或手机
	 *@param string $this->type 			验证类型
	 *
	 *@return true|exception
	 */
	protected function check() {
		if (empty($this->email_or_phone) && !$this->user->ID) {
			throw new Exception('发送地址为空！');
		}

		// 必须指定类型
		if (!$this->type) {
			throw new Exception('未指定验证类型！');
		}

		// 发送地址比如为邮箱或手机
		if (is_email($this->email_or_phone)) {
			$text = '邮箱';
		} elseif (wnd_is_phone($this->email_or_phone)) {
			$text = '手机';
		} else {
			throw new Exception('格式不正确！');
		}

		// 短信发送必须指定模板
		if (!$this->is_email and !$this->template) {
			throw new Exception('未指定短信模板！');
		}

		// 注册类型去重
		$temp_user = wnd_get_user_by($this->email_or_phone);
		if ($this->type == 'register' and $temp_user) {
			throw new Exception($text . '已注册过！');
			// 绑定
		} elseif ($this->type == 'bind' and $temp_user) {
			throw new Exception($text . '已注册过！');
			// 找回密码
		} elseif ($this->type == 'reset_password' and !$temp_user) {
			throw new Exception($text . '尚未注册！');
		}

		// 上次发送短信的时间，防止攻击
		global $wpdb;
		$field = $this->is_email ? 'email' : 'phone';
		$send_time = $wpdb->get_var($wpdb->prepare("SELECT time FROM {$wpdb->wnd_users} WHERE {$field} = %s;", $this->email_or_phone));
		$send_time = $send_time ?: 0;
		if ($send_time and (time() - $send_time < 90)) {
			throw new Exception('操作太频繁，请' . (90 - (time() - $send_time)) . '秒后重试！');
		}

		return true;
	}

	/**
	 *@since 2019.02.21 发送验证码给匿名用户
	 *@param string $this->email_or_phone 	邮箱或手机
	 *@param string $this->auth_code  		验证码
	 *@param string $this->type 			验证类型
	 */
	public function send() {
		// 权限检测
		$this->check();

		// 发送
		if ($this->is_email) {
			return $this->send_mail_code();
		} else {
			return $this->send_sms_code();
		}
	}

	/**
	 *@since 2019.02.22 发送验证码给已知用户
	 *@param mixed  $user
	 *@param string $this->email_or_phone 	邮箱或手机
	 *@param string $this->auth_code  		验证码
	 *@param string $this->type 			验证类型
	 */
	public function send_to_user($is_email) {
		// 根据发送类型获取当前用户邮箱或手机
		$user = $this->user;
		$this->email_or_phone = $is_email ? $user->user_email : wnd_get_user_phone($user->ID);

		// 权限检测
		$this->check();

		// 发送
		if ($this->is_email) {
			return $this->send_mail_code();
		} else {
			return $this->send_sms_code();
		}
	}

	/**
	 *@since 2019.01.28 发送邮箱验证码
	 *@param string $this->email_or_phone 	邮箱或手机
	 *@param string $this->auth_code  		验证码
	 *@return true|exception
	 */
	protected function send_mail_code() {
		$action = $this->insert();
		if (!$action) {
			throw new Exception('写入数据库失败！');
		}

		$message = '邮箱验证秘钥【' . $this->auth_code . '】（不含括号），关键凭证，请勿泄露！';
		$action = wp_mail($this->email_or_phone, '验证邮箱', $message);
		if ($action) {
			return true;
		} else {
			throw new Exception('发送失败，请稍后重试！');
		}
	}

	/**
	 *@since 初始化
	 *通过ajax发送短信
	 *点击发送按钮，通过js获取表单填写的手机号，检测并发送短信
	 *@param string $this->email_or_phone 	邮箱或手机
	 *@param string $this->auth_code 		验证码
	 *@return true|exception
	 */
	protected function send_sms_code() {
		require WND_PATH . 'components/tencent-sms/sendSms.php'; //腾讯云短信

		// 写入手机记录
		if (!$this->insert()) {
			throw new Exception('数据库写入失败！');
		}

		$send_status = wnd_send_sms($this->email_or_phone, $this->auth_code, $this->template);
		if ($send_status->result == 0) {
			return true;
		} else {
			throw new Exception('系统错误，请联系客服处理！');
		}
	}

	/**
	 *校验短信验证
	 *@since 初始化
	 *@return true|exception
	 *@param string $this->email_or_phone 邮箱或手机
	 *@param string $this->type 			验证类型
	 *@param string $this->auth_code	 	验证码
	 */
	public function verify() {
		global $wpdb;
		$field = $this->is_email ? 'email' : 'phone';
		$text = $this->is_email ? '邮箱' : '手机';

		if (empty($this->auth_code)) {
			throw new Exception('校验失败：请填写验证码！');
		}

		if (empty($this->email_or_phone)) {
			throw new Exception('校验失败：请填写' . $text . '！');
		}

		// 注册类型去重
		$temp_user = wnd_get_user_by($this->email_or_phone);
		if ($this->type == 'register' and $temp_user) {
			throw new Exception($text . '已注册过！');
			// 绑定
		} elseif ($this->type == 'bind' and $temp_user) {
			throw new Exception($text . '已注册过！');
			// 找回密码
		} elseif ($this->type == 'reset_password' and !$temp_user) {
			throw new Exception($text . '尚未注册！');
		}

		// 过期时间设置
		$intervals = $field == 'phone' ? 600 : 3600;
		$data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->wnd_users WHERE {$field} = %s;", $this->email_or_phone));
		if (!$data) {
			throw new Exception('校验失败：请先获取验证码！');
		}

		if (time() - $data->time > $intervals) {
			throw new Exception('验证码已失效请重新获取！');
		}

		if ($this->auth_code != $data->code) {
			throw new Exception('校验失败：验证码不正确！');
		}

		return true;
	}

	/**
	 *@since 2019.02.09 手机及邮箱验证模块
	 *@param string $this->email_or_phone 	邮箱或手机
	 *@param string $this->auth_code 		验证码
	 *@return int|exception
	 */
	protected function insert() {
		global $wpdb;
		$field = $this->is_email ? 'email' : 'phone';
		$code = $this->auth_code;
		if (!$this->email_or_phone) {
			throw new Exception('未指定邮箱或手机！');
		}

		$ID = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->wnd_users} WHERE {$field} = %s", $this->email_or_phone));
		if ($ID) {
			$db = $wpdb->update(
				$wpdb->wnd_users,
				array('code' => $code, 'time' => time()),
				array($field => $this->email_or_phone),
				array('%s', '%d'),
				array('%s')
			);
		} else {
			$db = $wpdb->insert(
				$wpdb->wnd_users,
				array($field => $this->email_or_phone, 'code' => $code, 'time' => time()),
				array('%s', '%s', '%d')
			);
		}

		return $db;
	}

	/**
	 *@param int 	$reg_user_id  			注册用户ID
	 *@param string $this->email_or_phone 	邮箱或手机
	 *重置验证码
	 */
	public function reset_code($reg_user_id = 0) {
		global $wpdb;
		$field = $this->is_email ? 'email' : 'phone';
		if (!$this->email_or_phone) {
			throw new Exception('未指定邮箱或手机！ ');
		}

		// 手机注册用户
		if ($reg_user_id) {
			$wpdb->update(
				$wpdb->wnd_users,
				array('code' => '', 'time' => time(), 'user_id' => $reg_user_id),
				array($field => $this->email_or_phone),
				array('%s', '%d', '%d'),
				array('%s')
			);
			//其他操作
		} else {
			$wpdb->update(
				$wpdb->wnd_users,
				array('code' => '', 'time' => time()),
				array($field => $this->email_or_phone),
				array('%s', '%d'),
				array('%s')
			);
		}
	}

	/**
	 *@since 2019.07.23
	 *验证完成后是否删除
	 *@param string $this->email_or_phone 邮箱或手机
	 */
	public function delete() {
		global $wpdb;
		$field = $this->is_email ? 'email' : 'phone';
		return $wpdb->delete(
			$wpdb->wnd_users,
			array($field => $this->email_or_phone),
			array('%s')
		);
	}

}
