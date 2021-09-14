<?php
namespace Wnd\Model;

use Wnd\Model\Wnd_Product;

/**
 * SKU 模块
 *
 * 在本插件中，不单独对产品做定义。任何 singular 如 Post、Page 以及其他自定义 Post Type 均可为商品。一切皆可销售。
 *
 * 注意：
 *  - 产品配置将影响订单价格，因而不适用于付费阅读付费下载。
 *  - 因为付费阅读、付费下载，目前尚不支持指定 SKU 信息查询，仅通过判断用户在当前 Post 下是否有已完成支付的订单，决定内容呈现。
 *   -简言之，付费阅读付费下载，应该设置唯一产品价格。
 *
 * @since 0.9.5
 */
class Wnd_SKU {

	// SKU KEY
	public static $sku_key = 'sku';

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
		// SKU 解析
		$sku_data = static::parse_sku_data($data, get_post_type($object_id));

		// 保存数据
		return wnd_update_post_meta($object_id, static::$sku_key, $sku_data);
	}

	/**
	 * 根据表单 name 前缀 自动依序遍历提取 _sku_* 并组合成新的二维数组，数据格式参考如下：
	 * 	$sku = [
	 * 		'sku_0' => ['name' => '套餐1', 'price' => '0.1', 'stock' => 10],
	 * 		'sku_1' => ['name' => '套餐2', 'price' => '0.2', 'stock' => 5],
	 * 	];
	 */
	protected static function parse_sku_data(array $data, string $post_type): array{
		$sku_data = [];
		$sku_keys = array_keys(static::get_sku_keys($post_type));

		/**
		 * #第一步：
		 *
		 * - 忽略非 SKU 属性数据
		 * - 移除表单 name 前缀
		 * - 忽略 SKU Keys 设定范围外的数据
		 *
		 * $data = [
		 * 		static::$name_prefix . 'name'  => ['name1','name2'],
		 * 		static::$name_prefix . 'price' => ['price1','price2'],
		 * 	];
		 *
		 * 提取后的数据：
		 * $sku_data = [
		 * 		'name'  => ['name1','name2'],
		 * 		'price' => ['price1','price2'],
		 * 	];
		 */
		foreach ($data as $key => $value) {
			if (false === stripos($key, static::$name_prefix)) {
				continue;
			}

			$props_key = str_replace(static::$name_prefix, '', $key);
			if (!in_array($props_key, $sku_keys)) {
				continue;
			}

			$sku_data[$props_key] = $value;
		}unset($key, $value);

		/**
		 * #第二步：
		 *
		 * 接收数据：
		 * $sku_data = [
		 * 		'name'  => ['name1','name2'],
		 * 		'price' => ['price1','price2'],
		 * 	];
		 *
		 * 返回数据：
		 * 	$sku = [
		 * 		'sku_0' => ['name' => 'name1', 'price' => 'price1'],
		 * 		'sku_1' => ['name' => 'name2', 'price' => 'price2'],
		 * 	];
		 */
		$sku = [];
		for ($i = 0, $size = count($sku_data['name']); $i < $size; $i++) {
			// SKU 标题为必选，若未设置，则删除本条信息
			if (!$sku_data['name'][$i]) {
				continue;
			}

			foreach ($sku_data as $sku_detail_key => $sku_detail_value) {
				// SKU ID
				$sku_id = 'sku_' . $i;

				// 组合 SKU 数据
				$sku[$sku_id][$sku_detail_key] = $sku_detail_value[$i];

				// 移除 SKU 属性中的空值
				$sku[$sku_id] = array_filter(array_unique($sku[$sku_id], SORT_REGULAR));
			}unset($key, $value);
		}

		return $sku;
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
		return Wnd_Product::get_object_props($object_id)[static::$sku_key] ?? [];
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
	 *
	 * 若未设置库存，表示该商品为无限库存（如虚拟商品等），返回 -1
	 */
	public static function get_single_sku_stock(int $object_id, string $sku_id): int {
		return static::get_single_sku($object_id, $sku_id)['stock'] ?? -1;
	}

	/**
	 * 更新指定单个 SKU
	 */
	protected static function update_single_sku(int $object_id, string $sku_id, array $single_sku): bool{
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

		return wnd_update_post_meta($object_id, static::$sku_key, $sku);
	}

	/**
	 * 扣除单个 SKU 库存
	 *
	 * - 若未设置库存或库为 -1 ：则为无限库存产品，如虚拟商品，则无需操作库存
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
}
