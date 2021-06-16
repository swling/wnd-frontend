<?php
namespace Wnd\Action;

use Exception;
use Wnd\Action\Wnd_Create_Order;
use Wnd\Getway\Wnd_Payment;
use Wnd\Model\Wnd_Order_Product;
use Wnd\Model\Wnd_Transaction;

/**
 * Ajax 创建第三方支付
 * @since 2020.06.19
 */
class Wnd_Do_Pay extends Wnd_Action {

	public function execute(): array{
		$post_id         = (int) ($this->data['post_id'] ?? 0);
		$total_amount    = (float) ($this->data['total_amount'] ?? 0);
		$payment_gateway = $this->data['payment_gateway'] ?? '';
		$subject         = $this->data['subject'] ?? '';
		$sku_id          = $this->data[Wnd_Order_Product::$sku_id_key] ?? '';
		$quantity        = $this->data[Wnd_Order_Product::$quantity_key] ?? 1;
		$type            = $this->data['type'] ?? ($post_id ? 'order' : 'recharge');

		if (!$payment_gateway) {
			throw new Exception(__('未定义支付方式', 'wnd'));
		}

		/**
		 * 当设置 $post_id 表征改支付为在线支付订单，需同步设置权限检测
		 * @since 0.8.69
		 */
		if ($post_id) {
			Wnd_Create_Order::check_create($post_id, $sku_id, $quantity, true);
		}

		/**
		 * 拆分站内数据创建于第三方支付构造
		 * 写入站内支付数据
		 * @since 0.9.32
		 */
		$transaction = Wnd_Transaction::get_instance($type);
		$transaction->set_payment_gateway($payment_gateway);
		$transaction->set_object_id($post_id);
		$transaction->set_quantity($quantity);
		$transaction->set_total_amount($total_amount);
		$transaction->set_props($this->data);
		$transaction->set_subject($subject);
		$transaction->create(false);

		/**
		 * 拆分站内数据创建于第三方支付构造
		 * 构建第三方支付
		 * @since 0.9.32
		 */
		$payment = Wnd_Payment::get_instance($transaction);

		// Ajax 提交时，需将提交响应返回，并替换用户UI界面，故需设置 ['status' => 7];
		return ['status' => 7, 'data' => '<div class="has-text-centered">' . $payment->build_interface() . '</div>'];
	}
}
