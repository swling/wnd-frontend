<?php
namespace Wnd\Controller;

use Wnd\Model\Wnd_Order;
use \Exception;

/**
 *@since 2019.10.02
 * 订单穿件类
 */
class Wnd_Create_Order extends Wnd_Ajax_Controller {

	public static function execute() {
		$post_id = (int) $_POST['post_id'];
		if (!$post_id) {
			return array('status' => 0, 'msg' => 'ID无效！');
		}

		$user_id = get_current_user_id();
		if (!$user_id) {
			return array('status' => 0, 'msg' => '请登录！');
		}

		$wnd_can_create_order = apply_filters('wnd_can_create_order', array('status' => 1, 'msg' => '默认通过'), $post_id);
		if ($wnd_can_create_order['status'] === 0) {
			return $wnd_can_create_order;
		}

		// 余额不足
		if (wnd_get_post_price($post_id) > wnd_get_user_money($user_id)) {
			$msg = '余额不足：';
			if (wnd_get_option('wnd', 'wnd_alipay_appid')) {
				$msg .= '<a href="' . _wnd_order_link($post_id) . '">在线支付</a> | ';
			}
			$msg .= '<a onclick="wnd_ajax_modal(\'_wnd_recharge_form\')">余额充值</a>';

			return array('status' => 0, 'msg' => $msg);
		}

		// 写入消费数据
		try {
			$order = new Wnd_Order();
			$order->set_object_id($post_id);
			$order->set_subject(get_the_title($post_id));
			$order->create($is_success = true);
		} catch (Exception $e) {
			return array('status' => 0, 'msg' => $e->getMessage());
		}

		// 支付成功
		return array('status' => 1, 'msg' => '支付成功！');
	}
}
