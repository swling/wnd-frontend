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
		return '<div class="message is-warning"><div class="message-body">当前账户没有管理权限！</div></div>';
	}

	// 查询参数
	$defaults = array(
		'post_status' => 'pending',
		'wnd_list_tpl' => '_wnd_table_list',
	);
	$args = wp_parse_args($args, $defaults);

	return _wnd_posts_filter($args);

}

/**
 *@since 2019.02.19 封装前端当前用户内容管理面板
 *@param array or string ：wp_query args
 */
function _wnd_user_posts_panel($args = '') {

	if (!is_user_logged_in()) {
		return '<div class="message is-warning"><div class="message-body">请登录！</div></div>';
	}

	// 查询参数
	$defaults = array(
		'post_status' => 'any',
		'wnd_only_cat' => 1,
		'wnd_list_tpl' => '_wnd_table_list',
	);
	$args = wp_parse_args($args, $defaults);

	// 优先参数
	$args['author'] = get_current_user_id();

	return _wnd_posts_filter($args);

}

/**
 *@since 2019.02.19 封装前端当前用户站内信
 *@param array or string ：wp_query args
 */
function _wnd_user_mail_box($args = '') {

	if (!is_user_logged_in()) {
		return '<div class="message is-warning"><div class="message-body">请登录！</div></div>';
	}

	// 查询参数
	$defaults = array(
		'post_status' => 'pending',
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
	$all_active = is_array($args['post_status']) ? 'class="is-active"' : '';

	// 配置未读邮件ajax请求参数
	$ajax_args_unread = array_merge($args, array('post_status' => 'pending'));
	unset($ajax_args_unread['paged']);
	$ajax_args_unread = http_build_query($ajax_args_unread);

	// 配置全部邮箱ajax请求参数
	$ajax_args_all = array_merge($args, array('post_status' => array('pending', 'private')));
	unset($ajax_args_all['paged']);
	$ajax_args_all = http_build_query($ajax_args_all);

	// 容器开始
	$html = '<div id="user-mail-box">';
	$html .= '<div class="tabs">';
	$html .= '<ul class="tab">';

	if (wnd_doing_ajax()) {

		// ajax请求类型
		$ajax_type = $_POST['ajax_type'] ?? 'modal';
		if ($ajax_type == 'modal') {
			$html .= '<li ' . $unread_active . '><a onclick="wnd_ajax_modal(\'_wnd_user_mail_box\',\'' . $ajax_args_unread . '\');">未读</a></li>';
			$html .= '<li ' . $all_active . '><a onclick="wnd_ajax_modal(\'_wnd_user_mail_box\',\'' . $ajax_args_all . '\');">全部</a></li>';
		} else {
			$html .= '<li ' . $unread_active . '><a onclick="wnd_ajax_embed(\'#user-mail-box\',\'_wnd_user_mail_box\',\'' . $ajax_args_unread . '\');">未读</a></li>';
			$html .= '<li ' . $all_active . '><a onclick="wnd_ajax_embed(\'#user-mail-box\',\'_wnd_user_mail_box\',\'' . $ajax_args_all . '\');">全部</a></li>';
		}

	} else {

		$html .= '<li ' . $all_active . '><a href="' . remove_query_arg('status') . '">全部</a></li>';
		$html .= '<li ' . $unread_active . ' ><a href="' . add_query_arg('status', 'private') . '">未读</a></li>';

	}

	$html .= '</ul>';
	$html .= '</div>';

	$html .= '<div id="user-mail-list">';
	$html .= _wnd_table_list($args);
	$html .= '</div>';

	// 容器结束
	$html .= '</div>';

	return $html;

}
