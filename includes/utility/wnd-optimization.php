<?php
namespace Wnd\Utility;

use Wnd\Utility\Wnd_Singleton_Trait;

/**
 * 初始化 优化
 */
class Wnd_Optimization {

	use Wnd_Singleton_Trait;

	public function __construct() {
		// 用户注册时，移除部分冗余wp user meta
		add_filter('insert_user_meta', [__CLASS__, 'unset_user_meta'], 10, 2);

		/**
		 * 禁止WordPress admin bar
		 * @since 2019.03.01
		 */
		add_filter('show_admin_bar', '__return_false');

		// 邮件名称
		add_filter('wp_mail_from_name', [__CLASS__, 'filter_mail_from_name']);

		// 404 SEO
		add_filter('redirect_canonical', [__CLASS__, 'filter_redirect_canonical']);

		// 禁用WP默认注册登录
		add_action('admin_init', [__CLASS__, 'redirect_non_admin_users']);
		add_action('login_init', [__CLASS__, 'redirect_login_form_register']);

		/**
		 * 本插件设置了自动草稿重用机制，故此无需删除自动草稿
		 * @since 2019.07.09 移除WordPress定时自动删除“自动草稿”
		 */
		remove_action('wp_scheduled_auto_draft_delete', 'wp_delete_auto_drafts');

		/**
		 * @since 2019.01.26 语言包
		 * @see load_textdomain() apply_filters( 'override_load_textdomain', false, $domain, $mofile );
		 */
		if (!is_admin() and wnd_get_config('disable_locale')) {
			add_filter('override_load_textdomain', function ($override, $domain): bool {
				if ('default' == $domain) {
					return true;
				}

				return $override;
			}, 10, 2);
		}

	}

	/**
	 * @link https://developer.wordpress.org/reference/hooks/insert_user_meta/
	 * @since 2019.02.14 当仅允许用户在前端操作时，可注销一些字段，降低wp_usermeta数据库开销
	 */
	public static function unset_user_meta($meta, $user) {
		// 排除超级管理员
		if (is_super_admin($user->ID)) {
			return $meta;
		}

		unset($meta['nickname']);
		unset($meta['first_name']);
		unset($meta['last_name']);
		unset($meta['syntax_highlighting']);
		unset($meta['rich_editing']);
		unset($meta['comment_shortcuts']); //评论快捷方式
		unset($meta['admin_color']);
		unset($meta['use_ssl']);
		unset($meta['show_admin_bar_front']);
		unset($meta['locale']);

		return $meta;
	}

	/**
	 * 修改通知系统邮件发件人名称“WordPress”为博客名称
	 * @since 2019.03.28
	 */
	public static function filter_mail_from_name($email) {
		return get_option('blogname');
	}

	/**
	 * @since 2019.1.14 移除错误网址的智能重定向，智能重定向可能会导致百度收录及改版校验等出现问题
	 */
	public static function filter_redirect_canonical($redirect_url) {
		if (is_404()) {
			return false;
		}
		return $redirect_url;
	}

	/**
	 * 禁止WordPress原生登录
	 * @since 2019.03.01
	 */
	public static function redirect_non_admin_users() {
		if (!is_super_admin() and false === strpos($_SERVER['PHP_SELF'], 'admin-ajax.php')) {
			wp_redirect(home_url('?from=wp-admin'));
			exit;
		}
	}

	/**
	 * 禁止WordPress原生注册
	 * @since 2019.03.01
	 */
	public static function redirect_login_form_register() {
		$action = $_REQUEST['action'] ?? '';
		if ('logout' == $action) {
			return;
		}

		wp_redirect(home_url('?from=wp-login.php'));
		exit(); // always call `exit()` after `wp_redirect`
	}

}
