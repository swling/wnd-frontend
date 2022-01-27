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
function wp_update_https_detection_errors() {}

// 短代码
function add_shortcode() {
	return;
}
