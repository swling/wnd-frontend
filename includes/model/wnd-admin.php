<?php
namespace Wnd\Model;

use Wnd\Model\Wnd_DB;

/**
 * 清理站点内容
 * @since 2019.3.14
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
		 * 关闭WordPress缩略图裁剪
		 * @since 2019.06.17
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
		 * @since 0.9.18
		 */
		flush_rewrite_rules();
	}

	/**
	 * 卸载插件
	 * @since 初始化
	 */
	public static function uninstall() {
		// delete_option('wnd');
		return;
	}

	/**
	 * 清理数据
	 *
	 */
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
			"SELECT ID FROM $wpdb->posts WHERE post_type IN ('order','recharge') AND post_status = 'wnd-pending' AND DATE_SUB(NOW(), INTERVAL 7 DAY) > post_date"
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
	 * 升级
	 * @since 2020.08.19
	 */
	public static function upgrade() {
		$db_version = get_option('wnd_ver');
		if (version_compare($db_version, WND_VER) >= 0) {
			return;
		}

		// 提取所有版本升级方法：以"v_"为前缀的方法，并做版本对比确定是否执行
		$reflection      = new \ReflectionClass(__CLASS__);
		$methods         = $reflection->getMethods(\ReflectionMethod::IS_STATIC);
		$upgrade_methods = [];
		foreach ($methods as $method) {
			if (0 !== stripos($method->name, 'v_')) {
				continue;
			}

			// 将升级方法名称，转为与之匹配的版本号（v_0_9_57 =>v.0.9.57）
			$method_version = str_replace('_', '.', $method->name);
			if (version_compare('v.' . $db_version, $method_version) >= 0) {
				continue;
			}

			// 写入数组存储，因为升级方法的执行顺序非常重要，保险起见，存入数组后并排序后，再循环执行
			$upgrade_methods[] = $method->class . '::' . $method->name;
		}

		// 方法排序并执行升级
		asort($upgrade_methods);
		foreach ($upgrade_methods as $upgrade_method) {
			$upgrade_method();
		}

		update_option('wnd_ver', WND_VER);
	}

	private static function v_0_9_29() {
		wnd_delete_option('wnd', 'alipay_sandbox');
	}

	private static function v_0_9_30() {
		if ('COS' == wnd_get_option('wnd', 'oss_sp')) {
			wnd_update_option('wnd', 'oss_sp', 'Qcloud');
		} elseif ('OSS' == wnd_get_option('wnd', 'oss_sp')) {
			wnd_update_option('wnd', 'oss_sp', 'Aliyun');
		}

		if ('tencent' == wnd_get_option('wnd', 'captcha_service')) {
			wnd_update_option('wnd', 'captcha_service', 'Qcloud');
		} elseif ('aliyun' == wnd_get_option('wnd', 'captcha_service')) {
			wnd_update_option('wnd', 'captcha_service', 'Aliyun');
		}

		if ('tencent' == wnd_get_option('wnd', 'sms_sp')) {
			wnd_update_option('wnd', 'sms_sp', 'Qcloud');
		} elseif ('aliyun' == wnd_get_option('wnd', 'sms_sp')) {
			wnd_update_option('wnd', 'sms_sp', 'Aliyun');
		}
	}

	private static function v_0_9_38() {
		$enable_cdn = wnd_get_option('wnd', 'cdn_enable');
		wnd_delete_option('wnd', 'cdn_enable');
		wnd_update_option('wnd', 'enable_cdn', $enable_cdn);

		$enable_oss = wnd_get_option('wnd', 'oss_enable');
		wnd_delete_option('wnd', 'oss_enable');
		wnd_update_option('wnd', 'enable_oss', $enable_oss);
	}

	// 新增用户日志表，需要升级数据表
	private static function v_0_9_57() {
		Wnd_DB::create_table();

		// 将旧的用户余额转移至新表（之所有需要将用户余额作为新表存储，是为了定期清理余额为零的睡眠账户）
		global $wpdb;
		$results = $wpdb->get_results("SELECT ID FROM $wpdb->users");
		foreach ($results as $user) {
			$user_id   = $user->ID;
			$old_money = floatval(wnd_get_user_meta($user_id, 'money'));
			if (!$old_money) {
				wnd_delete_user_meta($user_id, 'money');
				continue;
			}

			// 转移
			$action = \Wnd\Model\Wnd_Finance::inc_user_money($user_id, $old_money, false);
			if ($action) {
				wnd_delete_user_meta($user_id, 'money');
			}
		}
	}
}
