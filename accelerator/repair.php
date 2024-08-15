<?php
/**
 *  - 由于精简了部分功能，需要对应移除依赖这些功能的 Hook
 *  - 由于精简了部分功能，需要对应补充部分依赖这些功能的函数
 * @see wp-includes/default-filters.php
 * @since Wnd Frontend 0.9.35
 */

/**
 * 禁用古腾堡
 *
 */
add_filter('use_block_editor_for_post', '__return_false');

remove_action('current_screen', '_load_remote_block_patterns');

function has_blocks() {
	return false;
}

function has_block() {
	return false;
}

function parse_blocks($content) {
	return [];
}

function get_dynamic_block_names() {
	return [];
}

function excerpt_remove_blocks($text) {
	return $text;
}

/**
 * BlockTheme
 *
 */
function locate_block_template($template, $type, array $templates) {
	return $template;
}

/**
 * global style and setting
 */
function wp_get_global_stylesheet(): string {
	return '';
}

/**
 * WP_Theme_JSON
 */
class WP_Theme_JSON_Resolver {
	public static function theme_has_support() {
		return false;
	}
}

/**
 * 禁用 Embed
 *
 */
function wp_embed_register_handler($id, $regex, $callback, $priority = 10) {
	return false;
}

/**
 * 禁用 Widget
 *
 */
function is_active_sidebar() {
	return false;
}

/**
 * 移除 wptexturize
 *
 */
add_filter('run_wptexturize', '__return_false');

/**
 * HTTPS migration.
 *
 */
function wp_is_using_https() {
	return 'https' === wp_parse_url(home_url(), PHP_URL_SCHEME);
}

// 后端 WP Site Heath 会调用（插件保留了 WP Site Heath 相关功能）
// function wp_update_https_detection_errors() {}

// 短代码
function add_shortcode() {
	return;
}

// Application passwords
// add_filter('wp_is_application_passwords_available', '__return_false');

/**
 * 缓存核心更新结果，否则大陆服务器可能不定期拖站点加载速度（暂未查明具体逻辑）
 * @since 0.9.57.7
 */
get_site_option('auto_core_update_failed');

/**
 * @since wp 6.2
 */
function wp_theme_has_theme_json() {
	return false;
}

/**
 * Utilities used to fetch and create templates and template parts.
 *
 * @package WordPress
 * @since 5.8.0
 *
 * @since wp 6.2
 */

// Define constants for supported wp_template_part_area taxonomy.
if (!defined('WP_TEMPLATE_PART_AREA_HEADER')) {
	define('WP_TEMPLATE_PART_AREA_HEADER', 'header');
}
if (!defined('WP_TEMPLATE_PART_AREA_FOOTER')) {
	define('WP_TEMPLATE_PART_AREA_FOOTER', 'footer');
}
if (!defined('WP_TEMPLATE_PART_AREA_SIDEBAR')) {
	define('WP_TEMPLATE_PART_AREA_SIDEBAR', 'sidebar');
}
if (!defined('WP_TEMPLATE_PART_AREA_UNCATEGORIZED')) {
	define('WP_TEMPLATE_PART_AREA_UNCATEGORIZED', 'uncategorized');
}

/**
 * wp-admin 后台
 * @since 0.9.67
 *
 */
add_action('admin_init', function () {
	remove_action('admin_print_styles', 'wp_print_font_faces', 50);
});

// 禁止 Feed
function get_default_feed() {
	return 'rss2';
}

function bloginfo_rss($show = '') {
	echo '/';
}

add_action('parse_query', function () {
	if (is_feed()) {
		global $wp_query;
		$wp_query->set_404();
		status_header(404);
		get_template_part(404);
		exit();
	}
});
