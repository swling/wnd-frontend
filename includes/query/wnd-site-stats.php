<?php

namespace Wnd\Query;

use Exception;
use Wnd\Model\Wnd_Transaction;
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

	protected static function query($args = []): array{
		$type  = $args['type'] ?? 'order';
		$range = $args['range'] ?? 'year';

		if (in_array($type, ['order', 'recharge'])) {
			return static::get_finance_stats($type, $range);
		}
	}

	// 查询财务统计数据
	private static function get_finance_stats($type, $range): array{
		// 年度统计直接查询每月统计数据
		if ('year' == $range) {
			return static::get_annual_finance_stats($type);
		}

		// 周月统计需要按天查询所有订单后合并计算
		if (in_array($range, ['week', 'month'])) {
			return static::get_finance_stats_by_day($type, $range);
		}
	}

	// 财务数据：年度消费/充值统计
	private static function get_annual_finance_stats($type): array{
		$result = [
			'categories' => ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'],
			'series'     => [],
		];
		$year = date('Y', current_time('U'));

		/**
		 * 今年
		 * 去年
		 */
		$result['series'][] = static::query_annual_finance_data($type, $year);
		// $result['series'][] = static::query_annual_finance_data($type, $year-1);

		return $result;
	}

	private static function query_annual_finance_data($type, $year): array{
		$args = [
			'date_query'             => [
				[
					'year' => $year,
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

		$data = [
			'name' => 'date:' . $year,
			'data' => array_fill(0, 12, 0.00),
		];
		// 数据记录的月份-1 后对应插入数据（不能直接循环生成，因为可能存在某个月没有任何数据，引起错配）
		foreach ($posts as $post) {
			$month              = date('n', strtotime($post->post_date));
			$key                = $month - 1;
			$data['data'][$key] = number_format($post->post_content, 2, '.');
		}

		return $data;
	}

	// 财务数据：周度/月度财务数据
	private static function get_finance_stats_by_day($type, $range) {
		$result = [
			'categories' => 'week' == $range ? range(1, 7) : range(1, 31),
			'series'     => [],
		];

		$result['series'][] = static::query_daily_finance_data($type, $range);

		return $result;
	}

	private static function query_daily_finance_data($type, $range): array{
		$date_query = ['year' => date('Y', current_time('U'))];
		if ('week' == $range) {
			$date_query['week'] = date('W', current_time('U'));
		} else {
			$date_query['month'] = date('n', current_time('U'));
		}
		$args = [
			'date_query'             => $date_query,
			'post_type'              => $type,
			'post_status'            => [Wnd_Transaction::$completed_status, Wnd_Transaction::$closed_status],
			'posts_per_page'         => -1,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		];
		$query = new WP_Query($args);
		$posts = $query->get_posts();

		$days = 'week' == $range ? 7 : 31;
		$data = [
			'name' => 'this ' . $range,
			'data' => array_fill(0, $days, 0.00),
		];

		// 数据记录 星期/阳历 -1 后对应插入并累积数据（不能直接循环生成，因为可能存在某个月没有任何数据，引起错配）
		$date_key = 'week' == $range ? 'N' : 'j';
		foreach ($posts as $post) {
			$day                = date($date_key, strtotime($post->post_date));
			$key                = $day - 1;
			$data['data'][$key] = number_format(($data['data'][$key] + $post->post_content), 2, '.');
		}

		return $data;
	}

}
