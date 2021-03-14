<?php
namespace Wnd\Model;

use Wnd\Model\Wnd_DB;

/**
 *@since 2019.3.14
 *清理站点内容
 */
class Wnd_Admin {

	public static function install() {
		// 数据表
		Wnd_DB::create_table();

		// 默认option数据
		if (!get_option('wnd')) {
			$default_option = [
				'static_host'               => 'local',
				'front_page'                => '',
				'agreement_url'             => '',
				'reg_redirect_url'          => '',
				'default_avatar_url'        => WND_URL . 'static/images/avatar.jpg',

				'max_upload_size'           => '2048',
				'max_stick_posts'           => '10',

				'disable_locale'            => '',

				'primary_color'             => '',
				'second_color'              => '',

				'commission_rate'           => '',
				'enable_anon_order'         => 0,

				'pay_return_url'            => get_option('home'),
				'alipay_appid'              => '',
				'alipay_private_key'        => '',
				'alipay_public_key'         => '',

				'disable_email_reg'         => 0,
				'disable_user_login'        => 0,

				'min_verification_interval' => '60',
				'sms_sp'                    => 'tx',
				'enable_sms'                => '短信接口appid',
				'sms_appid'                 => '短信接口appid',
				'sms_appkey'                => '短信接口appkey',
				'sms_sign'                  => get_option('blogname'),
				'sms_template_r'            => '注册短信模板ID',
				'sms_template_v'            => '身份验证短信模板ID',
			];

			update_option('wnd', $default_option);
		}

		// 版本
		update_option('wnd_ver', WND_VER);

		/**
		 * @since 2019.06.17
		 *关闭WordPress缩略图裁剪
		 */
		update_option('medium_large_size_w', 0);
		update_option('medium_large_size_h', 0);

		update_option('thumbnail_size_w', 0);
		update_option('thumbnail_size_h', 0);

		update_option('medium_size_w', 0);
		update_option('medium_size_h', 0);

		update_option('large_size_w', 0);
		update_option('large_size_h', 0);

		/**
		 *@since 0.9.18
		 */
		flush_rewrite_rules();
	}

	/**
	 *@since 初始化
	 *卸载插件
	 */
	public static function uninstall() {
		// delete_option('wnd');
		return;
	}

	/**
	 *清理数据
	 **/
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

		// 一年前的非产品订单
		$old_posts = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'order' AND post_parent = 0 AND DATE_SUB(NOW(), INTERVAL 365 DAY) > post_date");
		foreach ((array) $old_posts as $delete) {
			// Force delete.
			wp_delete_post($delete, true);
		}

		// 一年前的充值记录
		$old_posts = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'recharge' AND DATE_SUB(NOW(), INTERVAL 365 DAY) > post_date");
		foreach ((array) $old_posts as $delete) {
			// Force delete.
			wp_delete_post($delete, true);
		}

		// 超期七天未完成的充值消费订单
		$old_posts = $wpdb->get_col(
			"SELECT ID FROM $wpdb->posts WHERE post_type IN ('order','recharge') AND post_status = 'wnd-processing' AND DATE_SUB(NOW(), INTERVAL 7 DAY) > post_date"
		);
		foreach ((array) $old_posts as $delete) {
			// Force delete.
			wp_delete_post($delete, true);
		}

		// 删除七天以前未注册的验证码记录
		$old_users = $wpdb->query(
			"DELETE FROM $wpdb->wnd_auths WHERE user_id = 0 AND DATE_SUB(NOW(), INTERVAL 7 DAY) > FROM_UNIXTIME(time)"
		);

