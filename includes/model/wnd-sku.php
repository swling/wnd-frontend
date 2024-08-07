<?php
namespace Wnd\Model;

use Wnd\Model\Wnd_Product;

/**
 * SKU 模块（作用于商品）
 * - 设置或读取商品 SKU 信息
 * - 在本插件中，不单独对产品做定义。任何 singular 如 Post、Page 以及其他自定义 Post Type 均可为商品。一切皆可销售。
 *
 * 注意：
 *  - 产品配置将影响订单价格，因而不适用于付费阅读付费下载。
 *  - 因为付费阅读、付费下载，目前尚不支持指定 SKU 信息查询，仅通过判断用户在当前 Post 下是否有已完成支付的订单，决定内容呈现。
 *   -简言之，付费阅读付费下载，应该设置唯一产品价格。
 *
 * @since 0.9.5
 */
abstract class Wnd_SKU {

	// SKU KEY
	private static $sku_meta_key = 'sku';

	// Form input name prefix
	public static $name_prefix = '_sku_';

	/**
	 * SKU 属性信息合集
	 * @param $post_type 产品类型 本插件不固定商品类型，因此可能不同类型的 post 需要不同的 SKU
	 */
	public static function get_sku_keys($post_type): array{
		$sku_keys = [
			'name'  => __('名称', 'wnd'),
			'price' => __('价格', 'wnd'),
			'stock' => __('库存', 'wnd'),
			'color' => __('颜色', 'wnd'),
			'size'  => __('尺寸', 'wnd'),
		];

		return apply_filters('wnd_sku_keys', $sku_keys, $post_type);
	}

	/**
	 * 设置产品属性
	 *
	 * # SKU
	 * 	$sku = [
	 * 		'sku_0' => ['name' => '套餐1', 'price' => '0.1', 'stock' => 10],
	 * 		'sku_1' => ['name' => '套餐2', 'price' => '0.2', 'stock' => 5],
	 * 	];
	 *
	 */
	public static function set_object_sku(int $object_id, array $data): bool{
		$post_type = get_post_type($object_id);
		$sku_data  = static::parse_sku_data($data, $post_type);
		return wnd_update_post_meta($object_id, static::$sku_meta_key, $sku_data);
	}

	/**
	 * 前端接收数据格式：
	 * 	$data = [
	 * 		0 => ['name' => '套餐1', 'price' => '0.1', 'stock' => 10],
	 * 		1 => ['name' => '套餐2', 'price' => '0.2', 'stock' => 5],
	 * 	];
	 * 
	 * 清洗后数据格式参考如下：
	 * 	$sku = [
	 * 		'sku_0' => ['name' => '套餐1', 'price' => '0.1', 'stock' => 10],
	 * 		'sku_1' => ['name' => '套餐2', 'price' => '0.2', 'stock' => 5],
	 * 	];
	 */
	private static function parse_sku_data(array $data, string $post_type): array{
		$sku_data = [];
		$sku_keys = array_keys(static::get_sku_keys($post_type));

		foreach ($data as $key => $sku_detail) {
			// 过滤空值、仅提取指定 sku 属性
			if (!wnd_array_filter($sku_detail)) {
				continue;
			}
			$sku_detail = array_intersect_key($sku_detail, array_flip($sku_keys));

			$key            = 'sku_' . $key;
			$sku_data[$key] = $sku_detail;
		}
		unset($key, $value);

		return $sku_data;
	}

	/**
	 * 获取产品 SKU，数据格式参考如下
	 *
	 * 	$sku = [
	 * 		'sku_0' => ['name' => '套餐1', 'price' => '0.1', 'stock' => 10],
	 * 		'sku_1' => ['name' => '套餐2', 'price' => '0.2', 'stock' => 5],
	 * 	];
	 */
	public static function get_object_sku(int $object_id): array{
		return Wnd_Product::get_object_props($object_id)[static::$sku_meta_key] ?? [];
	}

	/**
	 * 获取指定单个 SKU 详情
	 */
	public static function get_single_sku(int $object_id, string $sku_id): array{
		return static::get_object_sku($object_id)[$sku_id] ?? [];
	}

	/**
	 * 获取指定单个 SKU 价格
	 */
	public static function get_single_sku_price(int $object_id, string $sku_id): float {
		return (float) (static::get_single_sku($object_id, $sku_id)['price'] ?? 0);
	}

	/**
	 * 获取指定单个 SKU 名称
	 */
	public static function get_single_sku_name(int $object_id, string $sku_id): string {
		return static::get_single_sku($object_id, $sku_id)['name'] ?? '';
	}

	/**
	 * 获取指定单个 SKU 名称
	 * - 若未设置库存，表示该商品为无限库存（如虚拟商品等），返回 -1
	 */
	public static function get_single_sku_stock(int $object_id, string $sku_id): int {
		return static::get_single_sku($object_id, $sku_id)['stock'] ?? -1;
	}

	/**
	 * 扣除单个 SKU 库存
	 * - 若未设置库存或库为 -1 ：则为无限库存产品，如虚拟商品，则无需操作库存
	 * - 可设置 $quantity 为负值，则增加库存（如取消订单时）
	 *
	 */
	public static function reduce_single_sku_stock(int $object_id, string $sku_id, int $quantity): bool{
		$single_sku = static::get_single_sku($object_id, $sku_id);
		if (!isset($single_sku['stock'])) {
			return false;
		}

		if (-1 == $single_sku['stock']) {
			return false;
		}

		$single_sku['stock'] = $single_sku['stock'] - $quantity;

		return static::update_single_sku($object_id, $sku_id, $single_sku);
	}

	/**
	 * 更新指定单个 SKU
	 */
	private static function update_single_sku(int $object_id, string $sku_id, array $single_sku): bool{
		// 移除不合规的 SKU 属性
		$sku_keys = array_keys(static::get_sku_keys(get_post_type($object_id)));
		foreach ($single_sku as $key => $value) {
			if (!in_array($key, $sku_keys)) {
				unset($single_sku[$key]);
			}
		}unset($key, $value);

		// 合并现有 SKU 后写入
		$sku          = static::get_object_sku($object_id);
		$sku[$sku_id] = $single_sku;

		return wnd_update_post_meta($object_id, static::$sku_meta_key, $sku);
	}
}
