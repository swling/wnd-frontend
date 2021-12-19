<?php
namespace Wnd\Module;

use Wnd\Getway\Wnd_Payment_Getway;
use Wnd\Model\Wnd_Order_Props;
use Wnd\Model\Wnd_SKU;

/**
 * 获取订单详情（未完善信息）
 * @since 0.9.0
 */
class Wnd_Post_Detail_Order extends Wnd_Module_Html {

	protected static function build(array $args = []): string{
		/**
		 * 订单基本信息 + 产品属性等参数
		 * 移除表单签名参数
		 */
		$defaults = [
			'post_id' => 0,
			'post'    => '',
		];
		$args = wp_parse_args($args, $defaults);

		// 将数组元素依次定义为按键名命名的变量
		extract($args);

		$order = $post ?: get_post($post_id);

		$orde_detail = '<div class="content"><ul>';
		$orde_detail .= '<li><h1>' . get_the_title($order->post_parent) . '<h1></li>';
		$orde_detail .= '<li>' . $order->post_date . '</li>';
		$orde_detail .= '<li>' . $order->post_title . '</li>';
		$orde_detail .= '<li>' . $order->post_content . '</li>';
		$orde_detail .= '<li>' . $order->post_name . '</li>';
		$orde_detail .= '<li>' . Wnd_Payment_Getway::get_payment_gateway($order->ID) . '</li>';
		$orde_detail .= '<li>Refund_Count : ' . wnd_get_post_meta($order->ID, 'refund_count') . '</li>';
		$orde_detail .= '<li>User IP : ' . wnd_get_post_meta($order->ID, Wnd_Order_Props::$ip_key) . '</li>';
		$orde_detail .= '</ul></div>';

		/**
		 * 产品属性
		 * @since 0.8.76
		 */
		$sku      = Wnd_Order_Props::get_order_sku($post_id);
		$sku_keys = Wnd_SKU::get_sku_keys(get_post_type($post_id));

		// 列出产品属性提示信息
		$sku_info = '';
		foreach ($sku as $key => $value) {
			$key = $sku_keys[$key] ?? $key;
			$sku_info .= '[ ' . $key . ' : ' . $value . ' ]&nbsp;';
		}

		return $orde_detail . wnd_notification($sku_info);
	}
}
