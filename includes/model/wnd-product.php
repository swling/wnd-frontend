<?php
namespace Wnd\Model;

use Wnd\Model\Wnd_Order_Product;

/**
 * 商品模块
 * 在本插件中，不单独对产品做定义。任何 singular 如 Post、Page 以及其他自定义 Post Type 均可为商品。一切皆可销售。
 *
 * 注意：
 * - 产品配置将影响订单价格，因而不适用于付费阅读付费下载。
 * - 因为付费阅读、付费下载，目前尚不支持指定 SKU 信息查询，仅通过判断用户在当前 Post 下是否有已完成支付的订单，决定内容呈现。
 * 简言之，付费阅读付费下载，应该设置唯一产品价格。
 * @since 0.8.76
 */
abstract class Wnd_Product {

	/**
	 * 产品属性信息合集
	 */
	private static function get_props_keys(): array{
		return [
			'sku'         => __('SKU', 'wnd'),
			'order_count' => __('销量', 'wnd'),
		];
	}

	/**
	 * 获取产品全部属性
	 */
	public static function get_object_props(int $object_id): array{
		// 释放规定时间未完成的订单，以确保库存数据正确性
		Wnd_Order_Product::release_pending_orders($object_id);

		$meta  = get_post_meta($object_id, 'wnd_meta', true) ?: [];
		$props = static::parse_props_data($meta);

		/**
		 * Filter
		 * @since 0.9.32
		 */
		return apply_filters('wnd_product_props', $props, $object_id);
	}

	/**
	 * 从数组数据中按 static::get_props_keys() 数组键名提取产品 Props 数据
	 *
	 */
	private static function parse_props_data(array $data): array{
		$props_keys = array_keys(static::get_props_keys());
		foreach ($data as $key => $value) {
			// 移除非产品属性数据
			if (!in_array($key, $props_keys)) {
				unset($data[$key]);
				continue;
			}

			// 数组数据：过滤空值并去重
			$data[$key] = is_array($data[$key]) ? array_filter(array_unique($data[$key], SORT_REGULAR)) : $data[$key];
		}unset($key, $value);

		return $data;
	}

	/**
	 * @since 0.9.0 查询订单统计
	 *
	 * @param  	int 	$object_id 	商品ID
	 * @return 	int 	order count
	 */
	public static function get_order_count($object_id): int {
		return static::get_object_props($object_id)['order_count'] ?? 0;
	}

	/**
	 * @since 0.9.0 增加订单统计
	 *
	 * @param 	int 	$object_id 	商品ID
	 * @param 	int 	$number    	增加的数目，可为负
	 */
	public static function inc_order_count(int $object_id, int $number): bool{
		$order_count = static::get_order_count($object_id);
		$order_count = $order_count + $number;
		return wnd_update_post_meta($object_id, 'order_count', $order_count);
	}
}