		do_action('wnd_clean_up');
		return true;
	}

	/**
	 *@since 2020.08.19
	 *升级
	 */
	public static function upgrade() {
		global $wpdb;
		// 升级 0.8.61
		if (version_compare(get_option('wnd_ver'), '0.8.61', '<')) {
			$posts = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_status IN ( 'success', 'close')");
			foreach ((array) $posts as $post) {
				$update = $wpdb->update(
					$wpdb->posts,
					['post_status' => 'success' == $post->post_status ? 'wnd-completed' : 'wnd-closed'],
					['ID' => $post->ID],
					['%s', '%s'],
					['%d']
				);
			}unset($posts, $post);

			if ($update ?? false) {
				update_option('wnd_ver', '0.8.61');

				wp_cache_flush();
			}
		}

		// 升级 0.8.62
		if (version_compare(get_option('wnd_ver'), '0.8.62', '<')) {
			foreach (get_option('wnd') as $key => $value) {
				if ('wnd_app_private_key' == $key) {
					$key = 'alipay_app_private_key';
				} elseif (0 === stripos($key, 'wnd_')) {
					$key = substr($key, 4);
				}
				$option[$key] = $value;
			}
			update_option('wnd', $option);

			update_option('wnd_ver', '0.8.62');

			wp_cache_flush();
		}

		// 升级 0.8.73
		if (version_compare(get_option('wnd_ver'), '0.8.73', '<')) {
			foreach (get_option('wnd') as $key => $value) {
				if ('edit_page' == $key) {
					$key = 'ucenter_page';
				}
				$option[$key] = $value;
			}
			update_option('wnd', $option);

			update_option('wnd_ver', '0.8.73');

			wp_cache_flush();
		}

		// 升级 0.9.0：采用自定义站内信状态
		if (version_compare(get_option('wnd_ver'), '0.9.0', '<')) {
			$posts = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_type = 'mail' AND post_status IN ( 'pending', 'private')");
			foreach ((array) $posts as $post) {
				$update = $wpdb->update(
					$wpdb->posts,
					['post_status' => 'pending' == $post->post_status ? 'wnd-unread' : 'wnd-unread'],
					['ID' => $post->ID],
					['%s', '%s'],
					['%d']
				);
			}unset($posts, $post);

			if ($update ?? false) {
				update_option('wnd_ver', '0.9.0');

				wp_cache_flush();
			}
		}

		// 升级 0.9.2：重写用户验证数据表
		if (version_compare(get_option('wnd_ver'), '0.9.2', '<')) {
			ini_set('max_execution_time', 0); //秒为单位，自己根据需要定义

			function insert_auths($user_id, $identifier, $type) {
				global $wpdb;

				// 已写入
				$data = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT * FROM $wpdb->wnd_auths WHERE identifier = %s AND type = %s",
						$identifier, $type
					)
				);
				if ($data) {
					return false;
				}

				// 新记录
				return $wpdb->insert(
					$wpdb->wnd_auths,
					['user_id' => $user_id, 'identifier' => $identifier, 'type' => $type, 'credential' => '', 'time' => time()],
					['%d', '%s', '%s']
				);
			}

			// 创建数据表
			Wnd_DB::create_table();

			global $wpdb;
			$wpdb->wnd_users = $wpdb->prefix . 'wnd_users';
			$wpdb->wnd_auths = $wpdb->prefix . 'wnd_auths';

			$users = $wpdb->get_results("SELECT * FROM $wpdb->wnd_users WHERE id != 0;");
			foreach ($users as $user) {
				if ($user->email) {
					$do = insert_auths($user->user_id, $user->email, 'email');
				}

				if ($user->phone) {
					$do = insert_auths($user->user_id, $user->phone, 'phone');
				}

				if ($user->open_id) {
					$type = (32 == strlen($user->open_id)) ? 'qq' : 'google';
					$do   = insert_auths($user->user_id, $user->open_id, $type);
				}
			}unset($users, $user);

			$wpdb->query("DROP TABLE IF EXISTS $wpdb->wnd_users");
			update_option('wnd_ver', '0.9.2');
			wp_cache_flush();
		}

		// 升级 0.9.26
		if (version_compare(get_option('wnd_ver'), '0.9.26', '<')) {
			foreach (get_option('wnd') as $key => $value) {
				if ('ucenter_page' == $key) {
					$key = 'front_page';
				}
				$option[$key] = $value;
			}
			update_option('wnd', $option);

			update_option('wnd_ver', '0.9.26');

			wp_cache_flush();
		}
	}
}
