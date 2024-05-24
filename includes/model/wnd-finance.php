<?php
namespace Wnd\Model;

use Wnd\Model\Wnd_Order;
use Wnd\Model\Wnd_Order_Anonymous;
use Wnd\Model\Wnd_SKU;
use Wnd\WPDB\Wnd_User;

/**
 * 站内财务信息
 * @since 2019.10.25
 */
abstract class Wnd_Finance {

	/**
	 * @since 2019.02.11 查询是否已经支付
	 *
	 * @param  int  	$user_id          用户ID
	 * @param  int  	$object_id        Post ID
	 * @return bool 	是否已支付
	 */
	public static function user_has_paid($user_id, $object_id): bool {
		if (!$object_id) {
			return false;
		}

		// 匿名支付订单查询
		if (!$user_id) {
			return Wnd_Order_Anonymous::has_paid(0, $object_id);
		}

		// 不能将布尔值直接做为缓存结果，会导致无法判断是否具有缓存，转为整型 0/1
		return Wnd_Order::has_paid($user_id, $object_id);
	}

	/**
	 * 充值成功 写入用户 字段
	 *
	 * @param 	int   	$user_id  	用户ID
	 * @param 	float 	$money 		金额
	 * @param 	bool  	$external  	是否站外
	 */
	public static function inc_user_balance(int $user_id, float $amount, bool $external): bool {
		if (!get_user_by('id', $user_id)) {
			return false;
		}

		$action = Wnd_User::inc_user_balance($user_id, $amount);
		if (!$action) {
			return $action;
		}

		// 站外充值
		if ($amount > 0 and $external) {
			static::update_fin_stats($amount, 'recharge');
		}

		// 站内消费
		if ($amount < 0 and !$external) {
			static::update_fin_stats($amount, 'expense');
		}

		return $action;
	}

	/**
	 * 获取用户账户金额
	 * @param  	int   	$user_id       	用户ID
	 * @return 	float 	用户余额
	 */
	public static function get_user_balance(int $user_id, bool $format = false): mixed {
		$balance = wnd_get_wnd_user($user_id)->balance ?? 0;
		return static::format($balance, $format);
	}

	/**
	 * 新增用户消费记录
	 *
	 * @param 	int   	$user_id 	用户ID
	 * @param 	float 	$amount		金额
	 */
	public static function inc_user_expense($user_id, $amount): bool {
		if (!get_user_by('id', $user_id)) {
			return false;
		}

		$action = Wnd_User::inc_user_expense($user_id, $amount);

		// 整站按月统计充值和消费
		if ($action) {
			static::update_fin_stats($amount, 'expense');
		}
		return $action;
	}

	/**
	 * 获取用户消费
	 * @param  	int   	$user_id       	用户ID
	 * @return 	float 	用户消费
	 */
	public static function get_user_expense($user_id, $format = false): mixed {
		$expense = wnd_get_wnd_user($user_id)->expense ?? 0;
		return static::format($expense, $format);
	}

	/**
	 * 写入用户佣金
	 * @since 2019.02.22
	 *
	 * @param 	int   	$user_id 	用户ID
	 * @param 	float 	$money   		金额
	 */
	public static function inc_user_commission($user_id, $money): bool {
		return wnd_inc_wnd_user_meta($user_id, 'commission', number_format($money, 2, '.', ''));
	}

	/**
	 * @since 2019.02.18 获取用户佣金
	 *
	 * @param  	int   	$user_id       	用户ID
	 * @return 	float 	用户佣金
	 */
	public static function get_user_commission($user_id, $format = false): mixed {
		$commission = floatval(wnd_get_user_meta($user_id, 'commission'));
		return static::format($commission, $format);
	}

	/**
	 * 文章价格
	 * 新增产品 SKU
	 * @since 2019.02.13
	 * @since 0.8.76
	 *
	 * @param  	int    	$user_id                 	用户 ID
	 * @param  	string 	$sku_id		产品          SKU ID
	 * @param  	bool   	$format                  	是否格式化输出
	 * @return 	float  	两位数的价格信息 或者 0
	 */
	public static function get_post_price($post_id, $sku_id = '', $format = false): mixed {
		$price = floatval(get_post_meta($post_id, 'price', 1) ?: 0);

		if (!$price and $sku_id) {
			$price = Wnd_SKU::get_single_sku_price($post_id, $sku_id);
			$price = floatval($price);
		}

		$price = apply_filters('wnd_get_post_price', $price, $post_id, $sku_id);
		return static::format($price, $format);
	}

	/**
	 * 订单实际支付金额
	 * @since 0.8.76
	 *
	 * @param  	int   	$order_id      	订单 ID
	 * @return 	float 	订单金额
	 */
	public static function get_order_amount($order_id, $format = false): mixed {
		try {
			$order = new Wnd_Order;
			$order->set_transaction_id($order_id);
			$amount = $order->get_total_amount();
			return static::format($amount, $format);
		} catch (\Exception $e) {
			return $format ? 0.00 : 0;
		}
	}

