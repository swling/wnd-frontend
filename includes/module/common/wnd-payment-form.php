<?php

namespace Wnd\Module\Common;

use Wnd\Controller\Wnd_Request;
use Wnd\Getway\Wnd_Payment_Getway;
use Wnd\Model\Wnd_Order_Props;
use Wnd\Model\Wnd_SKU;
use Wnd\Module\Wnd_Module_Vue;

/**
 * 在线支付订单表单
 * 匿名支付订单默认启用人机验证
 * @since 2020.06.30
 */
class Wnd_Payment_Form extends Wnd_Module_Vue {

	/**
	 * 根据参数读取本次订单对应产品信息
	 * 构造：产品ID，SKU ID，数量，总金额，订单标题，SKU 提示信息
	 * @since 0.8.76
	 */
	protected static function parse_data(array $args): array {
		$user_id = get_current_user_id();
		// 将数组元素依次定义为按键名命名的变量
		$defaults = [
			'post_id'    => 0,
			'quantity'   => $args[Wnd_Order_Props::$quantity_key] ?? 1,
			'sku_id'     => $args[Wnd_Order_Props::$sku_id_key] ?? '',
			'is_virtual' => $args['is_virtual'] ?? 0,
		];
		unset($args[Wnd_Request::$sign_name]);
		$args = wp_parse_args($args, $defaults);
		extract($args);

		$balance         = wnd_get_user_balance($user_id);
		$title           = get_the_title($post_id);
		$payments        = Wnd_Payment_Getway::get_gateway_options();
		$default_gateway = Wnd_Payment_Getway::get_default_gateway();

		// 收货地址，确保为符合格式要求的数组
		$receiver = (array) (wnd_get_user_meta($user_id, 'receiver') ?: []);
		$receiver = array_merge(['name' => '', 'phone' => '', 'address' => ''], $receiver);

		// 余额支付
		if ($balance) {
			$payments[] = ['label' => __('余额', 'wnd'), 'value' => 'internal', 'icon' => '<i class="fas fa-wallet"></i>'];
		}

		// 未设置 sku 的 post
		if (!$sku_id) {
			$skus = ['single' => ['price' => wnd_get_post_price($post_id)]];
		} else {
			$skus = Wnd_SKU::get_object_sku($post_id);
		}

		$sign = Wnd_Request::sign(['post_id', 'sku_id', 'quantity', 'receiver', 'payment_gateway', 'is_virtual']);
		$msg  = (!is_user_logged_in() and $is_virtual) ? __('您当前尚未登录，匿名订单仅24小时有效，请悉知！', 'wnd') : '';

		/**
		 * 构造：产品ID，SKU ID，数量，总金额，订单标题，SKU 提示信息
		 */
		return compact(
			'balance',
			'post_id',
			'sku_id',
			'quantity',
			'title',
			'skus',
			'payments',
			'default_gateway',
			'msg',
			'is_virtual',
			'receiver',
			'sign'
		);
	}
}
