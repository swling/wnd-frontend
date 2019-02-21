<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.02.19 封装前端管理员内容审核平台
 *@param array or string ：wp_query args
 */
function _wnd_admin_posts_panel($args = array()) {

	if (!wnd_is_manager()) {
		echo '<div class="message is-danger"><div class="message-body">当前账户没有管理权限！</div></div>';
		return;
	}

	// ajax请求类型
	$ajax_type = $_POST['ajax_type'] ?? 'modal';

	// 查询参数
	$defaults = array(
		'post_status' => 'pending',
		'post_type' => 'post',
	);
	$args = wp_parse_args($args, $defaults);

	// 优先参数
	$args['post_type'] = $_REQUEST['tab'] ?? $args['post_type']; 

	// 容器开始
	echo '<div id="admin-panel">';
	echo '<div class="tabs"><ul class="tab">';

	// 查询内容并输出导航链接
	$post_types = get_post_types(array('public' => true, 'show_ui' => true), $output = 'objects', $operator = 'and');
	unset($post_types['page'], $post_types['attachment']); // 排除页面和附件

	foreach ($post_types as $post_type) {

		$active = ($args['post_type'] == $post_type->name) ? 'class="is-active"' : '';

		// 配置ajax请求参数
		$ajax_args = array_merge($args, array('post_type' => $post_type->name));
		unset($ajax_args['paged']);
		$ajax_args = http_build_query($ajax_args);

		if (wp_doing_ajax()) {
			if ($ajax_type == 'modal') {
				echo '<li ' . $active . '><a onclick="wnd_ajax_modal(\'list_posts\',\'' . $ajax_args . '\');">' . $post_type->label . '</a></li>';
			} else {
				echo '<li ' . $active . '><a onclick="wnd_ajax_embed(\'#admin-panel-posts-list\',\'list_posts\',\'' . $ajax_args . '\');">' . $post_type->label . '</a></li>';
			}
		} else {
			echo '<li ' . $active . '><a href="' . add_query_arg('tab', $post_type->name, remove_query_arg('pages')) . '">' . $post_type->label . '</a></li>';
		}

	}
	unset($post_type);
	echo '</ul></div>';

	echo '<div id="admin-panel-posts-list">';
	_wnd_list_posts($args);
	echo '</div>';

	// 容器结束
	echo '</div>';

}

/**
 *@since 2019.02.19 封装前端当前用户内容管理面板
 *@param array or string ：wp_query args
 */
function _wnd_user_posts_panel($args = array()) {

	if (!is_user_logged_in()) {
		echo '<div class="message is-danger"><div class="message-body">请登录！</div></div>';
		return;
	}

	// ajax请求类型
	$ajax_type = $_POST['ajax_type'] ?? 'modal';

	// post types 过滤
	$post_types = get_post_types(array('public' => true), $output = 'objects', $operator = 'and');
	unset($post_types['page'], $post_types['attachment']); // 排除页面和附件
	foreach ($post_types as $post_type) {
		if (!in_array($post_type->name, wnd_get_allowed_post_types())) {
			unset($post_types[$post_type->name]);
		}
	}
	unset($post_type);

	// 查询参数
	$defaults = array(
		'post_status' => 'any',
		'post_type' => reset($post_types)->name, //$post_types 为多维数组，获取第一个type 的 name
	);
	$args = wp_parse_args($args, $defaults);

	// 优先参数
	$args['post_type'] = $_REQUEST['tab'] ?? $args['post_type'];
	$args['author'] = get_current_user_id();

	// 容器开始
	echo '<div id="user-posts">';
	echo '<div class="tabs"><ul class="tab">';

	foreach ($post_types as $post_type) {

		$active = ($args['post_type'] == $post_type->name) ? 'class="is-active"' : '';

		// 配置ajax请求参数
		$ajax_args = array_merge($args, array('post_type' => $post_type->name));
		unset($ajax_args['paged']);
		$ajax_args = http_build_query($ajax_args);

		if (wp_doing_ajax()) {
			if ($ajax_type == 'modal') {
				echo '<li ' . $active . '><a onclick="wnd_ajax_modal(\'list_posts\',\'' . $ajax_args . '\');">' . $post_type->label . '</a></li>';
			} else {
				echo '<li ' . $active . '><a onclick="wnd_ajax_embed(\'#user-posts-list\',\'list_posts\',\'' . $ajax_args . '\');">' . $post_type->label . '</a></li>';
			}
		} else {
			echo '<li ' . $active . '><a href="' . add_query_arg('tab', $post_type->name, remove_query_arg('pages')) . '">' . $post_type->label . '</a></li>';
		}

	}
	unset($post_type);
	echo '</ul></div>';

	echo '<div id="user-posts-list">';
	_wnd_list_posts($args);
	echo '</div>';

	// 容器结束
	echo '</div>';

}