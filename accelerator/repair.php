<?php
/**
 *  - 由于精简了部分功能，需要对应移除依赖这些功能的 Hook
 *  - 由于精简了部分功能，需要对应补充部分依赖这些功能的函数
 * @since Wnd Frontend 0.9.35
 */

// WP 默认 Rest API
remove_action('rest_api_init', 'create_initial_rest_routes', 99);

// 禁用古腾堡
add_filter('use_block_editor_for_post', '__return_false');

// 移除依赖于古腾堡的相关 Hook
remove_action('init', ['WP_Block_Supports', 'init'], 22);
remove_action('init', '_register_core_block_patterns_and_categories');
remove_action('current_screen', '_load_remote_block_patterns');
remove_action('enqueue_block_assets', 'enqueue_block_styles_assets', 30);
remove_action('enqueue_block_assets', 'wp_enqueue_registered_block_scripts_and_styles');
remove_action('enqueue_block_editor_assets', 'wp_enqueue_registered_block_scripts_and_styles');
remove_filter('pre_kses', 'wp_pre_kses_block_attributes', 10, 3);
remove_filter('the_content', 'do_blocks', 9);

// 重写依赖于古腾堡的函数
function has_blocks() {
	return false;
}

function has_block() {
	return false;
}

function excerpt_remove_blocks($text) {
	return $text;
}

function locate_block_template($template, $type, array $templates) {
	return $template;
}

/**
 * 禁用 Embed
 *
 */
remove_action('plugins_loaded', 'wp_maybe_load_embeds', 0);
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'wp_oembed_add_host_js');
remove_action('rest_api_init', 'wp_oembed_register_route');
remove_filter('rest_pre_serve_request', '_oembed_rest_pre_serve_request', 10, 4);
remove_filter('excerpt_more', 'wp_embed_excerpt_more', 20);

function wp_embed_register_handler($id, $regex, $callback, $priority = 10) {
	return false;
}

/**
 * 禁用 Widget
 *
 */
remove_action('after_setup_theme', 'wp_setup_widgets_block_editor', 1);
remove_action('init', 'wp_widgets_init', 1);
remove_action('plugins_loaded', 'wp_maybe_load_widgets', 0);
remove_action('admin_head', 'wp_check_widget_editor_deps');
remove_action('after_switch_theme', '_wp_sidebars_changed');

function is_active_sidebar() {
	return false;
}

/**
 * 移除 wptexturize
 *
 */
add_filter('run_wptexturize', '__return_false');
