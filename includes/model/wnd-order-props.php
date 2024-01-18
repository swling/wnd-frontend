<?php
namespace Wnd\Model;

use Wnd\Model\Wnd_Product;
use Wnd\Model\Wnd_SKU;
use Wnd\Model\Wnd_Transaction;
use Wnd\WPDB\Wnd_Transaction_DB;

/**
 * 商品类订单模块 （作用于订单）
 * - 设置或读取商品类订单的属性
 * - 在本插件中，不单独对产品做定义。任何 singular 如 Post、Page 以及其他自定义 Post Type 均可为商品。一切皆可销售。
 *
 * 注意：
 * - 产品配置将影响订单价格，因而不适用于付费阅读付费下载。
 * - 因为付费阅读、付费下载，目前尚不支持指定 SKU 信息查询，仅通过判断用户在当前 Post 下是否有已完成支付的订单，决定内容呈现。
 * - 简言之，付费阅读付费下载，应该设置唯一产品价格。
 *
 * @since 0.9.0
 */
abstract class Wnd_Order_Props {

	// SKU KEY
	private static $sku_key = 'sku';

	// SKU ID KEY（同时也为订单创建时，用户请求的数据 name）
	public static $sku_id_key = 'sku_id';

	// 购买商品数目（同时也为订单创建时，用户请求的数据 name）
	public static $quantity_key = 'quantity';

	// IP
	public static $ip_key = 'ip';

	/**
	 * 从订单请求数据中解析订单属性
	 * - 由于sku_id 对应的产品信息可能发生改变，因此必须保存订单产生时的产品完整属性，以备后续核查，同时保存 SKU ID
	 * - 保存订单产品数量
	 * - 保存客户端 ip
	 */
	public static function parse_order_props(int $object_id, array $data): array {
		$meta         = [];
		$sku_key      = static::$sku_key;
		$quantity_key = static::$quantity_key;
		$ip_key       = static::$ip_key;

		// SKU
		$sku_id = $data[static::$sku_id_key] ?? '';
		if ($sku_id) {
			$sku_detail                      = Wnd_SKU::get_single_sku($object_id, $sku_id);
			$sku_detail[static::$sku_id_key] = $sku_id;
			$meta[$sku_key]                  = $sku_detail;
		}

		// quantity
		$meta[$quantity_key] = $data[static::$quantity_key] ?? 1;

		// IP
		$meta[$ip_key] = wnd_get_user_ip();

		// 备份全部请求数据信息
		$meta['request'] = $data;

		// data
		return $meta;
	}

	/**
	 * 更新订单 props
	 * @since 0.9.67
	 */
	public static function update_order_props(int $order_id, array $data) {
		$data    = array_merge(static::get_order_props($order_id), $data);
		$props   = json_encode($data, JSON_UNESCAPED_UNICODE);
		$handler = Wnd_Transaction_DB::get_instance();
		return $handler->update(['ID' => $order_id, 'props' => $props]);
	}

	/**
	 * 获取订单关联的产品属性
	 * - 订单属性，即从产品属性提供的选项中依次确定某一项组成。数据存储键名与产品属性保持一致。因此可复用 Wnd_Product::get_object_props($order_id);
	 * - 与产品属性返回的数据格式不同，【产品属性值】通常为维数组甚至二维数组，而【订单属性值】通常为确定的字符串。
	 */
	public static function get_order_props(int $order_id): array {
		$order = Wnd_Transaction::query_db(['ID' => $order_id]);
		$props = $order->props ?? '';
		return json_decode($props, true);
	}

	/**
	 * 获取订单关联的产品 SKU 属性
	 */
	public static function get_order_sku(int $order_id): array {
		return static::get_order_props($order_id)[static::$sku_key] ?? [];
	}

	/**
	 * 获取订单关联的产品 SKU ID
	 */
	public static function get_order_sku_id(int $order_id): string {
		return static::get_order_sku($order_id)[static::$sku_id_key] ?? '';
	}

	/**
	 * 获取订单关联的产品 SKU ID
	 */
	public static function get_order_quantity(int $order_id): int {
		return static::get_order_props($order_id)[static::$quantity_key] ?? 0;
	}

	/**
	 * 释放未支付的订单，已更新订单统计及库存
	 * - 删除60分钟前未付款的订单，并扣除订单统计
	 */
	public static function release_pending_orders(int $object_id) {
		$args = [
			'type'      => 'order',
			'object_id' => $object_id,
			'status'    => Wnd_Transaction::$pending_status,
			'time'      => '< ' . time() - 3600,
		];
		$orders = Wnd_Transaction::get_results($args);

		foreach ($orders as $order) {
			/**
			 * 此处不直接调用 static::cancel_order()，而是在删除订单时，通过action修正 @see wnd_action_deleted_post
			 * 以此确保订单统计的准确性，如用户主动删除，或其他原因人为删除订单时亦能自动修正订单统计
			 */
			// wp_delete_post($order->ID, true);
			Wnd_Transaction::delete($order->ID);
		}
		unset($order, $args);
	}

	/**
	 * 取消订单
	 */
	public static function cancel_order(object $order) {
		$object_id = $order->object_id ?? 0;
		if (!$object_id) {
			return false;
		}

		/**
		 * 订单及库存取消行为仅针对状态为待付款的订单
		 * 不可取消此处判断，因本方法可在外部直接调用
		 */
		if (Wnd_Transaction::$pending_status != $order->status) {
			return false;
		}

		/**
		 * @since 2019.06.04 扣除订单统计
		 */
		Wnd_Product::inc_order_count($object_id, -1);

		// 还原对应产品的库存
		static::restore_stock($order);
	}

	/**
	 * 还原库存
	 *
	 */
	private static function restore_stock(object $order) {
		$object_id = $order->object_id ?? 0;

		$props    = static::get_order_props($order->ID);
		$sku_id   = $props[static::$sku_key][static::$sku_id_key] ?? '';
		$quantity = $props[static::$quantity_key] ?? 1;

		/**
		 * 移除钩子
		 * - Wnd_SKU 获取产品属性时，会触发释放指定时间内未完成支付的订单
		 * - 如果当前产品包含可释放的订单，将陷入死循环：还原库存=>触发订单释放=>还原库存
		 * - 因而此处移除 Action
		 * @since 0.9.38
		 */
		remove_action('wnd_pre_get_product_props', [__CLASS__, 'release_pending_orders'], 10);

		// 还原库存（反向扣除库存）
		Wnd_SKU::reduce_single_sku_stock($object_id, $sku_id, $quantity * -1);

		/**
		 * 库存恢复完成，恢复 Action
		 * @since 0.9.38
		 */
		add_action('wnd_pre_get_product_props', [__CLASS__, 'release_pending_orders'], 10, 1);
	}
}
