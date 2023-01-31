<?php
namespace Wnd\Utility;

use Wnd\Getway\Wnd_Login_Social;
use Wnd\Utility\Wnd_Singleton_Trait;

/**
 * 多语言
 * - 目前仅支持中英双语
 *
 * @since 2020.01.14
 */
class Wnd_language {

	public static $request_key = WND_LANG_KEY;

	/**
	 * @link https://en.wikipedia.org/wiki/Language_localisation
	 *
	 * - 此处并非标准 language code 而是简化后用于 GET 参数的 key 值
	 * - 首项元素为默认语言，对应链接将不会添加语言参数
	 */
	const language_codes = [
		'zh_CN',
		'en',
	];

	use Wnd_Singleton_Trait;

	private function __construct() {
		// 切换语言
		add_filter('locale', [__CLASS__, 'filter_locale']);

		// 在用户完成注册时，将当前站点语言记录到用户字段
		add_action('user_register', [__CLASS__, 'action_on_user_register'], 99, 1);

		// 解析语言 $_GET 参数
		$lang = static::parse_locale();
		if (!$lang) {
			return;
		}

		// 语言参数用于用户手动修改语言，存入 cookie
		$switch_locale = $lang;
		$switch_locale = ('en' == $switch_locale) ? 'en_US' : $switch_locale;
		$domain        = parse_url(home_url())['host'];
		setcookie('lang', $switch_locale, time() + (3600 * 24 * 365), '/', $domain);

		// 默认语言不会添加语言参数
		if ($lang == static::language_codes[0]) {
			return;
		}

		// 为链接添加$_REQUEST[static::$request_key]参数
		add_filter('term_link', [__CLASS__, 'filter_link'], 99);
		add_filter('post_type_archive_link', [__CLASS__, 'filter_link'], 99);
		add_filter('post_type_link', [__CLASS__, 'filter_link'], 99);
		add_filter('post_link', [__CLASS__, 'filter_link'], 99);
		add_filter('page_link', [__CLASS__, 'filter_link'], 99);
		add_filter('attachment_link', [__CLASS__, 'filter_link'], 99);
		add_filter('author_link', [__CLASS__, 'filter_link'], 99);
		add_filter('get_edit_post_link', [__CLASS__, 'filter_link'], 99);

		// 修复因语言参数导致的评论分页 bug
		add_filter('get_comments_pagenum_link', function ($link) use ($lang) {
			$link = str_replace('?' . static::$request_key . '=' . $lang, '', $link);
			return static::filter_link($link);
		}, 99);

		// Wnd Filter
		add_filter('wnd_option_reg_redirect_url', [__CLASS__, 'filter_reg_redirect_link'], 99);
		add_filter('wnd_option_pay_return_url', [__CLASS__, 'filter_return_link'], 99);
	}

	/**
	 * 切换语言
	 * - $_GET 参数优先
	 * - cookie 其次
	 * - 浏览器语言最后
	 */
	public static function filter_locale(string $locale): string{
		// 参数优先
		$switch_locale = static::parse_locale();
		$switch_locale = ('en' == $switch_locale) ? 'en_US' : $switch_locale;
		if ($switch_locale) {
			return $switch_locale;
		}

		// cookie 其次
		if (isset($_COOKIE['lang'])) {
			return $_COOKIE['lang'];
		}

		// 浏览器语言
		$lang = static::get_browser_language($locale);
		return ('zh-CN' == $lang or 'zh' == $lang) ? 'zh_CN' : 'en_US';
	}

	/**
	 * 根据当前语言参数，自动为其他链接添加语言参数
	 * 注：默认语言不会添加语言参数
	 */
	public static function filter_link($link) {
		$lang = static::parse_locale();

		// 默认语言
		if ($lang == static::language_codes[0]) {
			return $link;
		}

		return $lang ? add_query_arg(static::$request_key, $lang, $link) : $link;
	}

	/**
	 * 根据语言参数或社交登录回调参数，获取注册页面语言，并添加到跳转url
	 *
	 * @since 2020.04.11
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
	 * 根据当前语言参数，自动为外部回调链接添加语言参数
	 * @since 2020.04.11
	 */
	public static function filter_return_link($link) {
		$user_locale = wnd_get_user_locale(get_current_user_id());
		if ('default' == $user_locale or get_locale() == $user_locale) {
			return $link;
		}

		return add_query_arg(static::$request_key, $user_locale, $link);
	}

	/**
	 * 在用户完成注册时，将当前站点语言记录到用户字段
	 * @since 2020.04.11
	 */
	public static function action_on_user_register($user_id) {
		wnd_update_user_meta($user_id, 'locale', get_locale());
	}

	/**
	 * 从 GET 参数中解析语言参数
	 * @since 0.9.30
	 */
	public static function parse_locale() {
		$locale = $_REQUEST[static::$request_key] ?? false;
		if (!$locale or !in_array($locale, self::language_codes)) {
			return false;
		}

		return $locale;
	}

	/**
	 * Get browser language, given an array of avalaible languages.
	 * @link https://gist.github.com/joke2k/c8118e8179172f2f075f0f024ed379d2
	 *
	 * Language code
	 * @see https://en.wikipedia.org/wiki/Language_localisation
	 *
	 * @param  [string]  $default             Default language for the site
	 * @return [string]                       Language code/prefix
	 */
	public static function get_browser_language(string $default = 'zh-CN'): string {
		if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			return $default;
		}

		$langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

		return empty($langs) ? $default : $langs[0];
	}

}
