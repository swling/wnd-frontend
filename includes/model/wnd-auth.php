<?php
namespace Wnd\Model;

use Exception;

/**
 *@since 2019.08.13
 *验证授权
 *
 *邮件验证码
 *短信验证码
 *
 */
abstract class Wnd_Auth {

	// object 当前用户
	protected $user;

	// string 验证类型 register / reset_password / verify / bind
	protected $type;

	// string 验证码
	protected $auth_code;

	// 验证设备类型
	protected $identity_type;

	// string|object 电子邮件/手机号/WP_User object
	protected $identifier;

	// 提示文字：邮箱 or 手机
	protected static $text;

	// string 信息模板
	protected $template;

	// 验证码有效时间（秒）
	protected $valid_time = 600;

	// 同一地址两次发送时间的最短间隔（秒）
	protected $intervals;

	/**
	 *@since 2019.08.13
	 *构造函数
	 **/
	public function __construct($identifier) {
		$this->identifier = $identifier;
		$this->auth_code  = wnd_random_code(6);
		$this->user       = wp_get_current_user();
		$this->intervals  = wnd_get_config('min_verification_interval');
	}

	/**
	 *设置：邮件、手机号码、WP_User object
	 */
	public static function get_instance($identifier) {
		if ($identifier instanceof \WP_User) {
			static::$text = __('用户', 'wnd');
			return new Wnd_Auth_User($identifier);
		}

		if (is_email($identifier)) {
			static::$text = __('邮箱', 'wnd');
			return new Wnd_Auth_Email($identifier);
		}

		if (wnd_is_mobile($identifier)) {
			static::$text = __('手机', 'wnd');
			return new Wnd_Auth_Phone($identifier);
		}

		throw new Exception(__('格式不正确', 'wnd'));
	}

	/**
	 *设置验证码，覆盖本实例默认的验证码
	 */
	public function set_auth_code($auth_code) {
		$this->auth_code = $auth_code;
	}

	/**
	 *设置验证类型
	 */
	public function set_type($type) {
		if (!in_array($type, ['register', 'reset_password', 'verify', 'bind'])) {
			throw new Exception(__('类型无效，请选择：register / reset_password / verify / bind', 'wnd'));
		}

		$this->type = $type;
	}

	/**
	 *设置信息模板
	 */
	public function set_template($template) {
		$this->template = $template;
	}

	/**
	 *@since 2019.02.10 类型权限检测
	 *
	 *@param string $this->identifier 	邮箱或手机
	 *@param string $this->type 		验证类型
	 *
	 *register / reset_password / verify / bind
	 *register / bind 	：注册、绑定	当前邮箱或手机已注册、则不可发送
	 *reset_password 	：找回密码 		当前邮箱或手机未注册、则不可发送
	 */
	protected function check_type() {
		if (empty($this->identifier)) {
			throw new Exception(__('请填写', 'wnd') . '&nbsp;' . static::$text);
		}

		// 必须指定类型
		if (!$this->type) {
			throw new Exception(__('未指定验证类型', 'wnd'));
		}

		// 注册
		$temp_user = is_object($this->identifier) ? $this->identifier : wnd_get_user_by($this->identifier);
		if ('register' == $this->type and $temp_user) {
			throw new Exception(static::$text . '&nbsp;' . __('已注册', 'wnd'));
		}

		// 绑定
		if ('bind' == $this->type) {
			if (!$this->user->ID) {
				throw new Exception(__('请登录', 'wnd'));
			}
			if ($temp_user) {
				throw new Exception(static::$text . '&nbsp;' . __('已注册', 'wnd'));
			}
		}

		// 找回密码
		if ('reset_password' == $this->type and !$temp_user) {
			throw new Exception(static::$text . '&nbsp;' . __('尚未注册', 'wnd'));
		}
	}

	/**
	 *@since 2019.02.10
	 *
	 *信息发送权限检测
	 *
	 */
	protected function check_send() {
		$send_time = $this->get_db_record()->time ?? 0;
		if ($send_time and (time() - $send_time < $this->intervals)) {
			throw new Exception(__('操作太频繁，请等待', 'wnd') . ($this->intervals - (time() - $send_time)) . __('秒', 'wnd'));
		}
	}

	/**
	 *@since 初始化
	 *检测权限，写入记录，并发送短信或邮箱
	 *
	 *@param string $this->identifier 	邮箱或手机
	 *@param string $this->auth_code 	验证码
	 */
	public function send() {
		// 类型检测
		$this->check_type();

		// 权限检测
		$this->check_send();

		// 写入数据记录
		$this->insert();

		// 发送短信或邮件
		$this->send_code();
	}

	/**
	 *@since 2019.02.21
	 *发送验证码
	 */
	abstract protected function send_code();

