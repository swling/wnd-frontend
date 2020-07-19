<?php
namespace Wnd\Action;

use Exception;
use Wnd\Model\Wnd_Order;

/**
 *@since 2019.10.02
 *创建订单
 *@param $_POST['post_id']  Post ID
 */
class Wnd_Create_Order extends Wnd_Action_Ajax {

	public static function execute(int $post_id = 0): array{
		$post_id = (int) ($post_id ?: $_POST['post_id']);
		$user_id = get_current_user_id();

		$wnd_can_create_order = apply_filters('wnd_can_create_order', ['status' => 1, 'msg' => ''], $post_id);
		if ($wnd_can_create_order['status'] === 0) {
			return $wnd_can_create_order;
		}

		// 写入消费数据
		try {
			static::check_create($post_id, $user_id);

			$order = new Wnd_Order();
			$order->set_object_id($post_id);
			$order->set_subject(get_the_title($post_id));
			$order->create($is_success = true);
		} catch (Exception $e) {
			return ['status' => 0, 'msg' => $e->getMessage()];
		}

		// 支付成功
		return ['status' => 1, 'msg' => __('支付成功', 'wnd')];
	}

	/**
	 *检测下单权限
	 */
	public static function check_create(int $post_id, int $user_id) {
		$post = $post_id ? get_post($post_id) : false;
		if (!$post) {
			return ['status' => 0, 'msg' => __('ID无效', 'wnd')];
		}

		if ($post->post_author == $user_id) {
			return ['status' => 0, 'msg' => __('禁止下单', 'wnd')];
		}

		if (!$user_id and !wnd_get_config('enable_anon_order')) {
			throw new Exception(__('请登录', 'wnd'));
		}

		$post_price    = wnd_get_post_price($post_id);
		$user_money    = wnd_get_user_money($user_id);
		$primary_color = 'is-' . wnd_get_config('primary_color');
		$second_color  = 'is-' . wnd_get_config('second_color');

		// 余额不足
		if ($post_price > $user_money) {
			$msg = '<p>' . __('当前余额：¥ ', 'wnd') . '<b>' . number_format($user_money, 2, '.', '') . '</b>&nbsp;&nbsp;' . __('本次消费：¥ ', 'wnd') . '<b>' . number_format($post_price, 2, '.', '') . '</b></p>';
			if (wnd_get_config('alipay_appid')) {
				$msg .= wnd_modal_button(__('在线支付', 'wnd'), 'wnd_order_form', $post_id, $primary_color);
				$msg .= '&nbsp;&nbsp;';
			}
			$msg .= wnd_modal_button(__('余额充值', 'wnd'), 'wnd_user_recharge_form', '', $second_color . ' is-outlined');

			throw new Exception($msg);
		}
	}
}
