<?php
namespace Wnd\Utility;

use Wnd\Utility\Wnd_Singleton_Trait;

/**
 * 多语言
 * - 目前仅支持中英双语
 *
 * 语言优先级
 * - $_GET 参数优先
 * - cookie 其次
 * - 浏览器语言最后
 * - 若全部为空则为 WP 后台配置的站点语言
 *  @see static::parse_user_locale()
 *
 * ### 当 URL 包含语言参数时，针对浏览器用户：
 * - 将解析后的语言参数写入 cookie 保存
 * - 当语言参数与 $site_locale（站点默认语言） 不一致时会触发 filter 对页面上的各类固定链接添加语言参数
 * - 简而言之：包含语言参数的 URL 对浏览器用户主要用于手动切换网站语言
 *
 * ### 当 URL 包含语言参数时，针对 Google 等搜索引擎
 * - Googlebot/Bingbot 不会携带 Accept-Language 头，且不会保存 cookie故：$user_locale = $site_locale
 * - 当语言参数与 $site_locale（站点默认语言） 不一致时会触发 filter 对页面上的各类固定链接添加语言参数
 * - 简而言之：包含语言参数的 URL 旨在供 Google 等海外搜索引擎索引多语言版本
 *
 * ### 注意
 * -【百度】搜索引擎会携带 Accept-Language 头，且为：zh-CN
 * - 应拦截百度对多语言版本的抓取，仅允许其抓取中文版本
 *
 * @link https://developers.google.com/search/docs/specialty/international/managing-multi-regional-sites?hl=zh-Hans#use-different-urls-for-different-language-versions
 *
 * @since 2020.01.14
 */
class Wnd_language {

	public static $request_key  = WND_LANG_KEY;
	private static $user_locale = '';
	private static $site_locale = '';
	private static $lang        = '';

	/**
	 * 此处并非标准 language code 而是简化后用于 GET 参数的 key 值
	 */
	const language_codes = [
		'zh_CN',
		'zh_TW',
		'en',
	];

	use Wnd_Singleton_Trait;

	private function __construct() {
		// 站点默认语言，必须设置在 'locale' 钩子之前
		static::$site_locale = get_locale();

		// 解析语言参数
		static::$lang = static::parse_locale();

		// 用户语言
		static::$user_locale = static::parse_user_locale(static::$site_locale, static::$lang);

		// 切换语言：绕过 WP 后台，否则管理员将难以确认当前站点的默认语言
		if (!is_admin()) {
			add_filter('locale', function () {return static::$user_locale;});
		}

		// 在用户完成注册时，将当前站点语言记录到用户字段
		add_action('user_register', [__CLASS__, 'action_on_user_register'], 99, 1);

		// 处理语言 $_GET 参数
		static::handle_lang_query(static::$lang);
	}

	/**
	 * 解析用户语言并转为 WP_Locale 格式（ '-' 改 '_' ）
	 * - $_GET 参数优先
	 * - cookie 其次
	 * - 浏览器语言最后
	 * - 若全部为空则为 WP 后台配置的站点语言
	 */
	private static function parse_user_locale(string $site_locale, string $lang): string {
		// 参数优先
		if ($lang) {
			return $lang;
		}

		// cookie 其次
		if (isset($_COOKIE['lang'])) {
			return $_COOKIE['lang'];
		}

		/**
		 * - 获取浏览器首选语言
		 * - 将浏览器语言转为 WP locale
		 */
		$lang = static::get_browser_language();
		if (in_array($lang, ['zh-CN', 'zh-cn', 'zh', 'zh_CN', 'zh_cn'])) {
			return 'zh_CN';
		}

		if (in_array($lang, ['zh-TW', 'zh_TW', 'zh-HK', 'zh_HK'])) {
			return 'zh_TW';
		}

		if (str_starts_with($lang, 'en-') or 'en' == $lang) {
			return 'en_US';
		}

		return $site_locale;
	}

	private function handle_lang_query(string $lang) {
		if (!$lang) {
			return;
		}

		// 语言参数用于用户手动修改语言，存入 cookie
		if (!isset($_COOKIE['lang']) or $lang != $_COOKIE['lang']) {
			$domain = parse_url(home_url())['host'];
			setcookie('lang', $lang, time() + (3600 * 24 * 365), '/', $domain);
		}

		// 语言切换参数与：（站点默认语言 or Cookie 设置语言） 一致时，页面链接无需添加语言参数
		if ($lang == static::$site_locale or (isset($_COOKIE['lang']) and $lang == $_COOKIE['lang'])) {
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
			if (str_starts_with($lang, 'en_')) {
				$lang = 'en';
			}
			$link = str_replace('?' . static::$request_key . '=' . $lang, '', $link);
			return static::filter_link($link);
		}, 99);

		// Wnd Filter
		add_filter('wnd_option_reg_redirect_url', [__CLASS__, 'filter_link'], 99);
		add_filter('wnd_option_pay_return_url', [__CLASS__, 'filter_return_link'], 99);
	}

	/**
	 * 根据当前语言参数，自动为其他链接添加语言参数
	 * 注：
	 * - 常规浏览器用户不会添加语言参数（与 handle_lang_query 重复判断的原因在于，外部可能会调用本方法）
	 * - 英语类 en_US, en_GB, en_CA 等统一设置 为 en
	 * - 由于本类为单例模式，因此 static::$site_locale 已在插件初始化时被赋值
	 */
	public static function filter_link($link): string{
		$lang = static::parse_locale();

		// 语言切换参数与：（站点默认语言 or Cookie 设置语言） 一致时，页面链接无需添加语言参数
		if ($lang == static::$site_locale or (isset($_COOKIE['lang']) and $lang == $_COOKIE['lang'])) {
			return $link;
		}

		if (str_starts_with($lang, 'en_')) {
			$lang = 'en';
		}

		return $lang ? add_query_arg(static::$request_key, $lang, $link) : $link;
	}

	/**
	 * 根据当前语言参数，自动为外部回调链接添加语言参数
	 * 之所以存在本方法，因为在支付类场景，不允许回调带参，故此需要调用用户字段来设置
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
	 * 注：英语类统一转为 en_US
	 * @since 0.9.30
	 */
	public static function parse_locale() {
		$locale = $_REQUEST[static::$request_key] ?? false;
		if (!$locale or !in_array($locale, self::language_codes)) {
			return false;
		}

		return ('en' == $locale) ? 'en_US' : $locale;
	}

	/**
	 * Get browser language, given an array of avalaible languages.
	 * @link https://gist.github.com/joke2k/c8118e8179172f2f075f0f024ed379d2
	 *
	 * Language code
	 * @see https://en.wikipedia.org/wiki/Language_localisation
	 *
	 * @return [string] 	Language code/prefix
	 */
	public static function get_browser_language(): string {
		if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			return '';
		}

		$langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

		return empty($langs) ? '' : $langs[0];
	}

	public static function selector(): string{
		$langs       = ['zh_CN' => '简体', 'zh_TW' => '繁体', 'en' => 'EN'];
		$user_locale = static::$user_locale;
		if ('en_US' == $user_locale) {
			$user_locale = 'en';
		}

		$html = '<select onchange="window.location=this.value">';
		foreach ($langs as $key => $lang) {
			$link = add_query_arg('lang', $key);
			if ($key == $user_locale) {
				$html .= '<option value="' . $link . '" selected>' . $lang . '</option>';
			} else {
				$html .= '<option value="' . $link . '">' . $lang . '</option>';
			}
		}
		$html .= '</select>';

		return $html;
	}

}
