<?php
namespace Wnd\Model;

use Exception;

/**
 *@since 2019.08.13
 *验证授权
 *
 *邮件验证码
 *短信验证码
 */
class Wnd_Auth {

	// object 当前用户
	protected $current_user;

	// string 电子邮件
	protected $email_or_phone;

	// string 验证类型 register / reset_password / verify / bind
	protected $type;

	// string 验证码
	protected $auth_code;

	// int 直接指定需要验证的用户
	protected $verify_user_id;

	// bool 是否为邮件
	protected $is_email;

	// string 短信模板
	protected $template;

	// 短信服务商
	protected $sms_sp;

	// 数据库字段：Email or Phone
	protected $db_field;

	// 提示文字：邮箱 or 手机
	protected $text;

	/**
	 *@since 2019.08.13
	 *构造函数
	 **/
	public function __construct() {
		$this->sms_sp       = wnd_get_option('wnd', 'wnd_sms_sp');
		$this->auth_code    = wnd_random_code(6);
		$this->template     = wnd_get_option('wnd', 'wnd_sms_template');
		$this->current_user = wp_get_current_user();
	}

	/**
	 *设置邮件或手机号码
	 */
	public function set_email_or_phone($email_or_phone) {
		$this->email_or_phone = $email_or_phone;

		// 检测发送地址
		if (is_email($this->email_or_phone)) {
			$this->is_email = true;
			$this->text     = '邮箱';
			$this->db_field = 'email';

		} elseif (wnd_is_phone($this->email_or_phone)) {
			$this->text     = '手机';
			$this->db_field = 'phone';

		} else {
			throw new Exception('格式不正确！');
		}
	}

	/**
	 *设置验证码，覆盖本实例默认的验证码
	 */
	public function set_auth_code($auth_code) {
		$this->auth_code = $auth_code;
	}

