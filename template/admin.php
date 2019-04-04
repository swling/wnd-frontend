<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.02.19 封装前端管理员内容审核平台
 *@param array or string ：wp_query args
 */
function _wnd_admin_posts_panel($args = '') {

	if (!wnd_is_manager()) {
		echo '<div class="message is-danger"><div class="message-body">当前账户没有管理权限！</div></div>';
		return;
	}

	// 查询参数
	$defaults = array(
		'post_status' => 'pending',
	);
	$args = wp_parse_args($args, $defaults);

	_wnd_posts_filter($args);

}

/**
 *@since 2019.02.19 封装前端当前用户内容管理面板
 *@param array or string ：wp_query args
 */
function _wnd_user_posts_panel($args = '') {

	if (!is_user_logged_in()) {
		echo '<div class="message is-danger"><div class="message-body">请登录！</div></div>';
		return;
	}

	// 查询参数
	$defaults = array(
		'post_status' => 'any',
		'wnd_only_cat' => 1,
	);
	$args = wp_parse_args($args, $defaults);

	// 优先参数
	$args['author'] = get_current_user_id();

	_wnd_posts_filter($args);

}

/**
 *@since 2019.02.19 封装前端当前用户站内信
 *@param array or string ：wp_query args
 */
function _wnd_user_mail_box($args = '') {

	if (!is_user_logged_in()) {
		echo '<div class="message is-danger"><div class="message-body">请登录！</div></div>';
		return;
	}

	// 查询参数
	$defaults = array(
		'post_status' => 'any',
		'post_type' => 'mail',
		'posts_per_page' => get_option('posts_per_page'),
		'paged' => 1,
	);
	$args = wp_parse_args($args, $defaults);

	// 优先参数
	$args['post_status'] = $_REQUEST['status'] ?? $args['post_status'];
	$args['author'] = get_current_user_id();

	// active
	$unread_active = ($args['post_status'] == 'pending') ? 'class="is-active"' : '';
	$all_active = ($args['post_status'] == 'any') ? 'class="is-active"' : '';

	// 容器开始
	echo '<div id="user-mail-box">';
	echo '<div class="tabs"><ul class="tab">';

	// 配置未读邮件ajax请求参数
	$ajax_args_unread = array_merge($args, array('post_status' => 'pending'));
	unset($ajax_args_unread['paged']);
	$ajax_args_unread = http_build_query($ajax_args_unread);

	// 配置全部邮箱ajax请求参数
	$ajax_args_all = array_merge($args, array('post_status' => 'any'));
	unset($ajax_args_all['paged']);
	$ajax_args_all = http_build_query($ajax_args_all);

	if (wp_doing_ajax()) {

		// ajax请求类型
		$ajax_type = $_POST['ajax_type'] ?? 'modal';
		if ($ajax_type == 'modal') {
			echo '<li ' . $all_active . '><a onclick="wnd_ajax_modal(\'user_mail_box\',\'' . $ajax_args_all . '\');">全部</a></li>';
			echo '<li ' . $unread_active . '><a onclick="wnd_ajax_modal(\'user_mail_box\',\'' . $ajax_args_unread . '\');">未读</a></li>';
		} else {
			echo '<li ' . $all_active . '><a onclick="wnd_ajax_embed(\'#user-mail-box\',\'user_mail_box\',\'' . $ajax_args_all . '\');">全部</a></li>';
			echo '<li ' . $unread_active . '><a onclick="wnd_ajax_embed(\'#user-mail-box\',\'user_mail_box\',\'' . $ajax_args_unread . '\');">未读</a></li>';
		}

	} else {

		echo '<li ' . $all_active . '><a href="' . remove_query_arg('status') . '">全部</a></li>';
		echo '<li ' . $unread_active . ' ><a href="' . add_query_arg('status', 'private') . '">未读</a></li>';

	}

	echo '</ul></div>';

	echo '<div id="user-mail-list">';
	_wnd_list_posts_by_table($args);
	echo '</div>';

	// 容器结束
	echo '</div>';

}