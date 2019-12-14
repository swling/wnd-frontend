<?php
namespace Wnd\Action;

use Exception;
use Wnd\Action\Wnd_Create_Order_Trait;
use Wnd\Model\Wnd_Order;

/**
 *@since 2019.10.02
 *创建订单
 *@param $_POST['post_id']  Post ID
 */
class Wnd_Create_Order extends Wnd_Action_Ajax {

	public static function execute(): array{
		$post_id = (int) $_POST['post_id'];
		$user_id = get_current_user_id();

		$wnd_can_create_order = apply_filters('wnd_can_create_order', ['status' => 1, 'msg' => '默认通过'], $post_id);
		if ($wnd_can_create_order['status'] === 0) {
			return $wnd_can_create_order;
		}

		// 写入消费数据
		try {
			Wnd_Create_Order_Trait::check_create($post_id, $user_id);

			$order = new Wnd_Order();
			$order->set_object_id($post_id);
			$order->set_subject(get_the_title($post_id));
			$order->create($is_success = true);
		} catch (Exception $e) {
			return ['status' => 0, 'msg' => $e->getMessage()];
		}

		// 支付成功
		return ['status' => 1, 'msg' => '支付成功'];
	}
}
