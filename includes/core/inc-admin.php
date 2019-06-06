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

/**
 *旧版wndwp 升级至 当前 wnd-frontend
 *@since 2019.04.30
 **/
function wnd_upgrade_02() {

	global $wpdb;

	// terms table
	if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}my_tag_under_cat'") == $wpdb->prefix . 'my_tag_under_cat') {
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wnd_terms");
		$wpdb->query("ALTER TABLE {$wpdb->prefix}my_tag_under_cat RENAME TO {$wpdb->prefix}wnd_terms");
	}

	// users table
	if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}my_sms'") == $wpdb->prefix . 'my_sms') {

		$users = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}my_sms WHERE 1 = 1");
		if ($users) {
			foreach ($users as $user) {

				$ID = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->wnd_users} WHERE phone = %s", $user->phone));
				if ($ID) {
					continue;
				}

				$wpdb->insert(
					$wpdb->wnd_users,
					array(
						'user_id' => $user->user_id,
						'phone' => $user->phone,
						'time' => $user->time,
					),
					array('%s', '%s', '%d')
				);
			}
		}

		if ($wpdb->get_results("SELECT * FROM {$wpdb->wnd_users} WHERE 1 = 1")) {
			$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}my_sms");
		}

	}

	// index
	// alter table table_name drop index index_name ;
	if ($wpdb->query("SHOW INDEX FROM {$wpdb->posts} WHERE Key_name = 'post_modified'")) {
		$wpdb->query("ALTER TABLE {$wpdb->posts} DROP INDEX post_modified");
	}

	if ($wpdb->query("SHOW INDEX FROM {$wpdb->comments} WHERE Key_name = 'user_id'")) {
		$wpdb->query("ALTER TABLE {$wpdb->comments} DROP INDEX user_id");
	}

	// meta
	$wpdb->update(
		$wpdb->postmeta,
		array(
			'meta_key' => 'wnd_meta', // string
		),
		array('meta_key' => 'my_post_meta')
	);

	$wpdb->update(
		$wpdb->usermeta,
		array(
			'meta_key' => 'wnd_meta', // string
		),
		array('meta_key' => 'my_user_meta')
	);

	// taxonomy
	$wpdb->update(
		$wpdb->term_taxonomy,
		array(
			'taxonomy' => 'company_cat', // string
		),
		array('taxonomy' => 'profile_cat')
	);

	$wpdb->update(
		$wpdb->term_taxonomy,
		array(
			'taxonomy' => 'company_tag', // string
		),
		array('taxonomy' => 'profile_tag')
	);

	$wpdb->update(
		$wpdb->term_taxonomy,
		array(
			'taxonomy' => 'region', // string
		),
		array('taxonomy' => 'area')
	);

	$wpdb->update(
		$wpdb->wnd_terms,
		array(
			'tag_taxonomy' => 'company_tag', // string
		),
		array('tag_taxonomy' => 'profile_tag')
	);

	wnd_copy_taxonomy('company_cat', 'people_cat');

	// post type
	$wpdb->update(
		$wpdb->posts,
		array(
			'post_type' => 'company', // string
		),
		array('post_type' => 'profile')
	);

	// options
	delete_option('my_wndwp');
}
