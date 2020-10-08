<?php
namespace Wnd\Action;

use Exception;
use Wnd\Model\Wnd_Order;

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

		$wnd_can_create_order = apply_filters('wnd_can_create_order', ['status' => 1, 'msg' => ''], $post_id);
		if (0 === $wnd_can_create_order['status']) {
			return $wnd_can_create_order;
		}

		// 写入消费数据
		static::check_create($post_id, $this->user_id);
		$order = new Wnd_Order();
		$order->set_object_id($post_id);
		$order->set_subject(get_the_title($post_id));
		$order->set_sku($this->data['sku'] ?? '');
		$order_post = $order->create(true);

		/**
		 *订单创建成功返回信息
		 *@since 0.8.71 新增 apply_filters('wnd_create_order_return', $return_array, $post_id);
		 */
		$return_array = ['status' => 4, 'msg' => __('支付成功', 'wnd'), 'data' => ['waiting' => 5]];
		return apply_filters('wnd_create_order_return', $return_array, $order_post);
	}

	/**
	 *检测下单权限
	 */
	public static function check_create(int $post_id, int $user_id) {
		$post = $post_id ? get_post($post_id) : false;
		if (!$post) {
			throw new Exception(__('ID无效', 'wnd'));
		}

		if (!$user_id and !wnd_get_config('enable_anon_order')) {
			throw new Exception(__('请登录', 'wnd'));
		}

		$post_price = wnd_get_post_price($post_id);
		$user_money = wnd_get_user_money($user_id);

		// 余额不足
		if ($post_price > $user_money) {
			$msg = '<p>' . __('当前余额：¥ ', 'wnd') . '<b>' . number_format($user_money, 2, '.', '') . '</b>&nbsp;&nbsp;' . __('本次消费：¥ ', 'wnd') . '<b>' . number_format($post_price, 2, '.', '') . '</b></p>';

			throw new Exception($msg);
		}
	}
}
