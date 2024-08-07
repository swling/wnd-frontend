<?php
namespace Wnd\Admin;

use Wnd\Model\Wnd_Transaction;
use Wnd\WPDB\Wnd_Mail_DB;

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

		// 一年前的站内信
		$old_mails = $wpdb->get_col("SELECT ID FROM $wpdb->wnd_mails WHERE DATE_SUB(NOW(), INTERVAL 365 DAY) > FROM_UNIXTIME(sent_at)");
		foreach ((array) $old_mails as $delete) {
			Wnd_Mail_DB::get_instance()->delete($delete);
		}

		do_action('wnd_clean_up');
		return true;
	}

}
