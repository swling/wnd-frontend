<?php
namespace Wnd\Module;

use Wnd\Model\Wnd_Order_Product;
use Wnd\Model\Wnd_Product;

/**
 *@since 0.9.0
 *获取订单详情（未完善信息）
 */
class Wnd_Post_Detail_Order extends Wnd_Module {

	protected static function build($args = []): string{
		/**
		 *订单基本信息 + 产品属性等参数
		 *移除表单签名参数
		 */
		$defaults = [
			'post_id' => 0,
			'post'    => '',
		];
		$args = wp_parse_args($args, $defaults);

		// 将数组元素依次定义为按键名命名的变量
		extract($args);

		/**
		 *@since 0.8.76
		 *产品属性
		 */
		$sku      = Wnd_Order_Product::get_order_sku($post_id);
		$sku_keys = Wnd_Product::get_sku_keys(get_post_type($post_id));

		// 列出产品属性提示信息
		$sku_info = '';
		foreach ($sku as $key => $value) {
			$key = $sku_keys[$key] ?? $key;
			$sku_info .= '[ ' . $key . ' : ' . $value . ' ]&nbsp;';
		}

		return static::build_notification($sku_info);
	}
}
