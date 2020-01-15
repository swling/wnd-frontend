<?php
namespace Wnd\Model;

/**
 *多语言
 *
 *
 *@since 2020.01.14
 */
class Wnd_language {

	private static $instance;

	private function __construct() {
		// 加载语言包
		add_action('plugins_loaded', [__CLASS__, 'load_languages']);

		// 根据$_GET['lang']切换语言
		add_filter('locale', [__CLASS__, 'filter_locale']);

		// 为链接添加$_GET['lang']参数
		add_filter('home_url', [__CLASS__, 'filter_link'], 99);
		add_filter('term_link', [__CLASS__, 'filter_link'], 99);
		add_filter('post_type_archive_link', [__CLASS__, 'filter_link'], 99);
		add_filter('post_type_link', [__CLASS__, 'filter_link'], 99);
		add_filter('post_link', [__CLASS__, 'filter_link'], 99);
		add_filter('author_link', [__CLASS__, 'filter_link'], 99);
		add_filter('get_edit_post_link', [__CLASS__, 'filter_link'], 99);
	}

	/**
	 *单例模式
	 */
	public static function instance() {
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
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
	 *根据GET参数切换语言
	 *
	 *@since 2020.01.14
	 */
	public static function filter_locale($locale) {
		return $_GET['lang'] ?? $locale;
	}

	/**
	 *根据当前语言参数，自动为其他链接添加语言参数
	 *
	 */
	public static function filter_link($link) {
		$lang = $_GET['lang'] ?? '';
		return $lang ? add_query_arg('lang', $lang, $link) : $link;
	}
}
