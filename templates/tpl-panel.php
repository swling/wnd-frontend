<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.02.19 封装前端管理员内容审核平台
 *@param $posts_per_page 每页列表数目
 */
function _wnd_admin_posts_panel(int $posts_per_page = 0) {
	if (!is_user_logged_in()) {
		return '<div class="message is-warning"><div class="message-body">请登录！</div></div>';
	}
	$posts_per_page = $posts_per_page ?: get_option('posts_per_page');

	$filter = new Wnd_Filter(true);
	$filter->add_post_type_filter(get_post_types(array('public' => true)), true);
	$filter->add_post_status_filter(array('待审' => 'pending'));
	$filter->set_posts_template('_wnd_posts_tpl');
	$filter->set_posts_per_page($posts_per_page);
	$filter->set_ajax_container('#admin-posts-panel');
	$filter->query();
	return $filter->get_tabs() . '<div id="admin-posts-panel">' . $filter->get_results() . '</div>';
}

/**
 *@since 2019.02.19 封装前端当前用户内容管理面板
 *@param $posts_per_page 每页列表数目
 */
function _wnd_user_posts_panel(int $posts_per_page = 0) {
	if (!is_user_logged_in()) {
		return '<div class="message is-warning"><div class="message-body">请登录！</div></div>';
	}
	$posts_per_page = $posts_per_page ?: get_option('posts_per_page');

	$filter = new Wnd_Filter(true);
	$filter->add_post_type_filter(wnd_get_user_panel_post_types());
	$filter->add_post_status_filter(array('全部' => 'any', '发布' => 'publish', '待审' => 'pending', '关闭' => 'close', '草稿' => 'draft'));
	$filter->add_taxonomy_filter(array('taxonomy' => $filter->category_taxonomy));
	$filter->add_query(array('author' => get_current_user_id()));
	$filter->set_posts_template('_wnd_posts_tpl');
	$filter->set_posts_per_page($posts_per_page);
	$filter->set_ajax_container('#user-posts-panel');
	$filter->query();
	return $filter->get_tabs() . '<div id="user-posts-panel">' . $filter->get_results() . '</div>';
}

/**
 *@since 2019.02.19 封装前端当前用户站内信
 *@param $posts_per_page 每页列表数目
 */
function _wnd_user_mail_panel(int $posts_per_page = 0) {
	if (!is_user_logged_in()) {
		return '<div class="message is-warning"><div class="message-body">请登录！</div></div>';
	}
	$posts_per_page = $posts_per_page ?: get_option('posts_per_page');

	$filter = new Wnd_Filter(true);
	$filter->add_post_type_filter(array('mail'));
	$filter->add_post_status_filter(array('全部' => 'any', '未读' => 'pending', '已读' => 'private'));
	$filter->add_query(array('author' => get_current_user_id()));
	$filter->set_posts_template('_wnd_mail_posts_tpl');
	$filter->set_posts_per_page($posts_per_page);
	$filter->set_ajax_container('#user-mail-panel');
	$filter->query();
	return $filter->get_tabs() . '<div id="user-mail-panel">' . $filter->get_results() . '</div>';

}

/**
 *@since 2019.08.16
 *用户邮件列表
 *@param 	object 	$query 	WP_Query 实例化结果
 *@return 	string 	$html 	输出表单
 **/
function _wnd_mail_posts_tpl($query) {
	$table = new Wnd_Posts_Table($query, true, true);
	$table->add_column(
		array(
			'post_field' => 'post_date',
			'title' => '日期',
			'class' => 'is-narrow is-hidden-mobile',
		)
	);
	$table->add_column(
		array(
			'post_field' => 'post_title_with_link',
			'title' => '标题',
		)
	);
	$table->build();
	$html = $table->html;
	return $html;
}

/**
 *@since 2019.08.16
 *常规文章列表
 *@param 	object 	$query 	WP_Query 实例化结果
 *@return 	string 	$html 	输出表单
 **/
function _wnd_posts_tpl($query) {
	$table = new Wnd_Posts_Table($query, true, true);
	$table->add_column(
		array(
			'post_field' => 'post_date',
			'title' => '日期',
			'class' => 'is-narrow is-hidden-mobile',
		)
	);
	$table->add_column(
		array(
			'post_field' => 'post_title_with_link',
			'title' => '标题',
		)
	);
	$table->add_column(
		array(
			'post_field' => 'post_status',
			'title' => '状态',
			'class' => 'is-narrow',
		)
	);
	$table->build();
	$html = $table->html;
	return $html;
}

/**
 *@since 2019.08.16
 *常规单个文章模板 演示
 *@param 	object 	$post 	post 对象
 *@return 	string 	$html 	输出表单
 **/
function _wnd_post_tpl($post) {
	$html = '<li><a href="' . get_permalink($post->ID) . '" target="_blank">' . $post->post_title . '</a></li>';
	return $html;
}