	/**
	 * 订单佣金分成
	 * @since 2019.02.12
	 *
	 * @param  	int   	$order_id      产生佣金的订单
	 * @param  	int   	$order_id      产生佣金的订单
	 * @return 	float 	佣金分成
	 */
	public static function get_order_commission($order_id, $format = false): mixed {
		$rate       = floatval(wnd_get_config('commission_rate'));
		$amount     = static::get_order_amount($order_id);
		$commission = $amount * $rate;

		$commission = apply_filters('wnd_get_order_commission', $commission, $order_id);
		return static::format($commission, $format);
	}

	/**
	 * 新增本篇付费内容作者佣金总额
	 * @since 2020.06.10
	 *
	 * @param 	int   	$post_id 	Post ID
	 * @param 	float 	$money   		金额
	 */
	public static function inc_post_total_commission($post_id, $money): bool {
		return wnd_inc_wnd_post_meta($post_id, 'total_commission', number_format($money, 2, '.', ''));
	}

	/**
	 * 获取付费内容作者获得的佣金
	 * @since 2020.06.10
	 *
	 * @param  	int   	$post_id       Post ID
	 * @return 	float 	用户佣金
	 */
	public static function get_post_total_commission($post_id, $format = false): mixed {
		$total_commission = floatval(wnd_get_post_meta($post_id, 'total_commission'));
		return static::format($total_commission, $format);
	}

	/**
	 * 新增商品总销售额
	 * @since 2020.06.10
	 *
	 * @param 	int   	$post_id 	Post ID
	 * @param 	float 	$money   		金额
	 */
	public static function inc_post_total_sales($post_id, $money): bool {
		return wnd_inc_wnd_post_meta($post_id, 'total_sales', number_format($money, 2, '.', ''));
	}

	/**
	 * 获取商品总销售额
	 * @since 2020.06.10
	 *
	 * @param  	int   	$user_id       	用户ID
	 * @return 	float 	用户佣金
	 */
	public static function get_post_total_sales($post_id, $format = false): mixed {
		$total_sales = floatval(wnd_get_post_meta($post_id, 'total_sales'));
		return static::format($total_sales, $format);
	}

	/**
	 * 统计整站财务数据，当用户发生充值或消费行为时触发
	 * 按月统计，每月生成两条post数据
	 * 用户充值post_type:stats-re
	 * 用户消费post_type:stats-ex
	 * 写入前，按post type 和时间查询，如果存在记录则更新记录，否则写入一条记录
	 * @since 初始化
	 *
	 * 注意：本方法并未采用 wp 内置的 wp_insert_post 及 wp_update_post 更新数据是出于数据库性能考虑
	 *
	 * @param float  $amout 	变动金额
	 * @param string $type  	数据类型：recharge/expense
	 */
	public static function update_fin_stats(float $amount, string $type) {
		switch ($type) {
			// 充值
			case 'recharge':
				$post_type = 'stats-re';
				break;

			// 消费
			case 'expense':
				$post_type = 'stats-ex';

				break;

			// 默认
			default:
				$post_type = '';
				break;
		}

		if (!$amount or !$type) {
			return;
		}

		global $wpdb;
		$year       = wnd_date('Y');
		$month      = wnd_date('m');
		$post_title = $year . '-' . $month . '-' . $post_type;
		$slug       = $post_title;
		$stats_post = wnd_get_post_by_slug($slug, $post_type, 'private');

		// 更新统计：较高并发情况下：update 存在数据不同步的问题。通过加减语法，来处理高并发数据
		if ($stats_post) {
			$sql = $wpdb->prepare(
				"UPDATE $wpdb->posts SET post_content = post_content + %.2f WHERE ID = %d",
				[$amount, $stats_post->ID]
			);

			$action = $wpdb->query($sql);
			if ($action) {
				clean_post_cache($stats_post->ID);
			}

			// 新增统计
		} else {
			$date     = current_time('mysql');
			$date_gmt = current_time('mysql', 1);
			$post_arr = [
				'post_author'       => 1,
				'post_type'         => $post_type,
				'post_title'        => $post_title,
				'post_content'      => $amount,
				'post_status'       => 'private',
				'post_name'         => $slug,
				'post_date'         => $date,
				'post_date_gmt'     => $date_gmt,
				'post_modified'     => $date,
				'post_modified_gmt' => $date_gmt,
			];
			$wpdb->insert($wpdb->posts, $post_arr);
			$post_id = (int) $wpdb->insert_id;
			if ($post_id) {
				clean_post_cache($post_id);
			}
		}
	}

	/**
	 * 货币格式化
	 * @since 0.9.60.4
	 *
	 */
	private static function format(float $amount, bool $format): mixed {
		if ($format) {
			return number_format($amount, 2, '.', '');
		} else {
			return floatval($amount);
		}
	}

}
