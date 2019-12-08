<?php
namespace Wnd\Action;

use Exception;
use Wnd\Action\Wnd_Create_Order;
use Wnd\Model\Wnd_Recharge;

/**
 *付费阅读类
 *@param $_POST['post_id']  Post ID
 */
class Wnd_Pay_For_Reading extends Wnd_Action_Ajax {

	public static function execute(): array{
		$post_id = (int) $_POST['post_id'];
		$post    = get_post($post_id);
		$user_id = get_current_user_id();

		//查找是否有more标签，否则免费部分为空（全文付费）
		$content_array = explode('<!--more-->', $post->post_content, 2);
		if (1 == count($content_array)) {
			$content_array = array('', $post->post_content);
		}
		list($free_content, $paid_content) = $content_array;
		if (!$paid_content) {
			return array('status' => 0, 'msg' => '获取付费内容出错');
		}

		//1、已付费
		if (wnd_user_has_paid($user_id, $post_id)) {
			return array('status' => 0, 'msg' => '请勿重复购买');
		}

		// 2、支付失败
		$order = Wnd_Create_Order::execute();
		if ($order['status'] === 0) {
			return $order;
		}

		// 文章作者新增资金
		$commission = wnd_get_post_commission($post_id);
		if ($commission) {
			try {
				$recharge = new Wnd_Recharge();
				$recharge->set_object_id($post->ID); // 设置充值来源
				$recharge->set_user_id($post->post_author);
				$recharge->set_total_amount($commission);
				$recharge->create(true); // 直接写入余额
			} catch (Exception $e) {
				return array('status' => 0, 'msg' => $e->getMessage());
			}
		}

		return array('status' => 1, 'msg' => $paid_content);
	}
}
