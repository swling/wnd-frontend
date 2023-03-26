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
	}
}
