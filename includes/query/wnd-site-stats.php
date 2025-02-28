<?php

namespace Wnd\Query;

use Exception;
use WP_Query;

/**
 * 获取站点运营统计报告
 * @since 0.9.59.1
 *
 * @param string $type order/recharge
 *
 * @link https://www.ucharts.cn/v2/#/demo/index
 * 折线图 json 数据格式参考如下：
 *
 * 	let res = {
 * 	categories: ["2016", "2017", "2018", "2019", "2020", "2021"],
 * 	series: [
 * 		{
 * 			name: "成交量A",
 * 			lineType: "dash",
 * 			data: [35, 8, 25, 37, 4, 20]
 * 		},
 * 		{
 * 			name: "成交量B",
 * 			data: [70, 40, 65, 100, 44, 68]
 * 		},
 * 		{
 * 			name: "成交量C",
 * 			data: [100, 80, 95, 150, 112, 132]
 * 		}
 * 	 ]
 * };
 *
 */
class Wnd_Site_Stats extends Wnd_Query {

	protected static function check() {
		if (!wnd_is_manager()) {
			throw new Exception(__('权限不足', 'wnd'));
		}
	}

	protected static function query($args = []): array {
		$type  = $args['type'] ?? 'recharge';
		$range = $args['range'] ?? 'year';

		if (in_array($type, ['order', 'recharge'])) {
			return static::get_finance_stats($type, $range);
		}
	}

	// 查询财务统计数据
	private static function get_finance_stats($type, $range): array {
		// 年度统计直接查询每月统计数据
		if ('year' == $range) {
			return static::get_annual_finance_stats($type);
		}

		// 周月统计需要按天查询所有订单后合并计算
		return static::get_finance_stats_by_day($type, $range);
	}

	// 财务数据：年度消费/充值统计
	private static function get_annual_finance_stats($type): array {
		// 过去十二个月初始化数据
		$data  = [];
		$today = new \DateTimeImmutable(get_option('timezone_string'));
		for ($i = 12; $i >= 0; $i--) {
			$date        = $today->modify('first day of -' . $i . ' month')->format('y-m');
			$data[$date] = 0;
		}
		unset($date);

		// 查询过去十二个月交易统计posts
		$today     = current_time('mysql');
		$last_year = wnd_date('Y-m-d H:i:s', strtotime('-12 months', strtotime($today)));
		$args      = [
			'date_query'             => [
				[
					'after'     => $last_year,
					'before'    => $today,
					'inclusive' => true,
				],
			],
			'post_type'              => 'order' == $type ? 'stats-ex' : 'stats-re',
			'post_status'            => 'private',
			'posts_per_page'         => -1,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		];
		$query = new WP_Query($args);
		$posts = $query->get_posts();

		// 将交易记录按日期对应合并到日期数据
		foreach ($posts as $post) {
			$month        = wnd_date('y-m', strtotime($post->post_date));
			$data[$month] = number_format(($data[$month] + $post->post_content), 2, '.', '');
		}

		// 组成最终数据格式
		$result = [
			'categories' => [],
			'series'     => [
				[
					'name' => $type,
					'data' => [],
				],
			],
			'total'      => number_format(array_sum($data), 2, '.'),
		];
		foreach ($data as $date => $value) {
			$result['categories'][]        = $date;
			$result['series'][0]['data'][] = $value;
		}

		return $result;
	}

	// 财务数据：周度/月度财务数据
	private static function get_finance_stats_by_day(string $type, int $days): array {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->wnd_transactions WHERE FROM_UNIXTIME(time) >= DATE_SUB(CURDATE(), INTERVAL %d DAY) AND type = '%s' ", $days, $type)
		);

		// 日期数据初始化
		$data  = [];
		$today = new \DateTimeImmutable(get_option('timezone_string'));
		for ($i = $days; $i >= 0; $i--) {
			$date        = $today->modify('-' . $i . ' day')->format('m-d');
			$data[$date] = 0;
		}
		// 将交易记录按日期对应合并到日期数据
		foreach ($results as $value) {
			if (in_array($value->status, ['pending', 'refunded', 'cancelled'])) {
				continue;
			}

			if (!$value) {
				continue;
			}

			$day = wnd_date('m-d', $value->time);
			if (!isset($data[$day])) {
				continue;
			}

			$props = json_decode($value->props);
			if (!$props) {
				continue;
			}

			// 匿名充值订单中 $value->total_amount 会随着用户消耗而减少，故从订单属性中读取原始数据
			if ('recharge' == $type) {
				$custom_total_amount = $props->custom_total_amount ?? 0;
				$total_amount        = $custom_total_amount ?: ($props->total_amount ?? 0);
				$total_amount        = floatval($total_amount);
			} else {
				$total_amount = $value->total_amount;
			}

			$data[$day] = number_format(($data[$day] + $total_amount), 2, '.', '');
		}

		// 组成最终数据格式
		$result = [
			'categories' => [],
			'series'     => [
				[
					'name' => $type,
					'data' => [],
				],
			],
			'total'      => number_format(array_sum($data), 2, '.'),
		];
		foreach ($data as $date => $value) {
			$result['categories'][]        = $date;
			$result['series'][0]['data'][] = $value;
		}

		return $result;
	}
}
