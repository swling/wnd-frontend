<?php
namespace Wnd\Model;

use Exception;

/**
 * 验证授权
 * 邮件验证码
 * 短信验证码
 * @since 2019.08.13
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
	 * 构造函数
	 * @since 2019.08.13
	 */
	public function __construct(string $identifier) {
		$this->identifier = $identifier;
		$this->auth_code  = wnd_random_code(6);
		$this->user       = wp_get_current_user();
		$this->intervals  = wnd_get_config('min_verification_interval') ?: 60;
	}

	/**
	 * 设置：邮件、手机号码、WP_User object
	 */
	public static function get_instance(string $identifier) {
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
	 * 设置验证码，覆盖本实例默认的验证码
	 */
	public function set_auth_code(string $auth_code) {
		$this->auth_code = $auth_code;
	}

	/**
	 * 设置验证类型
	 */
	public function set_type(string $type) {
		if (!in_array($type, ['register', 'reset_password', 'verify', 'bind'])) {
			throw new Exception(__('类型无效，请选择：register / reset_password / verify / bind', 'wnd'));
		}

		$this->type = $type;
	}

	/**
	 * 设置信息模板
	 */
	public function set_template(string $template) {
		$this->template = $template;
	}

	/**
	 * register / reset_password / verify / bind
	 * register / bind 	：注册、绑定	当前邮箱或手机已注册、则不可发送
	 * reset_password 	：找回密码 		当前邮箱或手机未注册、则不可发送
	 * @since 2019.02.10 类型权限检测
	 */
	protected function check_type() {
		// 必须指定类型
		if (!$this->type) {
			throw new Exception(__('未指定验证类型', 'wnd'));
		}

		// 注册
		$temp_user = wnd_get_user_by($this->identifier);
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
	 * 信息发送权限检测
	 * @since 2019.02.10
	 */
	protected function check_send() {
		$data = $this->get_db_record();
		if (!$data) {
			return;
		}

		// 计算当前时刻距离上次发送的间隔时间
		$send_time    = $data->time ?? 0;
		$elapsed_time = time() - $send_time;

		// 频次控制
		if ($elapsed_time < $this->intervals) {
			throw new Exception(__('操作太频繁，请等待', 'wnd') . ($this->intervals - $elapsed_time) . __('秒', 'wnd'));
		}

		// 五分钟内，重复请求验证码，保持验证码不变。防止多次发送不同验证码造成混淆 @date 2020.12.14
		if ($elapsed_time < 300) {
			$this->auth_code = $data->credential ?: $this->auth_code;
		}
	}

	/**
	 * 检测权限，写入记录，并发送短信或邮箱
	 * @since 初始化
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
	 * 发送验证码
	 * @since 2019.02.21
	 */
	abstract protected function send_code();

	/**
	 * 校验验证码
	 *
	 * 若已指定 $this->identifier 则依据邮箱或手机校验
	 * 若未指定邮箱及手机且当前用户已登录，则依据用户ID校验
	 *
	 * @since 初始化
	 *
	 * @param bool $delete_after_verified 	验证成功后是否删除本条记录(对应记录必须没有绑定用户)
	 */
	public function verify(bool $delete_after_verified = false) {
		$this->check_auth_fields(true);

		/**
		 * 类型检测
		 * @since 2019.10.02
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
		 * 验证完成后是否删除
		 * 删除的记录必须没有绑定用户
		 * @since 2019.07.23
		 */
		if ($delete_after_verified) {
			global $wpdb;
			$wpdb->delete($wpdb->wnd_auths, ['ID' => $data->ID, 'user_id' => 0], ['%d', '%d']);
		}

		return true;
	}

	/**
	 * @since 2019.02.09 手机及邮箱验证模块
	 */
	protected function insert() {
		$this->check_auth_fields(true);

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
	 * 重置验证码
	 * @param int $user_id 	注册用户ID
	 */
	public function reset_code(int $user_id = 0) {
		$this->check_auth_fields(false);

		global $wpdb;
		if ($user_id) {
			$wpdb->update(
				$wpdb->wnd_auths,
				['credential' => '', 'time' => time(), 'user_id' => $user_id],
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
	 * 删除
	 * @since 2019.07.23
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
	 * @since 2019.12.19
	 */
	protected function get_db_record() {
		$this->check_auth_fields(false);

		global $wpdb;
		$data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->wnd_auths WHERE identifier = %s AND type = %s",
				$this->identifier, $this->identity_type
			)
		);

		return $data;
	}

	/**
	 * 检测验证数据库基本属性是否完整
	 * - 检测 identity_type 字段：设备类型
	 * - 检测 identifier 	字段：设备地址，如邮箱或手机号等
	 * - 检测 auth_code 	字段：验证码
	 *
	 * @param bool $check_auth_code 是否检查验证码字段
	 */
	protected function check_auth_fields(bool $check_auth_code) {
		if (!$this->identity_type) {
			throw new Exception(__('未指定验证设备类型', 'wnd'));
		}

		if (!$this->identifier) {
			throw new Exception(__('未指定验证设备', 'wnd') . '&nbsp;' . static::$text);
		}

		if ($check_auth_code and empty($this->auth_code)) {
			throw new Exception(__('验证码为空', 'wnd'));
		}
	}
}