	/**
	 *校验验证码
	 *
	 *若已指定 $this->identifier 则依据邮箱或手机校验
	 *若未指定邮箱及手机且当前用户已登录，则依据用户ID校验
	 *
	 *@since 初始化
	 *
	 *@param bool 		$$delete_after_verified 	验证成功后是否删除本条记录(对应记录必须没有绑定用户)
	 *@param string 	$this->identifier 			邮箱/手机/用户
	 *@param string 	$this->type 				验证类型
	 *@param string 	$this->auth_code	 		验证码
	 *
	 *@return true|exception
	 */
	public function verify(bool $delete_after_verified = false) {
		if (empty($this->auth_code)) {
			throw new Exception(__('校验失败：请填写验证码', 'wnd'));
		}
		if (empty($this->identifier)) {
			throw new Exception(static::$text . '&nbsp;' . __('不可为空', 'wnd'));
		}

		/**
		 *@since 2019.10.02
		 *类型检测
		 */
		$this->check_type();

		// 有效性校验
		$data = $this->get_db_record();
		if (!$data or !$data->credential) {
			throw new Exception(__('校验失败：请先获取验证码', 'wnd'));
		}
		if (time() - $data->time > $this->valid_time) {
			throw new Exception(__('校验失败：验证码已过期', 'wnd'));
		}
		if ($this->auth_code != $data->credential) {
			throw new Exception(__('校验失败：验证码不正确', 'wnd'));
		}

		/**
		 *@since 2019.07.23
		 *验证完成后是否删除
		 *删除的记录必须没有绑定用户
		 */
		if ($delete_after_verified) {
			global $wpdb;
			$wpdb->delete($wpdb->wnd_auths, ['ID' => $data->ID, 'user_id' => 0], ['%d', '%d']);
		}

		return true;
	}

	/**
	 *@since 2019.02.09 手机及邮箱验证模块
	 *@param string $this->identity_type 	邮箱或手机数据库字段名
	 *@param string $this->identifier 		邮箱或手机
	 *@param string $this->auth_code 		验证码
	 *@return int|exception
	 */
	protected function insert() {
		if (!$this->identity_type or !$this->identifier) {
			throw new Exception(__('未定义数据库写入字段名或对应值', 'wnd'));
		}

		if (empty($this->auth_code)) {
			throw new Exception(__('验证码为空', 'wnd'));
		}

		global $wpdb;
		$ID = $this->get_db_record()->ID ?? 0;
		if ($ID) {
			$db = $wpdb->update(
				$wpdb->wnd_auths,
				['credential' => $this->auth_code, 'time' => time()],
				['identifier' => $this->identifier, 'type' => $this->identity_type],
				['%s', '%d'],
				['%s', '%s']
			);
		} else {
			$db = $wpdb->insert(
				$wpdb->wnd_auths,
				['identifier' => $this->identifier, 'type' => $this->identity_type, 'credential' => $this->auth_code, 'time' => time()],
				['%s', '%s', '%s', '%d']
			);
		}

		if (!$db) {
			throw new Exception(__('数据库写入失败', 'wnd'));
		}
	}

	/**
	 *@param int 	$reg_user_id  			注册用户ID
	 *@param string $this->identity_type 	邮箱或手机数据库字段名
	 *@param string $this->identifier 		邮箱或手机
	 *重置验证码
	 */
	public function reset_code($reg_user_id = 0) {
		if (!$this->identity_type or !$this->identifier) {
			throw new Exception(__('未定义数据库查询字段名或对应值', 'wnd'));
		}

		global $wpdb;
		if ($reg_user_id) {
			$wpdb->update(
				$wpdb->wnd_auths,
				['credential' => '', 'time' => time(), 'user_id' => $reg_user_id],
				['identifier' => $this->identifier, 'type' => $this->identity_type],
				['%s', '%d', '%d'],
				['%s', '%s']
			);

		} else {
			$wpdb->update(
				$wpdb->wnd_auths,
				['credential' => '', 'time' => time()],
				['identifier' => $this->identifier, 'type' => $this->identity_type],
				['%s', '%d'],
				['%s', '%s']
			);
		}
	}

	/**
	 *@since 2019.07.23
	 *删除
	 *@param string $this->identity_type 	据库字查询段名
	 *@param string $this->identifier 		数据库查询字段值
	 */
	public function delete() {
		global $wpdb;
		return $wpdb->delete(
			$wpdb->wnd_auths,
			['identifier' => $this->identifier, 'type' => $this->identity_type],
			['%s', '%s']
		);
	}

	/**
	 *@param string $this->identity_type 	据库字查询段名
	 *@param string $this->identifier 		数据库查询字段值
	 *
	 *@since 2019.12.19
	 */
	protected function get_db_record() {
		global $wpdb;
		$data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->wnd_auths WHERE identifier = %s AND type = %s",
				$this->identifier, $this->identity_type
			)
		);

		return $data;
	}
}
