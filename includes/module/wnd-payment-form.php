<?php
namespace Wnd\Module;

use Wnd\Model\Wnd_Order_Product;
use Wnd\Model\Wnd_Payment_Getway;
use Wnd\Model\Wnd_SKU;
use Wnd\Utility\Wnd_Request;
use Wnd\View\Wnd_Form_WP;

/**
 *@since 2020.06.30
 *在线支付订单表单
 *匿名支付订单默认启用人机验证
 */
class Wnd_Payment_Form extends Wnd_Module {

	protected $type = 'form';

	// HTML 输出
	protected static function build($args = []): string {
		return static::configure_form($args)->build();
	}

	// 结构输出 JavaScript 渲染
	protected function structure(): array{
		return static::configure_form($this->args)->get_structure();
	}

	// 配置表单
	protected static function configure_form(array $args): Wnd_Form_WP{
		/**
		 *订单基本信息 + 产品属性等参数
		 *
		 *构造：产品ID，SKU ID，数量，总金额，订单标题，SKU 提示信息
		 * 		$post_id, $sku_id, $quantity, $total_amount, $title，$sku_info
		 */
		extract(static::get_payment_props($args));

		/**
		 *基础信息
		 */
		$user_id         = get_current_user_id();
		$gateway_options = Wnd_Payment_Getway::get_gateway_options();
		$user_money      = wnd_get_user_money($user_id);

		// 消费提示
		$message = $user_id ? __('当前余额：¥ ', 'wnd') . '<b>' . number_format($user_money, 2, '.', '') . '</b>&nbsp;&nbsp;' : '';
		$message .= __('本次消费：¥ ', 'wnd') . '<b>' . number_format($total_amount, 2, '.', '') . '</b>';

		/**
		 *支付表单
		 *
		 * - 如果余额足够，提供站内支付结算方式
		 */
		if ($user_money >= $total_amount) {
			$gateway_options = array_merge([__('余额支付', 'wnd') => 'internal'], $gateway_options);
		}

		$form = new Wnd_Form_WP(true, !$user_id);
		$form->set_form_title($title, true);
		if (!$user_id) {
			$form->add_html(wnd_notification(__('您当前尚未登录，匿名订单仅24小时有效，请悉知！', 'wnd')));
		}
		$form->add_html($sku_info);
		$form->add_html('<div class="has-text-centered field">');
		$form->add_html('<p>' . $message . '</p>');

		if ($total_amount > 0) {
			$form->add_radio(
				[
					'name'     => 'payment_gateway',
					'options'  => $gateway_options,
					'required' => 'required',
					'checked'  => $user_money >= $total_amount ? 'internal' : Wnd_Payment_Getway::get_default_gateway(),
					'class'    => 'is-checkradio is-danger',
				]
			);
		} else {
			$form->add_hidden('payment_gateway', 'internal');
		}

		$form->add_checkbox(
			[
				'name'     => 'agreement',
				'options'  => [__('已阅读并同意交易协议及产品使用协议') => 1],
				'checked'  => true,
				'required' => 'required',
			]
		);
		$form->add_html('</div>');
		$form->set_route('action', 'wnd_pay_for_order');

		/**
		 *遍历参数信息并构建表单字段
		 */
		foreach ($args as $key => $value) {
			$form->add_hidden($key, $value);
		}

		$form->set_submit_button(__('确定', 'wnd'));
		return $form;
	}

	/**
	 *@since 0.8.76
	 *根据参数读取本次订单对应产品信息
	 *构造：产品ID，SKU ID，数量，总金额，订单标题，SKU 提示信息
	 */
	protected static function get_payment_props(array $args): array{
		// 将数组元素依次定义为按键名命名的变量
		$defaults = [
			'post_id'  => 0,
			'quantity' => $args[Wnd_Order_Product::$quantity_key] ?? 1,
			'sku_id'   => $args[Wnd_Order_Product::$sku_id_key] ?? '',
		];
		unset($args[Wnd_Request::$sign_name]);
		$args = wp_parse_args($args, $defaults);
		extract($args);

		$sku          = Wnd_SKU::get_single_sku($post_id, $sku_id);
		$sku_name     = Wnd_SKU::get_single_sku_name($post_id, $sku_id);
		$title        = $sku_name ? get_the_title($post_id) . '&nbsp;[' . $sku_name . ' x ' . $quantity . ']' : get_the_title($post_id) . '&nbsp;[ x ' . $quantity . ']';
		$post_price   = wnd_get_post_price($post_id, $sku_id);
		$total_amount = $post_price * $quantity;

		// 列出产品属性提示信息
		$sku_info = '';
		$sku_keys = Wnd_SKU::get_sku_keys(get_post_type($post_id));
		foreach ($sku as $key => $value) {
			$key = $sku_keys[$key] ?? $key;
			$sku_info .= '[ ' . $key . ' : ' . $value . ' ]&nbsp;';
		}
		$sku_info = wnd_notification($sku_info);

		/**
		 *构造：产品ID，SKU ID，数量，总金额，订单标题，SKU 提示信息
		 */
		return compact('post_id', 'sku_id', 'quantity', 'total_amount', 'title', 'sku_info');
	}
}
