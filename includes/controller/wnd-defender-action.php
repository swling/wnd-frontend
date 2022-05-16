<?php
namespace Wnd\Controller;

use Exception;
use Wnd\Action\Wnd_Action;

/**
 * # 操作防护
 * 需要开启 WP 对象缓存
 *
 * ## 数据格式
 * $actions_log = [
 * 		'$this->action'=>[
 * 			'time'=>'',
 * 			'count'=>''
 * 		]
 * ];
 *
 * - 已登录用户按 user ID 缓存
 * - 未登录用户按 IP地址 缓存
 * @since 0.9.50
 */
class Wnd_Defender_Action {

	// 本次 Action 是否在防护操作中
	protected $should_be_defend;

	/**
	 * Wnd User Meta Key
	 */
	protected static $meta_key = 'action_log';

	/**
	 * 默认 log 数据
	 */
	protected static $default_action_log = [
		'time'  => 0,
		'count' => 0,
	];

	/**
	 * 拦截计数时间段
	 */
	protected $period;

	/**
	 * 在规定时间段最多错误次数
	 */
	protected $max_actions;

	/**
	 * 针对当前用户的拦截标识
	 * - 已登录用户为用户 ID
	 * - 未登录用户基于 IP地址
	 * **/
	protected $cache_key;

	/**
	 * 当前 Action 操作标识
	 */
	protected $action;

	/**
	 * 全部 Actions log 数据
	 */
	protected $actions_log = [];

	/**
	 * 当前 Action log 数据
	 */
	protected $action_log = [];

	/**
	 * 构造
	 */
	public function __construct(Wnd_Action $wnd_action) {
		// 过滤钩子：可通过本过滤器，修改对应 Action 的拦截策略
		$defend_args = [
			'period'      => $wnd_action->period,
			'max_actions' => $wnd_action->max_actions,
		];
		$defend_args = apply_filters('wnd_action_defend_args', $defend_args, $wnd_action);
		extract($defend_args);

		$this->period           = $period;
		$this->max_actions      = $max_actions;
		$this->should_be_defend = ($max_actions and $period);
		$this->action           = $wnd_action::get_class_name();
		$this->cache_key        = get_current_user_id() ?: wnd_get_user_ip();
		$this->actions_log      = $this->get_actions_log();
		$this->action_log       = $this->get_action_log();

		// 清理历史数据
		static::_temp_clean_up_historical_data();
	}

	private function get_actions_log(): array{
		$actions_log = wp_cache_get($this->cache_key, static::$meta_key);
		$actions_log = is_array($actions_log) ? $actions_log : [];

		// 清理超期的日志
		foreach ($actions_log as $action => $log) {
			if (time() - $log['time'] > $this->period) {
				unset($actions_log[$action]);
			}

		}

		return $actions_log;
	}

	private function get_action_log(): array{
		$action_log = $this->actions_log[$this->action] ?? [];
		return array_merge(static::$default_action_log, $action_log);
	}

	/**
	 * 是否应该拦截此次操作
	 */
	public function defend_action() {
		if (!$this->should_be_defend) {
			return false;
		}

		// 错误次数未达到，无需拦截
		if ($this->action_log['count'] < $this->max_actions) {
			return false;
		}

		// 锁定时间范围内:
		$time = $this->period - (time() - $this->action_log['time']);
		if ($time > 0) {
			throw new Exception(sprintf(__('操作太频繁，请 %s 分钟后重试', 'wnd'), ceil($time / 60)));
		}
	}

	/**
	 * 写入操作执行日志
	 * - 拦截时间范围内，新增统计次数
	 * - 拦截时间范围外，初始化日志信息：更新出错时间，统计次数为1
	 */
	public function write_log() {
		if (!$this->should_be_defend) {
			return false;
		}

		if (time() - $this->action_log['time'] < $this->period) {
			$this->action_log['count']++;
		} else {
			$this->action_log['time']  = time();
			$this->action_log['count'] = 1;
		}

		$this->actions_log[$this->action] = $this->action_log;

		wp_cache_set($this->cache_key, $this->actions_log, static::$meta_key, $this->period);
	}

	/**
	 * 清空日志
	 */
	public function reset_log() {
		wp_cache_delete($this->cache_key, static::$meta_key);
	}

	/**
	 * 废弃的 action 拦截日志（转入对象缓存拦截）
	 * 后期可移除本代码
	 */
	private static function _temp_clean_up_historical_data() {
		$user_id = get_current_user_id();
		if ($user_id and wnd_get_user_meta($user_id, 'action_log')) {
			wnd_delete_user_meta($user_id, 'action_log');
		}
	}

}
