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
	$args['post_type'] = $_REQUEST['type'] ?? $args['post_type'];

	// 容器开始
	echo '<div id="admin-panel">';

	// post types 切换
	_wnd_post_types_tabs($args, $ajax_list_posts_call = 'list_posts', $ajax_embed_container = '#admin-panel-posts-list');

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
	$args['post_type'] = $_REQUEST['type'] ?? $args['post_type'];
	$args['author'] = get_current_user_id();

	// 容器开始
	echo '<div id="user-posts">';

	// post types 切换
	_wnd_post_types_tabs($args, $ajax_list_posts_call = 'list_posts', $ajax_embed_container = '#user-posts-list');

	echo '<div id="user-posts-list">';
	_wnd_list_posts($args);
	echo '</div>';

	// 容器结束
	echo '</div>';

}

/**
 *@since 2019.02.19 封装前端当前用户站内信
 *@param array or string ：wp_query args
 */
function _wnd_user_mail_box($args = array()) {

	if (!is_user_logged_in()) {
		echo '<div class="message is-danger"><div class="message-body">请登录！</div></div>';
		return;
	}

	// ajax请求类型
	$ajax_type = $_POST['ajax_type'] ?? 'modal';

	// 查询参数
	$defaults = array(
		'post_status' => 'any',
		'post_type' => 'mail',
	);
	$args = wp_parse_args($args, $defaults);

	// 优先参数
	$args['post_status'] = $_REQUEST['status'] ?? $args['post_status'];
	$args['author'] = get_current_user_id();

	// active
	$unread_active = ($args['post_status'] == 'private') ? 'class="is-active"' : '';
	$all_active = ($args['post_status'] != 'private') ? 'class="is-active"' : '';

	// 容器开始
	echo '<div id="user-mail-box">';
	echo '<div class="tabs"><ul class="tab">';

	// 配置未读邮件ajax请求参数
	$ajax_args_unread = array_merge($args, array('post_status' => 'private'));
	unset($ajax_args_unread['paged']);
	$ajax_args_unread = http_build_query($ajax_args_unread);

	// 配置全部邮箱ajax请求参数
	$ajax_args_all = array_merge($args, array('post_status' => 'any'));
	unset($ajax_args_all['paged']);
	$ajax_args_all = http_build_query($ajax_args_all);

	if (wp_doing_ajax()) {

		if ($ajax_type == 'modal') {
			echo '<li ' . $all_active . '><a onclick="wnd_ajax_modal(\'list_posts\',\'' . $ajax_args_all . '\');">全部</a></li>';
			echo '<li ' . $unread_active . '><a onclick="wnd_ajax_modal(\'list_posts\',\'' . $ajax_args_unread . '\');">未读</a></li>';
		} else {
			echo '<li ' . $all_active . '><a onclick="wnd_ajax_embed(\'#user-mail-list\',\'list_posts\',\'' . $ajax_args_all . '\');">全部</a></li>';
			echo '<li ' . $unread_active . '><a onclick="wnd_ajax_embed(\'#user-mail-list\',\'list_posts\',\'' . $ajax_args_unread . '\');">未读</a></li>';
		}

	} else {

		echo '<li ' . $all_active . '><a href="' . remove_query_arg('status') . '">全部</a></li>';
		echo '<li ' . $unread_active . ' ><a href="' . add_query_arg('status', 'private') . '">未读</a></li>';

	}

	echo '</ul></div>';

	echo '<div id="user-mail-list">';
	_wnd_list_posts($args);
	echo '</div>';

	// 容器结束
	echo '</div>';

}