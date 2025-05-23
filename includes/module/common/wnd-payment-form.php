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

		// 定义默认值
		$defaults = [
			'post_id'    => 0,
			'quantity'   => $args[Wnd_Order_Props::$quantity_key] ?? 1,
			'sku_id'     => $args[Wnd_Order_Props::$sku_id_key] ?? '',
			'is_virtual' => $args['is_virtual'] ?? 0,
		];

		// 移除签名参数
		unset($args[Wnd_Request::$sign_name]);

		// 合并参数
		$args = wp_parse_args($args, $defaults);

		// 显式定义变量
		$post_id    = $args['post_id'];
		$quantity   = $args['quantity'];
		$sku_id     = $args['sku_id'];
		$is_virtual = $args['is_virtual'];

		// 获取用户余额
		$balance = wnd_get_user_balance($user_id);

		// 获取产品标题
		$title = get_the_title($post_id);

		// 获取支付方式
		$payments        = Wnd_Payment_Getway::get_gateway_options();
		$default_gateway = Wnd_Payment_Getway::get_default_gateway();

		// 获取收货地址，确保为符合格式要求的数组
		$receiver = (array) (wnd_get_user_meta($user_id, 'receiver') ?: []);
		$receiver = array_merge(['name' => '', 'phone' => '', 'address' => ''], $receiver);

		// 如果用户有余额，添加余额支付选项
		if ($balance) {
			$payments[] = ['label' => __('余额', 'wnd'), 'value' => 'internal', 'icon' => '<i class="fas fa-wallet"></i>'];
		}

		// 获取 SKU 信息
		if (!$sku_id) {
			$skus = ['single' => ['price' => wnd_get_post_price($post_id)]];
		} else {
			$skus = Wnd_SKU::get_object_sku($post_id);
		}

		// 生成签名
		$sign = Wnd_Request::sign(['post_id', 'sku_id', 'quantity', 'receiver', 'payment_gateway', 'is_virtual']);

		// 提示信息
		$msg = (!is_user_logged_in() && $is_virtual) ? __('您当前尚未登录，匿名订单仅24小时有效，请悉知！', 'wnd') : '';

		// 返回显式构造的数组
		return [
			'balance'         => $balance,
			'post_id'         => $post_id,
			'sku_id'          => $sku_id,
			'quantity'        => $quantity,
			'title'           => $title,
			'skus'            => $skus,
			'payments'        => $payments,
			'default_gateway' => $default_gateway,
			'msg'             => $msg,
			'is_virtual'      => $is_virtual,
			'receiver'        => $receiver,
			'sign'            => $sign,
		];
	}
}
