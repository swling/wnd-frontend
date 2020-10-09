<?php
namespace Wnd\Model;

/**
 *@since 0.8.76
 *
 *商品模块
 *
 *在本插件中，不单独对产品做定义。任何 singular 如 Post、Page 以及其他自定义 Post Type 均可为商品。一切皆可销售。
 */
class Wnd_Product {

	// SKU KEY
	public static $sku_key = 'sku';

	// SKU KEY
	public static $color_key = 'color';

	// SKU KEY
	public static $size_key = 'size';

	/**
	 *产品属性数组
	 */
	public static function get_props_keys(): array{
		return [
			'sku'   => __('SKU', 'wnd'),
			'color' => __('颜色', 'wnd'),
			'size'  => __('尺寸', 'wnd'),
		];
	}

	/**
	 *设置产品属性
	 *
	 * # SKU
	 *	$sku = [
	 *		'sku_0' => ['title' => '套餐1', 'price' => '0.1', 'stock' => 10],
	 *		'sku_1' => ['title' => '套餐2', 'price' => '0.2', 'stock' => 5],
	 *	];
	 *
	 * # color
	 * $color = ['red', 'green'];
	 *
	 * # size
	 * $size = ['small', 'big'];
	 *
	 * ……
	 *
	 */
	public static function set_object_props(int $object_id, array $data) {
		// SKU 为二维数组，需要特别解析保存
		$sku_data = static::parse_sku_data($data);

		// 解析常规产品属性数据
		$data = static::parse_props_data($data);

		// 合并 SKU
		$data[static::$sku_key] = $sku_data;

		// 保存数据
		wnd_update_post_meta_array($object_id, $data);
	}

	/**
	 *解析 SKU
	 *
	 * 依序遍历提取 sku_stock, stock_price, sku_title 并组合成新的二维数组，数据格式如下：
	 *	$sku = [
	 *		'sku_0' => ['title' => '套餐1', 'price' => '0.1', 'stock' => 10],
	 *		'sku_1' => ['title' => '套餐2', 'price' => '0.2', 'stock' => 5],
	 *	];
	 */
	protected static function parse_sku_data(array $data): array{
		$sku = [];

		// SKU 标题为必选，若未设置，则删除本条信息
		for ($i = 0; $i < count($data['sku_title']); $i++) {
			if (!$data['sku_title'][$i]) {
				continue;
			}

			$sku['sku_' . $i]['title'] = $data['sku_title'][$i];
			$sku['sku_' . $i]['price'] = $data['sku_price'][$i];
			$sku['sku_' . $i]['stock'] = $data['sku_stock'][$i];
		}

		return $sku;
	}

	/**
	 * 从数组数据中按 static::get_props_keys() 数组键名提取产品 Props 数据
	 *
	 */
	public static function parse_props_data(array $data): array{
		foreach ($data as $key => $value) {
			// 移除非产品属性数据
			if (!in_array($key, array_keys(static::get_props_keys()))) {
				unset($data[$key]);
				continue;
			}

			// 数组数据：过滤空值并去重
			$data[$key] = is_array($data[$key]) ? array_filter(array_unique($data[$key], SORT_REGULAR)) : $data[$key];
		}unset($key, $value);

		return $data;
	}

	/**
	 *获取产品全部属性
	 */
	public static function get_object_props(int $object_id): array{
		$meta = get_post_meta($object_id, 'wnd_meta', true) ?: [];

		return static::parse_props_data($meta);
	}

	/**
	 *获取产品 SKU
	 */
	public static function get_object_sku(int $object_id): array{
		return wnd_get_post_meta($object_id, static::$sku_key) ?: [];
	}

	/**
	 *获取指定单个 SKU 详情
	 */
	public static function get_single_sku(int $object_id, string $sku_id): array{
		return static::get_object_sku($object_id)[$sku_id] ?? [];
	}

	/**
	 *设置订单关联的产品属性
	 *
	 *读取数据中和产品属性相关的数据，保存至订单 wnd meta
	 */
	public static function set_order_props(int $order_id, array $data) {
		$data = static::parse_props_data($data);

		wnd_update_post_meta_array($order_id, $data);
	}
}
