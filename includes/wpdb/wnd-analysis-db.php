<?php

namespace Wnd\WPDB;

use Wnd\Utility\Wnd_Singleton_Trait;
use Wnd\WPDB\WPDB_Row;

/**
 * 自定义站内信息
 * @since 0.9.73
 *
 *		`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,  -- 消息ID，主键，自增
 *		`post_id` BIGINT(20) UNSIGNED NOT NULL,
 *		`today_views` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
 *		`week_views` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
 *		`month_views` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
 *		`total_views` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
 *		`favorites_count` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
 *		`rating_score` FLOAT NOT NULL DEFAULT 0,
 *		`rating_count` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
 *		`last_viewed_date` DATE NOT NULL DEFAULT '1970-01-01',
 *		PRIMARY KEY (`ID`),
 *		INDEX `post_id` (`post_id`),
 *		INDEX `today_views` (`today_views`),
 *		INDEX `week_views` (`week_views`),
 *		INDEX `month_views` (`month_views`),
 *		INDEX `total_views` (`total_views`),
 *		INDEX `favorites_count` (`favorites_count`),
 *		INDEX `rating_score` (`rating_score`),
 *		INDEX `last_viewed_date` (`last_viewed_date`),
 *		FOREIGN KEY (`post_id`) REFERENCES `wp_posts`(`ID`) ON DELETE CASCADE
 */
class Wnd_Analysis_DB extends WPDB_Row {

	protected $table_name        = 'wnd_analyses';
	protected $object_name       = 'wnd_analysis';
	protected $primary_id_column = 'ID';
	protected $required_columns  = ['post_id', 'last_viewed_date'];

	protected $object_cache_fields = ['post_id'];

	/**
	 * 单例模式
	 */
	use Wnd_Singleton_Trait;

	private function __construct() {
		parent::__construct();
	}

	public function update_post_views($post_id) {
		// 如果数据库中也没有记录，初始化视图数据
		$views_data = $this->get_by('post_id', $post_id);
		$views_data = $views_data ? (array) $views_data : $views_data;
		if (!$views_data) {
			$views_data = [
				'today_views'      => 0,
				'week_views'       => 0,
				'month_views'      => 0,
				'total_views'      => 0,
				'last_viewed_date' => date('Y-m-d', 0),
			];
		}

		// 当天首次浏览：可能需要重置周月统计
		$current_date = wnd_date('Y-m-d');
		if ($views_data['last_viewed_date'] != $current_date) {
			return $this->update_first_daily_views($post_id, $views_data);
		} else {
			return $this->update_views($post_id, $views_data);
		}
	}

	/**
	 * 非当日首次浏览
	 * - 对象缓存开启的情况下，将缓存后批量写入数据库
	 * - 未开启对象缓存时，将直接写入数据库
	 *
	 * ## 开启对象缓存，批量入库可能存在的问题：
	 * 在批量入库的情况下，日、周、月浏览可能存在一定偏差，以日数据为例：
	 *
	 * 每日零点，昨日已缓存未入库的数据，将会在下一次批量入库时被写入。
	 * - 若写入时，对应 post_id  的 update_first_daily_views 尚未执行，
	 *   则此部分数据将在  update_first_daily_views 执行时被重置。即丢失昨日部分数据。
	 *
	 * - 若写入时，对应 post_id  的 update_first_daily_views 已执行，
	 *   则此部分昨日数据，将被累计到今日数据。
	 *
	 * 周数据，月数据同理。数据偏移量将取决于批量入库条件。
	 * 总浏览量不受影响
	 *
	 * @see 开发者认为：为了降数据写入、提升系统性能，日、周、月浏览量存在小范围偏差，是可接受的。
	 *
	 * @since 0.9.75
	 */
	private function update_views($postID, $views_data) {
		if (empty($postID)) {
			return;
		}

		// 缓存浏览
		$cache_key   = 'views';
		$cache_group = 'wnd_post_views';
		$cache_data  = wp_cache_get($cache_key, $cache_group) ?: [];

		// 缓存计数器
		$cache_count_key = 'cache_count';
		$cache_count     = wp_cache_get($cache_count_key, $cache_group) ?: 0;
		wp_cache_set($cache_count_key, $cache_count + 1, $cache_group);

		// 获取当前日期
		$current_date = wnd_date('Y-m-d');

		// 将当前浏览 写入/更新 到缓存集合
		$id_key              = "id_$postID";
		$count               = isset($cache_data[$id_key]) ? $cache_data[$id_key]['count'] + 1 : 1;
		$cache_data[$id_key] = ['post_id' => $postID, 'count' => $count, 'date' => $current_date];
		wp_cache_set($cache_key, $cache_data, $cache_group);

		// 设置一个阈值或条件来决定何时写入数据库 wp_using_ext_object_cache()
		if ($cache_count < 10 and wp_using_ext_object_cache()) {
			return;
		}

		$sql_values = '';
		foreach ($cache_data as $value) {
			if ($sql_values) {
				$sql_values .= ', ';
			}

			$sql_values .= sprintf('(%d, %d, %d, %d, %d)', $value['post_id'], $value['count'], $value['count'], $value['count'], $value['count']);

			// 清理单条记录缓存
			$post_id       = $value['post_id'];
			$object_before = $this->get_by('post_id', $post_id);
			if (!$object_before) {
				continue;
			}
			$this->cache->clean_row_cache($object_before);
		}

		global $wpdb;
		$wpdb->query(
			"INSERT INTO {$wpdb->wnd_analyses} (post_id, today_views, week_views, month_views, total_views)
				VALUES {$sql_values}
				ON DUPLICATE KEY UPDATE
					post_id = VALUES(post_id),
					today_views = today_views + VALUES(today_views),
					week_views = week_views + VALUES(week_views),
					month_views = month_views + VALUES(month_views),
					total_views = total_views + VALUES(total_views);
			",
		);

		// 同步数据库后，可以清除缓存集合
		wp_cache_delete($cache_key, $cache_group);

		// 重置缓存技术
		wp_cache_delete($cache_count_key, $cache_group);
	}

