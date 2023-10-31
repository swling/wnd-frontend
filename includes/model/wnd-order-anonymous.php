<?php
namespace Wnd\Model;

use Exception;
use Wnd\Model\Wnd_Transaction_Anonymous;

/**
 * 匿名订单模块
 * @since 0.9.32
 */
class Wnd_Order_Anonymous extends Wnd_Order {

	/**
	 * 检测创建权限
	 * @since 0.9.51
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
	 *
	 * @since 0.9.32
	 */
	protected function generate_transaction_data() {
		$this->transaction_slug = md5(uniqid($this->object_id));
		Wnd_Transaction_Anonymous::set_anon_cookie($this->transaction_type, $this->object_id, $this->transaction_slug);

		parent::generate_transaction_data();
	}

	/**
	 * 查询订单是否已完成支付
	 * @since 0.9.32
	 *
	 * @return bool
	 */
	public static function has_paid(int $user_id, int $object_id): bool {
		return !empty(static::get_user_valid_orders($user_id, $object_id));
	}

	/**
	 * 查询匿名订单是否已完成支付
	 * @since 0.9.32
	 *
	 * @return array 有效订单的合集 [order_post_object]
	 */
	public static function get_user_valid_orders(int $user_id, int $object_id, int $limit = 1): array {
		$transaction_slug = Wnd_Transaction_Anonymous::get_anon_cookie_value('order', $object_id);
		if (!$transaction_slug) {
			return [];
		}

		$order = static::query_db(['slug' => $transaction_slug]) ?: [];
		if (!$order) {
			return [];
		}

		if ($order->status != static::$completed_status or 'order' != $order->type) {
			return [];
		}

		/**
		 * 必须检测订单是否与 object id 是否匹配。否则用户前端支付任意订单后，即可修改 cookie name 篡改任意 object id 的支付权限
		 * @since 0.9.32
		 */
		if ($order->object_id != $object_id) {
			return [];
		}

		if (time() - $order->time < 3600 * 24) {
			return [$order];
		} else {
			return [];
		}
	}
}
