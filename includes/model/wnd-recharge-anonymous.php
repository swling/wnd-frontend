<?php
namespace Wnd\Model;

use Exception;
use Wnd\Model\Wnd_Transaction_Anonymous;

/**
 * 匿名小额充值模块
 * - 充值金额：post_content
 * - 消费金额：post_content_filtered
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
	public static function get_anon_user_balance(): float{
		$recharge = static::get_anon_recharge();
		if (!$recharge) {
			return 0.00;
		}

		if (time() - strtotime($recharge->post_date_gmt) < 3600 * 24) {
			$transaction = Wnd_Transaction::get_instance('', $recharge->ID);
			return $transaction->get_total_amount() - ((float) $recharge->post_content_filtered);
		} else {
			return 0.00;
		}
	}

	/**
	 * 更新匿名用户余额消费
	 *
	 */
	public static function inc_anon_user_expense(float $amount): bool{
		$recharge = static::get_anon_recharge();
		if (!$recharge) {
			return false;
		}

		$new_consumption = ((float) $recharge->post_content_filtered) + $amount;
		$ID              = wp_update_post(['ID' => $recharge->ID, 'post_content_filtered' => $new_consumption]);

		return is_wp_error($ID) ? false : true;
	}

	/**
	 * 获取匿名用户充值订单
	 *
	 */
	private static function get_anon_recharge() {
		$transaction_slug = Wnd_Transaction_Anonymous::get_anon_cookie_value('recharge', 0);
		if (!$transaction_slug) {
			return false;
		}

		$recharge = wnd_get_post_by_slug($transaction_slug, 'recharge', [static::$completed_status, static::$processing_status]);
		return $recharge ?: false;
	}
}
