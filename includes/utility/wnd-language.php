<?php
namespace Wnd\Utility;

use Wnd\Getway\Wnd_Login_Social;
use Wnd\Utility\Wnd_Singleton_Trait;

/**
 *多语言
 *
 *
 *@since 2020.01.14
 */
class Wnd_language {

	public static $request_key = WND_LANG_KEY;

	/**
	 *@link https://en.wikipedia.org/wiki/Language_localisation
	 */
	const language_codes = [
		'zh_CN',
		'en',
		'en_US',
	];

	use Wnd_Singleton_Trait;

	private function __construct() {
		// 加载语言包
		add_action('plugins_loaded', [__CLASS__, 'load_languages']);

		// 根据$_REQUEST[WND_LANG_KEY]切换语言
		add_filter('locale', [__CLASS__, 'filter_locale']);

		// 为链接添加$_REQUEST[static::$request_key]参数
		add_filter('term_link', [__CLASS__, 'filter_link'], 99);
		add_filter('post_type_archive_link', [__CLASS__, 'filter_link'], 99);
		add_filter('post_type_link', [__CLASS__, 'filter_link'], 99);
		add_filter('post_link', [__CLASS__, 'filter_link'], 99);
		add_filter('author_link', [__CLASS__, 'filter_link'], 99);
		add_filter('get_edit_post_link', [__CLASS__, 'filter_link'], 99);

		// Wnd Filter
		add_filter('wnd_option_reg_redirect_url', [__CLASS__, 'filter_reg_redirect_link'], 99);
		add_filter('wnd_option_pay_return_url', [__CLASS__, 'filter_return_link'], 99);

		// 在用户完成注册时，将当前站点语言记录到用户字段
		add_action('user_register', [__CLASS__, 'action_on_user_register'], 99, 1);
	}

	/**
	 *语言包
	 *
	 *@since 2020.01.14
	 */
	public static function load_languages() {
		load_plugin_textdomain('wnd', false, 'wnd-frontend' . DIRECTORY_SEPARATOR . 'languages');
	}

	/**
	 *根据参数切换语言
	 *
	 *@since 2020.01.14
	 */
	public static function filter_locale($locale) {
		$switch_locale = static::parse_locale();
		$switch_locale = ('en' == $switch_locale) ? 'en_US' : $switch_locale;
		return $switch_locale ?: $locale;
	}

	/**
	 *根据当前语言参数，自动为其他链接添加语言参数
	 *
	 */
	public static function filter_link($link) {
		$lang = static::parse_locale();
		return $lang ? add_query_arg(static::$request_key, $lang, $link) : $link;
	}

	/**
	 *根据语言参数或社交登录回调参数，获取注册页面语言，并添加到跳转url
	 *
	 *@since 2020.04.11
	 */
	public static function filter_reg_redirect_link($link) {
		// 本地语言参数优先
		$lang = $_REQUEST[static::$request_key] ?? false;
		if ($lang) {
			return add_query_arg(static::$request_key, $lang, $link);
		}

		// 社交登录回调语言检测
		$state = $_REQUEST['state'] ?? false;
		if (!$state) {
			return $link;
		}

		// 解析自定义state
		$lang = Wnd_Login_Social::parse_state($state)[static::$request_key] ?? false;
		if (get_locale() == $lang) {
			return $link;
		}

		return $lang ? add_query_arg(static::$request_key, $lang, $link) : $link;
	}

	/**
	 *根据当前语言参数，自动为外部回调链接添加语言参数
	 *@since 2020.04.11
	 */
	public static function filter_return_link($link) {
		$user_locale = wnd_get_user_locale(get_current_user_id());
		if ('default' == $user_locale or get_locale() == $user_locale) {
			return $link;
		}

		return add_query_arg(static::$request_key, $user_locale, $link);
	}

	/**
	 *在用户完成注册时，将当前站点语言记录到用户字段
	 *@since 2020.04.11
	 */
	public static function action_on_user_register($user_id) {
		wnd_update_user_meta($user_id, 'locale', get_locale());
	}

	/**
	 *@since 0.9.30
	 *从 GET 参数中解析语言参数
	 */
	private static function parse_locale() {
		$locale = $_REQUEST[static::$request_key] ?? false;
		if (!$locale or !in_array($locale, self::language_codes)) {
			return false;
		}

		return $locale;
	}
}
