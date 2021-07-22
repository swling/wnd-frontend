<?php
/**
 *  - 由于精简了部分功能，需要对应移除依赖这些功能的 Hook
 *  - 由于精简了部分功能，需要对应补充部分依赖这些功能的函数
 * @since Wnd Frontend 0.9.35
 */

// WP 默认 Rest API
remove_action('rest_api_init', 'create_initial_rest_routes', 99);

// 古腾堡
remove_action('init', ['WP_Block_Supports', 'init'], 22);
remove_action('init', '_register_core_block_patterns_and_categories');
remove_filter('pre_kses', 'wp_pre_kses_block_attributes', 10, 3);
remove_filter('the_content', 'do_blocks', 9);
remove_action('current_screen', '_load_remote_block_patterns');
remove_action('enqueue_block_assets', 'enqueue_block_styles_assets', 30);
remove_action('enqueue_block_assets', 'wp_enqueue_registered_block_scripts_and_styles');
remove_action('enqueue_block_editor_assets', 'wp_enqueue_registered_block_scripts_and_styles');

// 古腾堡函数
function has_blocks() {
	return false;
}

function has_block() {
	return false;
}

function excerpt_remove_blocks($text) {
	return $text;
}
