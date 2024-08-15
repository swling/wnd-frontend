<?php
namespace Wnd\Model;

use Wnd\Utility\Wnd_Singleton_Trait;

/**
 * 数据表
 * @since 2019.01.24
 */
class Wnd_DB {

	use Wnd_Singleton_Trait;

	private function __construct() {
		global $wpdb;

		// 用户验证
		$wpdb->wnd_auths = $wpdb->prefix . 'wnd_auths';

		// 标签关联分类
		$wpdb->wnd_terms = $wpdb->prefix . 'wnd_terms';

		// 用户表 @since 0.9.56.7
		$wpdb->wnd_users = $wpdb->prefix . 'wnd_users';

		// 交易数据表 @since 0.9.67
		$wpdb->wnd_transactions = $wpdb->prefix . 'wnd_transactions';

		// 站内信 @since 0.9.73
		$wpdb->wnd_mails = $wpdb->prefix . 'wnd_mails';

		$wpdb->wnd_analyses = $wpdb->prefix . 'wnd_analyses';
	}

	/**
	 * 创建插件数据表
	 * @since 2019.01.24
	 */
	public static function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require ABSPATH . 'wp-admin/includes/upgrade.php';

		// 创建标签关联分类数据库
		$create_terms_sql = "CREATE TABLE IF NOT EXISTS $wpdb->wnd_terms (
        ID bigint(20) NOT NULL auto_increment,
        cat_id bigint(20) NOT NULL,
        tag_id bigint(20) NOT NULL,
        tag_taxonomy varchar(32) NOT NULL,
        count bigint(20) NOT NULL,
        PRIMARY KEY (ID),
        UNIQUE KEY cat_tag(cat_id,tag_id)

        ) $charset_collate;";
		dbDelta($create_terms_sql);

		// 创建用户身份验证数据库
		$create_auths_sql = "CREATE TABLE IF NOT EXISTS $wpdb->wnd_auths (
			ID bigint(20) NOT NULL auto_increment,
			user_id bigint(20) NOT NULL,
			identifier varchar(100) NOT NULL,
			type varchar(32) NOT NULL,
			credential varchar(64) NOT NULL,
			time bigint(20) NOT NULL,
			PRIMARY KEY (ID),
			KEY user_id(user_id),
			UNIQUE KEY identifier(identifier,type)
			) $charset_collate;";
		dbDelta($create_auths_sql);

		/**
		 * 创建自定义用户数据库
		 * @link https://stackoverflow.com/questions/13030368/best-data-type-to-store-money-values-in-mysql
		 * @since 0.9.56.7
		 */
		$create_users_sql = "CREATE TABLE IF NOT EXISTS $wpdb->wnd_users (
			ID bigint(20) NOT NULL auto_increment,
			user_id bigint(20) NOT NULL,
			balance decimal(10, 2) NOT NULL,
			expense decimal(10, 2) NOT NULL,
			last_login bigint(20) NOT NULL,
			login_count bigint(20) NOT NULL,
			last_recall bigint(20) NOT NULL,
			client_ip varchar(100) NOT NULL,
			PRIMARY KEY (ID),
			UNIQUE KEY user_id(user_id),
			KEY last_login(last_login),
			KEY login_count(login_count),
			KEY last_recall(last_recall)
			) $charset_collate;";
		dbDelta($create_users_sql);

		/**
		 * 创建自定义交易数据库
		 * @since 0.9.67
		 */
		$create_users_sql = "CREATE TABLE IF NOT EXISTS $wpdb->wnd_transactions (
			ID bigint(20) NOT NULL auto_increment,
			object_id bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL,
			type varchar(32) NOT NULL,
			total_amount decimal(10, 2) NOT NULL,
			payment_gateway varchar(32) NOT NULL,
			status varchar(16) NOT NULL,
			subject varchar(100) NOT NULL,
			slug varchar(100) NOT NULL,
			time bigint(20) NOT NULL,
			props json NOT NULL,
			PRIMARY KEY (ID),
			UNIQUE KEY slug(slug),
			KEY user_id(user_id),
			KEY object_uts(object_id, user_id, type, status),
			KEY time(time)
			) $charset_collate;";
		dbDelta($create_users_sql);

		/**
		 * 创建站内信
		 * @since 0.9.73
		 */
		$create_users_sql = "CREATE TABLE IF NOT EXISTS $wpdb->wnd_mails (
			`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,  -- 消息ID，主键，自增
    		`sender` BIGINT UNSIGNED NOT NULL,             -- 发送者ID
    		`receiver` BIGINT UNSIGNED NOT NULL,           -- 接收者ID
    		`subject` VARCHAR(255) NOT NULL,               -- 消息主题
    		`content` TEXT NOT NULL,                       -- 消息内容
    		`sent_at` bigint(20) NOT NULL,                 -- 发送时间，默认当前时间
    		`read_at` bigint(20) NOT NULL,                 -- 阅读时间，默认为NULL，表示未读
    		`status` ENUM('unread', 'read', 'deleted') DEFAULT 'unread',  -- 消息状态：未读，已读，删除
    		PRIMARY KEY (`ID`),                            -- 设置主键
    		INDEX `receiver` (`receiver`),                 -- 为接收者ID添加索引，方便查询
    		INDEX `sender` (`sender`)                      -- 为发送者ID添加索引，方便查询
			) $charset_collate;";
		dbDelta($create_users_sql);

		/**
		 * 创建 posts 分析数据表
		 * @since 0.9.73
		 */
		$create_users_sql = "CREATE TABLE IF NOT EXISTS $wpdb->wnd_analyses (
			`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,  -- 消息ID，主键，自增
			`post_id` BIGINT(20) UNSIGNED NOT NULL,
			`today_views` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			`week_views` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			`month_views` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			`total_views` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			`favorites_count` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			`rating_score` FLOAT NOT NULL DEFAULT 0,
			`rating_count` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			`last_viewed_date` DATE NOT NULL DEFAULT '1970-01-01',
			PRIMARY KEY (`ID`),
			UNIQUE INDEX `post_id` (`post_id`),
			INDEX `today_views` (`today_views`),
			INDEX `week_views` (`week_views`),
			INDEX `month_views` (`month_views`),
			INDEX `total_views` (`total_views`),
			INDEX `favorites_count` (`favorites_count`),
			INDEX `rating_score` (`rating_score`),
			INDEX `last_viewed_date` (`last_viewed_date`),
			FOREIGN KEY (`post_id`) REFERENCES `wp_posts`(`ID`) ON DELETE CASCADE
			) $charset_collate;";
		dbDelta($create_users_sql);
	}
}
