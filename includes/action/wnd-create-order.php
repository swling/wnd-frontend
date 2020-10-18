<?php
namespace Wnd\Action;

use Exception;
use Wnd\Model\Wnd_Order;
use Wnd\Model\Wnd_Product;

/**
 *@since 2019.10.02
 *创建站内订单
 *@param $post_id  Post ID
 */
class Wnd_Create_Order extends Wnd_Action_Ajax {

	public function execute(int $post_id = 0): array{
		if (!$post_id) {
			$post_id = $this->data['post_id'] ?? 0;
		}

		/**
		 *@since 0.8.76
		 *新增 SKU ID
		 *
		 *新增商品数目 quantity
		 */
		$sku_id   = $this->data[Wnd_Product::$sku_key] ?? '';
		$quantity = $this->data[Wnd_Product::$quantity_key] ?? 1;

		/**
		 *权限检测
		 */
		static::check_create($post_id, $sku_id, $quantity, false);

		// 写入消费数据
		$order = new Wnd_Order();
		$order->set_object_id($post_id);
		$order->set_quantity($quantity);
		$order->set_subject(get_the_title($post_id));
		$order->set_props($this->data);
		$order_post = $order->create(true);

		/**
		 *订单创建成功返回信息
		 *@since 0.8.71 新增 apply_filters('wnd_create_order_return', $return_array, $post_id);
		 */
		$return_array = ['status' => 3, 'msg' => __('支付成功', 'wnd'), 'data' => ['redirect_to' => get_permalink($post_id)]];
		return apply_filters('wnd_create_order_return', $return_array, $order_post);
	}

	/**
	 *检测下单权限
	 *
	 *@since 0.8.76
	 *新增 SKU ID
	 *
	 *@param int 	$post_id 		对应产品 ID
	 *@param string $sku_id 		产品 SKU ID
	 *@param int 	$quantity 		采购数量
	 *@param bool 	$online_payment 是否为在线支付
	 */
	public static function check_create(int $post_id, string $sku_id, int $quantity, bool $online_payment) {
		if ($quantity <= 0) {
			throw new Exception(__('订单 Quantity 无效', 'wnd'));
		}

		$post    = $post_id ? get_post($post_id) : false;
		$user_id = get_current_user_id();
		if (!$post) {
			throw new Exception(__('ID无效', 'wnd'));
		}

		if (!$user_id and !wnd_get_config('enable_anon_order')) {
			throw new Exception(__('请登录', 'wnd'));
		}

		/**
		 *库存检测
		 */
		if ($sku_id) {
			$single_sku_stock = Wnd_Product::get_single_sku_stock($post_id, $sku_id);
			if (-1 != $single_sku_stock and $quantity > $single_sku_stock) {
				throw new Exception(__('产品库存不足', 'wnd'));
			}
		}

		// Filter
		$wnd_can_create_order = apply_filters('wnd_can_create_order', ['status' => 1, 'msg' => ''], $post_id, $sku_id, $quantity);
		if (0 === $wnd_can_create_order['status']) {
			throw new Exception($wnd_can_create_order['msg']);
		}

		// 在线支付无需检测余额
		if ($online_payment) {
			return true;
		}

		// 余额检测
		$post_price   = wnd_get_post_price($post_id, $sku_id);
		$total_amount = $post_price * $quantity;
		$user_money   = wnd_get_user_money($user_id);
		if ($total_amount > $user_money) {
			$msg = '<p>' . __('当前余额：¥ ', 'wnd') . '<b>' . number_format($user_money, 2, '.', '') . '</b>&nbsp;&nbsp;' . __('本次消费：¥ ', 'wnd') . '<b>' . number_format($total_amount, 2, '.', '') . '</b></p>';

			throw new Exception($msg);
		}
	}
}