	/**
	 *在明确需要验证的用户，但不确定当前验证邮箱或手机的情况下使用
	 */
	public function set_verify_user_id($user_id) {
		$this->verify_user_id = $user_id;
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
	 *设置短信服务商
	 */
	public function set_sms_sp($sms_sp) {
		$this->sms_sp = $sms_sp;
	}

	/**
	 *验证类型权限检测
	 */
	protected function check_type() {
		if (empty($this->email_or_phone)) {
			throw new Exception('发送地址为空！');
		}

		// 必须指定类型
		if (!$this->type) {
			throw new Exception('未指定验证类型！');
		}

		// 注册
		$temp_user = wnd_get_user_by($this->email_or_phone);
		if ($this->type == 'register' and $temp_user) {
			throw new Exception($this->text . '已注册！');
		}

		// 绑定
		if ($this->type == 'bind') {
			if (!$this->current_user->ID) {
				throw new Exception('请未登录后再绑定！');
			}
			if ($temp_user) {
				throw new Exception($this->text . '已注册！');
			}
		}

		// 找回密码
		if ($this->type == 'reset_password' and !$temp_user) {
			throw new Exception($this->text . '尚未注册！');
		}
	}

	/**
	 *@since 2019.02.10 权限检测
	 *此处的权限校验仅作为前端是否可以发送验证验证码的初级校验，较容易被绕过
	 *在对验证码正确性进行校验时，应该再次进行类型权限校验
	 *
	 *@param string $this->email_or_phone 	邮箱或手机
	 *@param string $this->type 			验证类型
	 *
	 *register / reset_password / verify / bind
	 *register / bind 	：注册、绑定	当前邮箱或手机已注册、则不可发送
	 *reset_password 	：找回密码 		当前邮箱或手机未注册、则不可发送
	 *
	 *@return true|exception
	 */
	protected function check_send() {
		$this->check_type();

		// 短信发送必须指定模板
		if (!$this->is_email and !$this->template) {
			throw new Exception('未指定短信模板！');
		}

		// 上次发送短信的时间，防止攻击
		global $wpdb;
		$send_time = $wpdb->get_var($wpdb->prepare("SELECT time FROM {$wpdb->wnd_users} WHERE {$this->db_field} = %s;", $this->email_or_phone));
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
		$this->check_send();

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
		if (!$this->insert()) {
			throw new Exception('写入数据库失败！');
		}

		$message = '邮箱验证秘钥【' . $this->auth_code . '】（不含括号），关键凭证，请勿泄露！';
		$action  = wp_mail($this->email_or_phone, '验证邮箱', $message);
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
		// 写入手机记录
		if (!$this->insert()) {
			throw new Exception('数据库写入失败！');
		}

		if ('tx' == $this->sms_sp) {
			$sms = new Wnd_Sms_TX();
		} elseif ('ali' == $this->sms_sp) {
			$sms = new Wnd_Sms_Ali();
		} else {
			throw new Exception('未指定短信服务商！');
		}

		$sms->set_phone($this->email_or_phone);
		$sms->set_code($this->auth_code);
		$sms->set_template($this->template);
		$sms->send();
	}

	/**
	 *校验验证码
	 *
	 *若已指定 $this->email_or_phone 则依据邮箱或手机校验
	 *若未指定邮箱及手机且当前用户已登录，则依据用户ID校验
	 *
	 *@since 初始化
	 *
	 *@param bool 		$$delete_after_verified 	验证成功后是否删除本条记录(对应记录必须没有绑定用户)
	 *@param string 	$this->email_or_phone 		邮箱或手机
	 *@param int 		$this->verify_user_id 		当前用户
	 *@param string 	$this->type 				验证类型
	 *@param string 	$this->auth_code	 		验证码
	 *
	 *@return true|exception
	 */
	public function verify(bool $delete_after_verified = false) {
		if (empty($this->auth_code)) {
			throw new Exception('校验失败：请填写验证码！');
		}
		if (empty($this->email_or_phone) and !$this->verify_user_id) {
			throw new Exception('校验失败：请填写' . $this->text . '！');
		}

		/**
		 *@since 2019.10.02
		 *若直接指定了验证用户ID，表示已确定需要验证的用户信息，绕过类型检测
		 */
		if (!$this->verify_user_id) {
			$this->check_type();
		}

		// 过期时间设置
		global $wpdb;
		$intervals = $this->is_email ? 3600 : 600;
		if ($this->email_or_phone) {
			$data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->wnd_users WHERE {$this->db_field} = %s;", $this->email_or_phone));
		} else {
			$data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->wnd_users WHERE user_id = %d;", $this->verify_user_id));
		}

		if (!$data) {
			throw new Exception('校验失败：请先获取验证码！');
		}
		if (time() - $data->time > $intervals) {
			throw new Exception('验证码已失效请重新获取！');
		}
		if ($this->auth_code != $data->code) {
			throw new Exception('校验失败：验证码不正确！');
		}

		/**
		 *@since 2019.07.23
		 *验证完成后是否删除
		 *删除的记录必须没有绑定用户
		 */
		if ($delete_after_verified) {
			$wpdb->delete($wpdb->wnd_users, array('ID' => $data->ID, 'user_id' => 0), array('%d'));
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
		if (!$this->email_or_phone) {
			throw new Exception('未指定邮箱或手机！');
		}

		$ID = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->wnd_users} WHERE {$this->db_field} = %s", $this->email_or_phone));
		if ($ID) {
			$db = $wpdb->update(
				$wpdb->wnd_users,
				array('code' => $this->auth_code, 'time' => time()),
				array($this->db_field => $this->email_or_phone),
				array('%s', '%d'),
				array('%s')
			);
		} else {
			$db = $wpdb->insert(
				$wpdb->wnd_users,
				array($this->db_field => $this->email_or_phone, 'code' => $this->auth_code, 'time' => time()),
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
		if (!$this->email_or_phone) {
			throw new Exception('未指定邮箱或手机！ ');
		}

		// 手机注册用户
		if ($reg_user_id) {
			$wpdb->update(
				$wpdb->wnd_users,
				array('code' => '', 'time' => time(), 'user_id' => $reg_user_id),
				array($this->db_field => $this->email_or_phone),
				array('%s', '%d', '%d'),
				array('%s')
			);
			//其他操作
		} else {
			$wpdb->update(
				$wpdb->wnd_users,
				array('code' => '', 'time' => time()),
				array($this->db_field => $this->email_or_phone),
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
		return $wpdb->delete(
			$wpdb->wnd_users,
			array($this->db_field => $this->email_or_phone),
			array('%s')
		);
	}

}
