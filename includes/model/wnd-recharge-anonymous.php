<?php
namespace Wnd\Model;

use Exception;
use Wnd\Model\Wnd_Finance;
use Wnd\Model\Wnd_Order_Props;
use Wnd\Model\Wnd_Transaction_Anonymous;

/**
 * 匿名小额充值模块
 * - 充值金额：total_amount
 * - 消费金额：props->used_amount
 *
 * 注意：匿名充值后，如果再次充值，会覆盖掉之前的订单，用户余额将以最新订单为准
 *
 * @since 0.9.57.10
 */
class Wnd_Recharge_Anonymous extends Wnd_Recharge {

	/**
	 * 检测创建权限
	 */
	protected function check_create() {
		if (!wnd_get_config('enable_anon_order')) {
			throw new Exception('Anonymous orders are not enabled.');
		}

		if (wnd_get_anon_user_balance() > 0) {
			throw new Exception(__('匿名用户：需清空当前余额后，方可再次充值', 'wnd'));
		}
	}

	/**
	 * 匿名订单处理
	 * - 将匿名订单 cookie 设置为订单 $this->transaction_slug
	 * - 设置匿名订单 cookie
	 * - 调用父类同名方法
	 */
	protected function generate_transaction_data() {
		$this->transaction_slug = md5(uniqid($this->object_id));
		Wnd_Transaction_Anonymous::set_anon_cookie($this->transaction_type, $this->object_id, $this->transaction_slug);

		parent::generate_transaction_data();
	}

	/**
	 * 匿名用户余额 = 充值金额 - 消费金额
	 *
	 */
	public static function get_anon_user_balance(): float {
		$recharge = static::get_anon_recharge();
		if (!$recharge) {
			return 0.00;
		}

		if (time() - $recharge->time < 3600 * 24) {
			return $recharge->total_amount - ((float) static::get_used_amount($recharge));
		} else {
			return 0.00;
		}
	}

	/**
	 * 更新匿名用户余额消费
	 *
	 */
	public static function inc_anon_user_expense(float $amount): bool {
		$recharge = static::get_anon_recharge();
		if (!$recharge) {
			return false;
		}

		$used_amount = static::get_used_amount($recharge);
		$used_amount = $used_amount + $amount;
		$action      = Wnd_Order_Props::update_order_props($recharge->ID, ['used_amount' => $used_amount]);

		Wnd_Finance::update_fin_stats($amount, 'expense');
		return $action;
	}

	/**
	 * 获取匿名用户充值订单
	 *
	 */
	private static function get_anon_recharge(): mixed {
		$transaction_slug = Wnd_Transaction_Anonymous::get_anon_cookie_value('recharge', 0);
		if (!$transaction_slug) {
			return false;
		}

		$recharge = static::query_db(['slug' => $transaction_slug]);
		if (!$recharge) {
			return false;
		}

		if ($recharge->status != static::$completed_status or 'recharge' != $recharge->type) {
			return false;
		}

		return $recharge;
	}

	private static function get_used_amount($recharge): float {
		$props = json_decode($recharge->props);
		return (float) ($props->used_amount ?? 0);
	}

}
