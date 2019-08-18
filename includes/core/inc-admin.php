<?php
/**
 *@since 2019.3.14
 *清理站点内容
 */
function wnd_clean_up() {
	if (!is_super_admin()) {
		return;
	}
	global $wpdb;

	// 一年前的站内信
	$old_posts = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'mail' AND DATE_SUB( NOW(), INTERVAL 365 DAY ) > post_date");
	foreach ((array) $old_posts as $delete) {
		// Force delete.
		wp_delete_post($delete, true);
	}

	// 超期七天未完成的充值消费订单
	$old_posts = $wpdb->get_col(
		"SELECT ID FROM $wpdb->posts WHERE post_type IN ('order','recharge') AND post_status = 'pending' AND DATE_SUB( NOW(), INTERVAL 7 DAY ) > post_date"
	);
	foreach ((array) $old_posts as $delete) {
		// Force delete.
		wp_delete_post($delete, true);
	}

	do_action('wnd_clean_up');
	return array('status' => 1, 'msg' => '清理完成');
}
