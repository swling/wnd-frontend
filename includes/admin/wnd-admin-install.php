<?php
namespace Wnd\Admin;

use Wnd\Model\Wnd_DB;

/**
 * 清理站点内容
 * @since 2019.3.14
 */
class Wnd_Admin_Install {

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
}
