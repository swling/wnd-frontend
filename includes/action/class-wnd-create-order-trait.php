<?php
namespace Wnd\Action;

use Exception;

/**
 *@since 2019.11.26
 *
 *创建订单特性
 */
trait Wnd_Create_Order_Trait {

	public static function check_create($post_id, $user_id) {
		if (!$post_id) {
			throw new Exception('ID无效！');
		}

		if (!$user_id) {
			throw new Exception('用户无效！');
		}

		$post_price = wnd_get_post_price($post_id);
		$user_money = wnd_get_user_money($user_id);

		// 余额不足
		if ($post_price > $user_money) {
			$msg = '<h3>当前余额：¥' . $user_money . ' 本次消费：¥' . $post_price . '</h3>';
			if (wnd_get_option('wnd', 'wnd_alipay_appid')) {
				$msg .= '<a href="' . wnd_order_link($post_id) . '">在线支付</a> | ';
			}
			$msg .= '<a onclick="wnd_ajax_modal(\'wnd_user_recharge_form\')">余额充值</a>';

			throw new Exception($msg);
		}
	}
}
