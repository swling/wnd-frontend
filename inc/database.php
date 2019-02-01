<?php
/**
 *@since 2019.01.24 WndWP所需独立数据表
 */

global $wpdb;
// 短信
$wpdb->wnd_sms = $wpdb->prefix . 'wnd_sms';
// 标签关联分类
$wpdb->wnd_term = $wpdb->prefix . 'wnd_term';
// 支付
$wpdb->wnd_payment = $wpdb->prefix . 'wnd_payment';

/**
 *@since 2019.01.24
 *创建插件数据表
 */
function wnd_create_table() {

	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	require ABSPATH . 'wp-admin/includes/upgrade.php';

	// 创建短信数据库
	$create_sms_sql = "CREATE TABLE IF NOT EXISTS $wpdb->wnd_sms (

			ID bigint(20) NOT NULL auto_increment,
			phone varchar(14) NOT NULL,
			code char(4) NOT NULL,
			time bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL,
			-- reg tinyint(1)  NOT NULL,
			PRIMARY KEY (ID),
			KEY phone(phone),
			KEY user_id(user_id)

			) $charset_collate;";

	dbDelta($create_sms_sql);

	// 创建标签关联分类数据库
	$create_tag_under_cat_sql = "CREATE TABLE IF NOT EXISTS $wpdb->wnd_term (
        ID bigint(20) NOT NULL auto_increment,
        cat_id bigint(20) NOT NULL,
        tag_id bigint(20) NOT NULL,
        tag_taxonomy varchar(32) NOT NULL,
        count bigint(20) NOT NULL,
        PRIMARY KEY (ID),
        UNIQUE KEY cat_tag(cat_id,tag_id)

        ) $charset_collate;";
	dbDelta($create_tag_under_cat_sql);

	/**
	 * @since 2019.01.30 支付订单数据库
	 */
	$create_payment_sql = "CREATE TABLE IF NOT EXISTS $wpdb->wnd_payment (
        ID bigint(20) NOT NULL auto_increment,
        post_id bigint(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        price DECIMAL(6,2) NOT NULL,
        time bigint(20) NOT NULL,
        status varchar(20) NOT NULL,
        type varchar(20) NOT NULL,
        PRIMARY KEY (ID),
        KEY post_id(post_id),
        KEY user_id(user_id)

        ) $charset_collate;";
	dbDelta($create_payment_sql);

}
