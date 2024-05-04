<?php
namespace Wnd\WPDB;

use Wnd\Utility\Wnd_Singleton_Trait;
use Wnd\WPDB\WPDB_Row;

/**
 * 自定义用户表及其他用户常用方法
 * @since 2019.10.25
 */
class Wnd_User extends WPDB_Row {

	protected $table_name        = 'wnd_users';
	protected $object_name       = 'wnd_user';
	protected $primary_id_column = 'ID';
	protected $required_columns  = ['user_id'];

	protected $object_cache_fields = ['user_id'];

	/**
	 * 单例模式
	 * @since 0.9.59
	 */
	use Wnd_Singleton_Trait;

	private function __construct() {
		parent::__construct();
	}

	/**
	 * 获取自定义用户对象
	 * - Users 主要数据：balance、last_login、client_ip
	 * @since 2019.11.06
	 */
	public static function get_wnd_user(int $user_id): object {
		$instance = new static();
		return $instance->get_by('user_id', $user_id) ?: new \stdClass;
	}

	/**
	 * 更新自定义用户对象
	 * - Users 主要数据：balance、last_login、client_ip
	 * @since 2019.11.06
	 */
	public static function update_wnd_user(int $user_id, array $data): bool {
		$instance = new static();
		$user     = (array) static::get_wnd_user($user_id);

		if ($user) {
			$data = array_merge($user, $data);
			return $instance->update($data, ['user_id' => $user_id]);
		} else {
			$defaults = ['user_id' => $user_id, 'balance' => 0, 'last_login' => '', 'login_count' => '', 'last_recall' => '', 'client_ip' => ''];
			$data     = array_merge($defaults, $data);
			return $instance->insert($data);
		}
	}

	public static function inc_user_balance(int $user_id, float $amout) {
		$instance = new static();
		return $instance->inc(['user_id' => $user_id], 'balance', $amout);
	}

	public static function inc_user_expense(int $user_id, float $amout) {
		$instance = new static();
		return $instance->inc(['user_id' => $user_id], 'expense', $amout);
	}

	/**
	 * 删除记录
	 * @since 0.9.57
	 */
	public static function delete_wnd_user(int $user_id): int {
		$instance = new static();
		return $instance->delete_by('user_id', $user_id);
	}

	/**
	 * 记录登录日志
	 * @since 0.9.57
	 */
	public static function write_login_log(): bool {
		$user_id = get_current_user_id();
		if (!$user_id) {
			return false;
		}

		$db_records  = static::get_wnd_user($user_id);
		$last_login  = $db_records->last_login ?? 0;
		$login_count = $db_records->login_count ?? 0;
		if ($last_login) {
			$last_login = date('Y-m-d', wnd_time_to_local($last_login));
			if ($last_login == wnd_date('Y-m-d')) {
				return false;
			}
		}

		// 未设置登录时间/注册后未登录
		static::update_wnd_user($user_id, ['last_login' => time(), 'login_count' => $login_count + 1, 'client_ip' => wnd_get_user_ip()]);
		return true;
	}

	/**
	 * 判断用户是否注册后首次登陆
	 * 使用该判断方法，需要注意代码执行顺序，即：需要在首次写入登陆日志之前判断
	 * @see Wnd\Hook\Wnd_Add_Action_WP::action_on_wp_loaded
	 *
	 * @since 0.9.69.7
	 */
	public static function is_first_login(int $user_id = 0): bool {
		$user_id = $user_id ?: get_current_user_id();
		if (!$user_id) {
			return false;
		}

		$db_records  = static::get_wnd_user($user_id);
		$login_count = $db_records->login_count ?? 0;

		return $login_count < 1;
	}

	/**
	 * 获取长期未登录的睡眠账户
	 * @since 0.9.57
	 */
	public static function get_sleep_users(int $day): array {
		global $wpdb;
		$timestamp = time() - (3600 * 24 * $day);
		$results   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->wnd_users WHERE last_login < %d",
				$timestamp
			)
		);

		return $results ?: [];
	}

}
