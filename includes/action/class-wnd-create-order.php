<?php
namespace Wnd\Action;

use Exception;
use Wnd\Action\Wnd_Create_Order_Trait;
use Wnd\Model\Wnd_Order;
use Wnd\Model\Wnd_Recharge;

/**
 *@since 2019.10.02
 *创建订单
 *@param $_POST['post_id']  Post ID
 */
class Wnd_Create_Order extends Wnd_Action_Ajax {

	public static function execute($post_id = 0): array{
		$post_id = $post_id ?: ($_POST['post_id'] ?? 0);
		$post    = $post_id ? get_post($post_id) : false;
		$user_id = get_current_user_id();
		if (!$post) {
			return ['status' => 0, 'msg' => __('ID无效', 'wnd')];
		}

		$wnd_can_create_order = apply_filters('wnd_can_create_order', ['status' => 1, 'msg' => ''], $post_id);
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

		// 文章作者新增资金
		$commission = wnd_get_post_commission($post_id);
		if ($commission) {
			try {
				$recharge = new Wnd_Recharge();
				$recharge->set_object_id($post->ID); // 设置佣金来源
				$recharge->set_user_id($post->post_author);
				$recharge->set_total_amount($commission);
				$recharge->create(true); // 直接写入余额
			} catch (Exception $e) {
				return ['status' => 1, 'msg' => $e->getMessage()];
			}
		}

		// 支付成功
		return ['status' => 1, 'msg' => __('支付成功', 'wnd')];
	}
}
