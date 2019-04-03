<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.02.28 如不注册类型，直接创建pending状态post时，会有notice级别的错误
 *@see wp-includes/post.php @3509
 */
function wnd_post_type_recharge() {
	$args = array(
		'description' => '充值',
		'public' => false,
		'has_archive' => false,
		'query_var' => false,
	);
	register_post_type('recharge', $args);
}
add_action('init', 'wnd_post_type_recharge');

/**
 *@since 2019.02.28 订单
 */
function wnd_post_type_order() {
	$args = array(
		'description' => '订单',
		'public' => false,
		'has_archive' => false,
		'query_var' => false, //order 为wp_query的排序参数，如果查询参数中包含order排序，会导致冲突，此处需要注销
	);
	register_post_type('order', $args);
}
add_action('init', 'wnd_post_type_order');

/**
 *@since 2019.02.28 站内信
 */
function wnd_post_type_mail() {
	$labels = array(
		'name' => '站内信',
	);
	$args = array(
		'labels' => $labels,
		'description' => '站内信',
		'public' => true,
		'has_archive' => false,
		'show_ui' => false,
		'rewrite' => array('slug' => 'mail', 'with_front' => false),
	);
	register_post_type('mail', $args);
}
add_action('init', 'wnd_post_type_mail');

##整站月度财务统计：stats_re（充值）、stats_ex（消费）
/**
 *@since 2019.02.28 充值统计
 */
function wnd_post_type_stats_re() {
	$args = array(
		'description' => '充值统计',
		'public' => false,
		'has_archive' => false,
	);
	register_post_type('stats-re', $args);
}
add_action('init', 'wnd_post_type_stats_re');

/**
 *@since 2019.02.28 消费统计
 */
function wnd_post_type_stats_ex() {
	$args = array(
		'description' => '消费统计',
		'public' => false,
		'has_archive' => false,
	);
	register_post_type('stats-ex', $args);
}
add_action('init', 'wnd_post_type_stats_ex');