<?php
namespace Wnd\Action\Common;

use Exception;
use Wnd\Action\Wnd_Action;
use Wnd\Getway\Wnd_Payment;
use Wnd\Model\Wnd_Order_Props;
use Wnd\Model\Wnd_SKU;
use Wnd\Model\Wnd_Transaction;

/**
 * 统一支付操作类
 * @since 0.9.36
 */
class Wnd_Do_Payment extends Wnd_Action {

	private $post_id;
	private $sku_id;
	private $quantity;
	private $type;

	private $internal;
	private $total_amount;
	private $payment_gateway;
	private $subject;
	private $app_id;

	protected function execute(): array{
		// 定义是否为站内交易
		$this->internal = 'internal' == strtolower($this->payment_gateway);

		// 写入交易记录
		$transaction = Wnd_Transaction::get_instance($this->type);
		$transaction->set_payment_gateway($this->payment_gateway);
		$transaction->set_object_id($this->post_id);
		$transaction->set_quantity($this->quantity);
		$transaction->set_total_amount($this->total_amount);
		$transaction->set_props($this->data);
		$transaction->set_subject($this->subject);
		$transaction_post = $transaction->create($this->internal);

		/**
		 * 站内交易订单
		 * @since 0.8.71 新增 apply_filters('wnd_internal_payment_return', $return_array, $post_id);
		 */
		if ($this->internal) {
			$return_array = ['status' => 3, 'msg' => __('支付成功', 'wnd'), 'data' => ['redirect_to' => get_permalink($this->post_id)]];
			return apply_filters('wnd_internal_payment_return', $return_array, $transaction_post);
		}

		/**
		 * 第三方支付平台
		 * - 设定 App ID 时，应该直接返回json转换后的数组
		 * - 站内 web 支付，响应对应的 HTML 数组
		 * @since 0.9.32
		 */
		$payment = Wnd_Payment::get_instance($transaction, $this->app_id);
		$result  = $payment->build_interface();
		if ($this->app_id) {
			$result = json_decode($result, true) ?: [];
		} else {
			$result = ['status' => 7, 'data' => '<div class="has-text-centered">' . $payment->build_interface() . '</div>'];
		}
		return $result;
	}

	/**
	 * 检测下单权限
	 *
	 * 新增 SKU ID
	 * @since 0.8.76
	 *
	 * @param bool 	$online_payment 是否为在线支付
	 */
	protected function check() {
		// 解析数据
		$this->parse_data();

		// 通用检测
		if (!$this->payment_gateway) {
			throw new Exception(__('未定义支付方式', 'wnd'));
		}

		// 区别检测产品订单与非产品订单
		if ($this->post_id) {
			$this->check_product_payment();
		} else {
			$this->check_none_product_payment();
		}

		// 站内消费检测余额
		if ($this->internal) {
			$this->check_internal_payment();
		}

		// Filter
		$can_do_payment = apply_filters('wnd_can_do_payment', ['status' => 1], $this->post_id, $this->type, $this->sku_id, $this->quantity);
		if (0 === $can_do_payment['status']) {
			throw new Exception($can_do_payment['msg']);
		}
	}

	/**
	 * 解析交易数据
	 * @since 0.9.36
	 */
	private function parse_data() {
		// 基本数据
		$this->post_id  = $this->data['post_id'] ?? 0;
		$this->sku_id   = $this->data[Wnd_Order_Props::$sku_id_key] ?? '';
		$this->quantity = $this->data[Wnd_Order_Props::$quantity_key] ?? 1;
		$this->type     = $this->data['type'] ?? ($this->post_id ? 'order' : 'recharge');

		// 在线支付数据
		$total_amount          = $this->data['total_amount'] ?? 0;
		$custom_total_amount   = $this->data['custom_total_amount'] ?? 0;
		$this->total_amount    = (float) ($custom_total_amount ?: $total_amount);
		$this->payment_gateway = $this->data['payment_gateway'] ?? '';
		$this->subject         = $this->data['subject'] ?? '';

		/**
		 * 设置当前付款的 App ID. 如微信小程序、公众号支付，通常与站内微信支付 AppID 不同.
		 * @since 0.9.56.6
		 */
		$this->app_id = $this->data['app_id'] ?? '';
	}

	/**
	 * 和产品关联的交易
	 * - 典型场景如各类针对特定商品的购买订单
	 * - 反之如余额充值类，则为非产品订单
	 */
	private function check_product_payment() {
		// 订单属性检测
		if ($this->quantity <= 0) {
			throw new Exception(__('订单 Quantity 无效', 'wnd'));
		}

		$post = get_post($this->post_id);
		if (!$post) {
			throw new Exception(__('ID无效', 'wnd'));
		}

		/**
		 * 库存检测
		 */
		if ($this->sku_id) {
			$single_sku_stock = Wnd_SKU::get_single_sku_stock($this->post_id, $this->sku_id);
			if (-1 != $single_sku_stock and $this->quantity > $single_sku_stock) {
				throw new Exception(__('产品库存不足', 'wnd'));
			}
		}
	}

	/**
	 * 非产品交易
	 * - 典型如余额充值
	 *
	 */
	private function check_none_product_payment() {
		if (!$this->total_amount) {
			throw new Exception(__('获取金额失败', 'wnd'));
		}
	}

	/**
	 * 站内交易检测
	 * - 余额检测
	 *
	 */
	private function check_internal_payment() {
		// 站内交易余额检测
		$post_price   = wnd_get_post_price($this->post_id, $this->sku_id);
		$total_amount = $post_price * $this->quantity;
		$user_money   = wnd_get_user_balance($this->user_id);
		if ($total_amount > $user_money) {
			$msg = '<p>' . __('当前余额：¥ ', 'wnd') . '<b>' . number_format($user_money, 2, '.', '') . '</b>&nbsp;&nbsp;' . __('本次消费：¥ ', 'wnd') . '<b>' . number_format($total_amount, 2, '.', '') . '</b></p>';
			throw new Exception($msg);
		}
	}
}
