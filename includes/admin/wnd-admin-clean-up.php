<?php
namespace Wnd\Admin;

use Wnd\Model\Wnd_Transaction;

/**
 * 清理站点内容
 * @since 2019.3.14
 */
class Wnd_Admin_Clean_UP {

	public static function clean_up() {
		if (!is_super_admin()) {
			return false;
		}
		global $wpdb;

		// 一年前的站内信
		$old_posts = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'mail' AND DATE_SUB(NOW(), INTERVAL 365 DAY) > post_date");
		foreach ((array) $old_posts as $delete) {
			// Force delete.
			wp_delete_post($delete, true);
		}

		// 一年前的充值/非产品订单
		$old_posts = $wpdb->get_col("SELECT ID FROM $wpdb->wnd_transactions WHERE object_id = 0 AND DATE_SUB(NOW(), INTERVAL 365 DAY) > FROM_UNIXTIME(time)");
		foreach ((array) $old_posts as $delete) {
			Wnd_Transaction::delete($delete);
		}

		// 超期七天未完成的充值消费订单
		$old_posts = $wpdb->get_col(
			"SELECT ID FROM $wpdb->wnd_transactions WHERE status = 'pending' AND DATE_SUB(NOW(), INTERVAL 7 DAY) > FROM_UNIXTIME(time)"
		);
		foreach ((array) $old_posts as $delete) {
			Wnd_Transaction::delete($delete);
		}

		// 删除七天以前未注册的验证码记录
		$old_users = $wpdb->query(
			"DELETE FROM $wpdb->wnd_auths WHERE user_id = 0 AND DATE_SUB(NOW(), INTERVAL 7 DAY) > FROM_UNIXTIME(time)"
		);

		do_action('wnd_clean_up');
		return true;
	}

}
