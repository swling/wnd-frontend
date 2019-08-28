<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.02.28 如不注册类型，直接创建pending状态post时，会有notice级别的错误
 *@see wp-includes/post.php @3509
 */
function wnd_action_register_post_type() {

	/*充值记录*/
	$labels = array(
		'name' => '充值记录',
	);
	$args = array(
		'labels'      => $labels,
		'description' => '充值',
		'public'      => false,
		'has_archive' => false,
		'query_var'   => false,
		/**
		 *支持author的post type 删除用户时才能自动删除对应的自定义post
		 *@see wp-admin/includes/user.php @370
		 *@since 2019.05.05
		 */
		'supports'    => array('title', 'author', 'editor'),
	);
	register_post_type('recharge', $args);

	/*订单记录*/
	$labels = array(
		'name' => '订单记录',
	);
	$args = array(
		'labels'      => $labels,
		'description' => '订单',
		'public'      => false,
		'has_archive' => false,
		'query_var'   => false, //order 为wp_query的排序参数，如果查询参数中包含order排序，会导致冲突，此处需要注销
		'supports'    => array('title', 'author', 'editor'),
	);
	register_post_type('order', $args);

	/*站内信*/
	$labels = array(
		'name' => '站内信',
	);
	$args = array(
		'labels'      => $labels,
		'description' => '站内信',
		'public'      => true,
		'has_archive' => false,
		'show_ui'     => false,
		'supports'    => array('title', 'author', 'editor', 'thumbnail', 'excerpt', 'comments', 'custom-fields'),
		'rewrite'     => array('slug' => 'mail', 'with_front' => false),
	);
	register_post_type('mail', $args);

	/*整站充值统计*/
	$labels = array(
		'name' => '充值统计',
	);
	$args = array(
		'labels'      => $labels,
		'description' => '充值统计',
		'public'      => false,
		'has_archive' => false,
		'supports'    => array('title', 'author', 'editor'),
	);
	register_post_type('stats-re', $args);

	/*整站消费统计*/
	$labels = array(
		'name' => '消费统计',
	);
	$args = array(
		'labels'      => $labels,
		'description' => '消费统计',
		'public'      => false,
		'has_archive' => false,
		'supports'    => array('title', 'author', 'editor'),
	);
	register_post_type('stats-ex', $args);

}
add_action('init', 'wnd_action_register_post_type');

/**
 *注册自定义post status
 **/
function wnd_action_register_post_status() {

	/**
	 *@since 2019.03.01 注册自定义状态：success 用于功能型post
	 *wp_insert_post可直接写入未经注册的post_status
	 *未经注册的post_status无法通过wp_query进行筛选，故此注册
	 **/
	register_post_status('success', array(
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => false,
		'show_in_admin_status_list' => false,
	));

	/**
	 *@since 2019.05.31 注册自定义状态：close 用于关闭文章条目，但前端可以正常浏览
	 *wp_insert_post可直接写入未经注册的post_status
	 *未经注册的post_status无法通过wp_query进行筛选，故此注册
	 **/
	register_post_status('close', array(
		'label'                     => '关闭',
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => false,
		'show_in_admin_status_list' => false,
	));
}
add_action('init', 'wnd_action_register_post_status');
