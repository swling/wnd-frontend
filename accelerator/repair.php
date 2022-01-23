<?php
/**
 *  - 由于精简了部分功能，需要对应移除依赖这些功能的 Hook
 *  - 由于精简了部分功能，需要对应补充部分依赖这些功能的函数
 * @see wp-includes/default-filters.php
 * @since Wnd Frontend 0.9.35
 */

/**
 * WP 默认 Rest API
 *
 */
remove_action('rest_api_init', 'create_initial_rest_routes', 99);

/**
 * 禁用古腾堡
 *
 */
add_filter('use_block_editor_for_post', '__return_false');

remove_action('init', ['WP_Block_Supports', 'init'], 22);
remove_action('init', '_register_core_block_patterns_and_categories');
remove_action('current_screen', '_load_remote_block_patterns');
remove_action('enqueue_block_assets', 'enqueue_block_styles_assets', 30);
remove_action('enqueue_block_assets', 'wp_enqueue_registered_block_scripts_and_styles');
remove_action('enqueue_block_editor_assets', 'wp_enqueue_registered_block_scripts_and_styles');
remove_filter('pre_kses', 'wp_pre_kses_block_attributes', 10, 3);
remove_filter('the_content', 'do_blocks', 9);

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
remove_filter('the_content_feed', '_oembed_filter_feed_content');

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

/**
 * 移除 HTTPS 相关
 *
 */
remove_filter('the_content', 'wp_replace_insecure_home_url');
remove_filter('the_excerpt', 'wp_replace_insecure_home_url');
remove_filter('wp_get_custom_css', 'wp_replace_insecure_home_url');

// HTTPS detection.
remove_action('init', 'wp_schedule_https_detection');
remove_action('wp_https_detection', 'wp_update_https_detection_errors');
remove_filter('cron_request', 'wp_cron_conditionally_prevent_sslverify', 9999);

// HTTPS migration.
remove_action('update_option_home', 'wp_update_https_migration_required', 10, 2);

function wp_is_using_https() {
	return 'https' === wp_parse_url(home_url(), PHP_URL_SCHEME);
}

// 后端 WP Site Heath 会调用（插件保留了 WP Site Heath 相关功能）
function wp_update_https_detection_errors() {}

/**
 * ###########################################################################
 * 其他 Filters
 * 以下的 Filter 为优化操作，即保留这些 Filter 也不会报错
 */

// Embeds.
remove_action('wp_head', 'wp_oembed_remove_discovery_links');
remove_action('wp_head', 'wp_oembed_remove_host_js');

remove_action('embed_head', 'enqueue_embed_scripts', 1);
remove_action('embed_head', 'print_emoji_detection_script');
remove_action('embed_head', 'print_embed_styles');
remove_action('embed_head', 'wp_print_head_scripts', 20);
remove_action('embed_head', 'wp_print_styles', 20);
remove_action('embed_head', 'wp_robots');
remove_action('embed_head', 'rel_canonical');
remove_action('embed_head', 'locale_stylesheet', 30);

remove_action('embed_content_meta', 'print_embed_comments_button');
remove_action('embed_content_meta', 'print_embed_sharing_button');

remove_action('embed_footer', 'print_embed_sharing_dialog');
remove_action('embed_footer', 'print_embed_scripts');
remove_action('embed_footer', 'wp_print_footer_scripts', 20);

remove_filter('the_excerpt_embed', 'wptexturize');
remove_filter('the_excerpt_embed', 'convert_chars');
remove_filter('the_excerpt_embed', 'wpautop');
remove_filter('the_excerpt_embed', 'shortcode_unautop');
remove_filter('the_excerpt_embed', 'wp_embed_excerpt_attachment');

remove_filter('oembed_dataparse', 'wp_filter_oembed_iframe_title_attribute', 5, 3);
remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10, 3);
remove_filter('oembed_response_data', 'get_oembed_response_data_rich', 10, 4);
remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10, 3);

// Script
remove_action('enqueue_block_assets', 'wp_enqueue_registered_block_scripts_and_styles');
remove_action('enqueue_block_assets', 'enqueue_block_styles_assets', 30);
remove_action('enqueue_block_editor_assets', 'wp_enqueue_registered_block_scripts_and_styles');
remove_action('enqueue_block_editor_assets', 'enqueue_editor_block_styles_assets');
remove_action('enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets');
remove_action('enqueue_block_editor_assets', 'wp_enqueue_editor_format_library_assets');

// Block Templates CPT and Rendering
remove_filter('render_block_context', '_block_template_render_without_post_block_context');
remove_filter('pre_wp_unique_post_slug', 'wp_filter_wp_template_unique_post_slug', 10, 5);
remove_action('wp_footer', 'the_block_template_skip_link');
remove_action('setup_theme', 'wp_enable_block_templates');

// Display Filters
remove_filter('widget_text', 'balanceTags');
remove_filter('widget_text_content', 'capital_P_dangit', 11);
remove_filter('widget_text_content', 'wptexturize');
remove_filter('widget_text_content', 'convert_smilies', 20);
remove_filter('widget_text_content', 'wpautop');
remove_filter('widget_text_content', 'shortcode_unautop');
remove_filter('widget_text_content', 'wp_filter_content_tags');
remove_filter('widget_text_content', 'wp_replace_insecure_home_url');
remove_filter('widget_text_content', 'do_shortcode', 11); // Runs after wpautop(); note that $post global will be null when shortcodes run.

remove_filter('widget_block_content', 'do_blocks', 9);
remove_filter('widget_block_content', 'wp_filter_content_tags');
remove_filter('widget_block_content', 'do_shortcode', 11);

remove_filter('block_type_metadata', 'wp_migrate_old_typography_shape');
