<?php
namespace Wnd\Utility;

use Exception;

/**
 *用户安全防护
 *@since 0.8.61
 */
class Wnd_Defender_User {

	/**
	 *Wnd User Meta Key
	 */
	protected static $meta_key = 'login_log';

	/**
	 *默认 log 数据
	 */
	protected static $default_user_log = [
		'failure_time'  => 0,
		'failure_count' => 0,
	];

	/**
	 *拦截计数时间段
	 */
	protected static $period = 300;

	/**
	 *在规定时间段最多错误次数
	 */
	protected static $max_failures = 3;

	/**
	 *锁定时间
	 */
	protected static $lock_time = 1800;

	/**
	 *用户 ID
	 */
	protected $user_id;

	/**
	 *用户 log 数据
	 */
	protected $user_log = [];

	/**
	 *构造
	 */
	public function __construct($user_id) {
		$this->user_id  = $user_id;
		$user_log       = wnd_get_user_meta($this->user_id, static::$meta_key);
		$user_log       = is_array($user_log) ? $user_log : [];
		$this->user_log = array_merge(static::$default_user_log, $user_log);
	}

	/**
	 *@since 0.8.61
	 *写入试错日志
	 * - 拦截时间范围内，新增统计次数
	 * - 拦截时间范围外，初始化日志信息：更新出错时间，统计次数为1
	 */
	public function write_failure_log() {
		if (time() - $this->user_log['failure_time'] < static::$period) {
			$this->user_log['failure_count']++;
		} else {
			$this->user_log['failure_time']  = time();
			$this->user_log['failure_count'] = 1;
		}

		wnd_update_user_meta($this->user_id, static::$meta_key, $this->user_log);

		// 抛出错误提示
		throw new Exception(sprintf(__('登录失败， %s 分钟剩余次数：%s', 'wnd'), ceil(static::$period / 60), static::$max_failures - $this->user_log['failure_count']));
	}

	/**
	 *@since 0.8.61
	 *清空试错日志
	 */
	public function reset_log() {
		wnd_delete_user_meta($this->user_id, static::$meta_key);
	}

	/**
	 *@since 0.8.61
	 *是否应该拦截此次登录尝试
	 */
	public function check_login() {
		// 错误次数未达到，无需拦截
		if ($this->user_log['failure_count'] < static::$max_failures) {
			return false;
		}

		// 锁定时间范围内:
		$time = static::$lock_time - (time() - $this->user_log['failure_time']);
		if ($time > 0) {
			throw new Exception(sprintf(__('错误次数太多请 %s 分钟后重试，或立即重置密码', 'wnd'), ceil($time / 60)));
		}
	}
}
