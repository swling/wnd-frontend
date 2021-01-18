<?php
namespace Wnd\Action;

use Exception;
use Wnd\Action\Wnd_Create_Order;
use Wnd\Model\Wnd_Order_Product;
use Wnd\Model\Wnd_Payment;

/**
 *Ajax创建支付
 *@since 2020.06.19
 *
 */
class Wnd_Do_Pay extends Wnd_Action {

	public function execute(): array{
		$post_id         = (int) ($this->data['post_id'] ?? 0);
		$total_amount    = (float) ($this->data['total_amount'] ?? 0);
		$payment_gateway = $this->data['payment_gateway'] ?? '';
		$subject         = $this->data['subject'] ?? '';
		$sku_id          = $this->data[Wnd_Order_Product::$sku_id_key] ?? '';
		$quantity        = $this->data[Wnd_Order_Product::$quantity_key] ?? 1;

		if (!$payment_gateway) {
			throw new Exception(__('未定义支付方式', 'wnd'));
		}

		/**
		 *@since 0.8.69
		 *当设置 $post_id 表征改支付为在线支付订单，需同步设置权限检测
		 */
		if ($post_id) {
			Wnd_Create_Order::check_create($post_id, $sku_id, $quantity, true);
		}

		$payment = Wnd_Payment::get_instance($payment_gateway);
		$payment->set_object_id($post_id);
		$payment->set_quantity($quantity);
		$payment->set_total_amount($total_amount);
		$payment->set_props($this->data);
		$payment->set_subject($subject);
		$payment->create(false);

		// Ajax 提交时，需将提交响应返回，并替换用户UI界面，故需设置 ['status' => 7];
		$interface = $payment->build_interface();
		return ['status' => 7, 'data' => '<div class="has-text-centered">' . $interface . '</div>'];
	}
}
