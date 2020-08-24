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
				'wnd_static_host'               => 'local',
				'wnd_edit_page'                 => '',
				'wnd_agreement_url'             => '',
				'wnd_reg_redirect_url'          => '',
				'wnd_default_avatar_url'        => WND_URL . 'static/images/avatar.jpg',

				'wnd_max_upload_size'           => '2048',
				'wnd_max_stick_posts'           => '10',

				'wnd_disable_locale'            => '',

				'wnd_primary_color'             => '',
				'wnd_second_color'              => '',

				'wnd_commission_rate'           => '',
				'wnd_enable_anon_order'         => 0,

				'wnd_pay_return_url'            => get_option('home'),
				'wnd_alipay_appid'              => '',
				'wnd_alipay_private_key'        => '',
				'wnd_alipay_public_key'         => '',

				'wnd_disable_email_reg'         => 0,
				'wnd_disable_user_login'        => 0,

				'wnd_min_verification_interval' => '60',
				'wnd_sms_sp'                    => 'tx',
				'wnd_enable_sms'                => '短信接口appid',
				'wnd_sms_appid'                 => '短信接口appid',
				'wnd_sms_appkey'                => '短信接口appkey',
				'wnd_sms_sign'                  => get_option('blogname'),
				'wnd_sms_template_r'            => '注册短信模板ID',
				'wnd_sms_template_v'            => '身份验证短信模板ID',
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
			"DELETE FROM $wpdb->wnd_users WHERE user_id = 0 AND DATE_SUB(NOW(), INTERVAL 7 DAY) > FROM_UNIXTIME(time)"
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
		if (version_compare(get_option('wnd_var'), '0.8.61', '<')) {
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
				update_option('wnd_ver', WND_VER);

				wp_cache_flush();
			}
		}

		// 升级 0.8.62
		if (version_compare(get_option('wnd_var'), '0.8.62', '<')) {
			foreach (get_option('wnd') as $key => $value) {
				if ('wnd_app_private_key' == $key) {
					$key = 'alipay_app_private_key';
				} elseif (0 === stripos($key, 'wnd_')) {
					$key = substr($key, 4);
				}
				$option[$key] = $value;
			}
			update_option('wnd', $option);

			update_option('wnd_ver', WND_VER);

			wp_cache_flush();
		}
	}
}