	private function update_first_daily_views($post_id, $views_data): bool {
		// 获取当前日期
		$current_date   = wnd_date('Y-m-d');
		$start_of_week  = wnd_date('Y-m-d', strtotime('monday this week'));
		$start_of_month = wnd_date('Y-m-01');

		// 根据日期判断是否重置
		if ($views_data['last_viewed_date'] == $current_date) {
			return false;
		}

		// 重置今日
		$views_data['today_views'] = 0;

		// 可能需要重置本周
		if ($views_data['last_viewed_date'] < $start_of_week) {
			$views_data['week_views'] = 0;
		}

		// 可能需要重置本月
		if ($views_data['last_viewed_date'] < $start_of_month) {
			$views_data['month_views'] = 0;
		}

		// 更新视图数据
		$views_data['today_views']++;
		$views_data['week_views']++;
		$views_data['month_views']++;
		$views_data['total_views']++;
		$views_data['last_viewed_date'] = $current_date;
		$views_data['post_id']          = $post_id;

		// insert / update
		$this->insert($views_data);
		return true;
	}

	public function update_rating_score($postID, $new_rating) {
		global $wpdb;
		$table_name = $wpdb->wnd_analyses;

		// 获取当前评分和评分次数
		$current_score_data = $wpdb->get_row(
			$wpdb->prepare("SELECT rating_score, rating_count FROM $table_name WHERE post_id = %d", $postID),
			ARRAY_A
		);

		// 如果还没有评分，则初始化
		if (!$current_score_data) {
			$current_score_data = ['rating_score' => 0, 'rating_count' => 0];
		}

		$new_total_score  = ($current_score_data['rating_score'] * $current_score_data['rating_count']) + $new_rating;
		$new_rating_count = $current_score_data['rating_count'] + 1;
		$new_rating_score = $new_total_score / $new_rating_count;

		// 更新评分
		$wpdb->update(
			$table_name,
			['rating_score' => $new_rating_score, 'rating_count' => $new_rating_count],
			['post_id' => $postID],
			['%f', '%d'],
			['%d']
		);
	}

	public function update_favorites_count($postID, $action) {
		global $wpdb;
		$table_name = $wpdb->wnd_analyses;

		// 获取当前收藏数
		$current_favorites_count = $wpdb->get_var(
			$wpdb->prepare("SELECT favorites_count FROM $table_name WHERE post_id = %d", $postID)
		);

		// 更新收藏数
		if ($action == 'add') {
			$new_favorites_count = $current_favorites_count + 1;
		} elseif ($action == 'remove') {
			$new_favorites_count = max(0, $current_favorites_count - 1); // 避免负数
		}

		$wpdb->update(
			$table_name,
			['favorites_count' => $new_favorites_count],
			['post_id' => $postID],
			['%d'],
			['%d']
		);
	}
}
