<?php
namespace Wnd\Model;

use Wnd\Model\Wnd_SKU;
use Wnd\Model\Wnd_Transaction;

/**
 *@since 0.9.0
 *
 *商品订单模块
 *
 *在本插件中，不单独对产品做定义。任何 singular 如 Post、Page 以及其他自定义 Post Type 均可为商品。一切皆可销售。
 *
 *注意：
 *  产品配置将影响订单价格，因而不适用于付费阅读付费下载。
 *  因为付费阅读、付费下载，目前尚不支持指定 SKU 信息查询，仅通过判断用户在当前 Post 下是否有已完成支付的订单，决定内容呈现。
 *  简言之，付费阅读付费下载，应该设置唯一产品价格。
 */
class Wnd_Order_Product {

	// SKU KEY
	protected static $sku_key = 'sku';

	// SKU ID KEY
	public static $sku_id_key = 'sku_id';

	// 购买商品数目
	public static $quantity_key = 'quantity';

	// IP
	public static $ip_key = 'ip';

	/**
	 *设置订单关联的产品属性
	 *
	 *读取数据中和产品属性相关的数据，保存至订单 wnd meta
	 *由于sku_id 对应的产品信息可能发生改变，因此必须保存订单产生时的产品完整属性，以备后续核查
	 *在产品单条 SKU 信息之外新增保存 SKU ID
	 */
	public static function set_order_props(int $order_id, array $data): bool{
		$meta      = [];
		$object_id = get_post($order_id)->post_parent ?? 0;
		if (!$object_id) {
			return false;
		}

		// SKU
		$sku_id = $data[static::$sku_id_key] ?? '';
		if ($sku_id) {
			$sku_detail                      = Wnd_SKU::get_single_sku($object_id, $sku_id);
			$sku_detail[static::$sku_id_key] = $sku_id;
			$meta[static::$sku_key]          = $sku_detail;
		}

		// quantity
		$quantity                    = $data[static::$quantity_key] ?? 1;
		$meta[static::$quantity_key] = $quantity;

		// IP
		$meta[static::$ip_key] = wnd_get_user_ip();

		// save data
		if ($meta) {
			return wnd_update_post_meta_array($order_id, $meta);
		} else {
			return true;
		}
	}

	/**
	 *获取订单关联的产品属性
	 *
	 *	订单属性，即从产品属性提供的选项中依次确定某一项组成。数据存储键名与产品属性保持一致。因此可复用 Wnd_Product::get_object_props($order_id);
	 *	与产品属性返回的数据格式不同，【产品属性值】通常为维数组甚至二维数组，而【订单属性值】通常为确定的字符串。
	 *
	 */
	public static function get_order_props(int $order_id): array{
		return get_post_meta($order_id, 'wnd_meta', true) ?: [];
	}

	/**
	 *获取订单关联的产品 SKU 属性
	 *
	 */
	public static function get_order_sku(int $order_id): array{
		return static::get_order_props($order_id)[static::$sku_key] ?? [];
	}

	/**
	 *释放未支付的订单，已更新订单统计及库存
	 * - 删除15分钟前未完成的订单，并扣除订单统计
	 */
	public static function release_pending_orders(int $object_id) {
		$args = [
			'posts_per_page' => -1,
			'post_type'      => 'order',
			'post_parent'    => $object_id,
			'post_status'    => Wnd_Transaction::$processing_status,
			'date_query'     => [
				[
					'column'    => 'post_date',
					'before'    => date('Y-m-d H:i:s', current_time('timestamp', false) - 900),
					'inclusive' => true,
				],
			],
		];
		foreach (get_posts($args) as $order) {
			// 取消订单
			static::cancel_order($order);

			/**
			 * 此处不直接修正order_count，而是在删除订单时，通过action修正order_count @see wnd_action_deleted_post
			 * 以此确保订单统计的准确性，如用户主动删除，或其他原因人为删除订单时亦能自动修正订单统计
			 */
			wp_delete_post($order->ID, true);
		}
		unset($order, $args);
	}

	/**
	 *取消订单
	 */
	protected static function cancel_order(\WP_Post $order) {
		// 取消行为仅针对状态为待处理的订单
		if (Wnd_Transaction::$processing_status != $order->post_status) {
			return false;
		}

		$object_id = $order->post_parent ?? 0;
		if (!$object_id) {
			return false;
		}

		/**
		 *  还原库存
		 *
		 * 此处不可调用 Wnd_SKU::reduce_single_sku_stock 及 Wnd_Product 其他获取产品属性的方法
		 * Wnd_Product 相关方法在获取现有 SKU 信息时，会调用本类中的 static::release_pending_orders 从而产生死循环
		 */
		$props    = static::get_order_props($order->ID);
		$sku_id   = $props[static::$sku_key][static::$sku_id_key] ?? '';
		$quantity = $props[static::$quantity_key] ?? 1;

		// 获取现有库存，若未设置库存，或库存为 -1 表示为虚拟产品或其他无限量库存产品，无需操作
		$object_sku        = wnd_get_post_meta($object_id, Wnd_SKU::$sku_key) ?? [];
		$object_single_sku = $object_sku[$sku_id] ?? [];
		if (!isset($object_single_sku['stock']) or -1 == $object_single_sku['stock']) {
			return false;
		}
		$object_single_sku['stock'] = $object_single_sku['stock'] + $quantity;

		// update post meta
		$object_sku[$sku_id] = $object_single_sku;
		wnd_update_post_meta($object_id, Wnd_SKU::$sku_key, $object_sku);
	}
}
