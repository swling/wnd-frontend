<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.04.22常规分页导航栏
 *@param $max_page 页面总数
 *@param $show_page 当前页右侧需要显示的页面数量
 */
function _wnd_pagination($max_page, $show_page = 5) {

	$paged = (isset($_GET['page'])) ? intval($_GET['page']) : 1;
	// $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

	if ($max_page <= 1) {
		return;
	}

	$html = '<div class="pagination is-centered">';
	if ($paged > 1) {
		$html .= '<a class="pagination-previous" href="' . add_query_arg('page', $paged - 1) . '">上一页</a>';
	}

	if ($paged < $max_page) {
		$html .= '<a class="pagination-next" href="' . add_query_arg('page', $paged + 1) . '">下一页</a>';
	}

	$html .= '<ul class="pagination-list">';
	$html .= '<li><a class="pagination-link" href="' . remove_query_arg('page') . '" >首页</a></li>';
	for ($i = $paged - 1; $i <= $paged + $show_page; $i++) {
		if ($i > 0 && $i <= $max_page) {
			if ($i == $paged) {
				$html .= '<li><a class="pagination-link is-current" href="' . add_query_arg('page', $i) . '"> <span>' . $i . '</span> </a></li>';
			} else {
				$html .= '<li><a class="pagination-link" href="' . add_query_arg('page', $i) . '"> <span>' . $i . '</span> </a></li>';
			}
		}
	}
	if ($paged < $max_page - 3) {
		$html .= '<li><span class="pagination-ellipsis">&hellip;</span></li>';
	}

	$html .= '<li><a class="pagination-link" href="' . add_query_arg('page', $max_page) . '">尾页</a></li>';
	$html .= '</ul></div>';

	return $html;
}

/**
 *@since 2019.02.15 简单分页
 *不查询总数的情况下，简单实现下一页翻页
 *翻页参数键名$page_key 不能设置为 paged 可能会与原生WordPress翻页机制产生冲突
 */
function _wnd_next_page($posts_per_page, $current_post_count, $page_key = 'page') {

	$paged = (isset($_GET[$page_key])) ? intval($_GET[$page_key]) : 1;

	$html = '<nav class="pagination is-centered" role="navigation" aria-label="pagination">';
	$html .= '<ul class="pagination-list">';

	if ($paged >= 2) {
		$html .= '<li><a class="pagination-link" href="' . add_query_arg($page_key, $paged - 1) . '">上一页</a>';
	}
	if ($current_post_count >= $posts_per_page) {
		$html .= '<li><a class="pagination-link" href="' . add_query_arg($page_key, $paged + 1) . '">下一页</a>';
	}
	$html .= '</ul>';
	$html .= '</nav>';

	return $html;

}

/**
 *@since 2019.02.18 ajax分页
 *在当前ajax请求的基础上，自增一个翻页参数，并再次发送(paged)
 *@param $function 需要请求的内容列表输出函数名
 *@param $function 的参数
 *函数应该与请求函数处于同一个父元素之下，父容器必须设置ID
 */
function _wnd_ajax_next_page($function, $args, $post_count) {

	$current_page = $args['paged'] ?? 1;

	// 下一页参数
	$args['paged'] = $current_page + 1;
	$js_next_args = '\'' . $function . '\',\'' . http_build_query($args) . '\'';
	// $js_next_args = str_replace('_wnd_', '', $js_next_args);

	// 上一页参数
	$args['paged'] = $current_page - 1;
	$js_pre_args = '\'' . $function . '\',\'' . http_build_query($args) . '\'';
	// $js_pre_args = str_replace('_wnd_', '', $js_pre_args);

	$ajax_type = $_GET['ajax_type'] ?? 'modal';
	if ($ajax_type == 'modal') {
		$next_onclick = 'wnd_ajax_modal(' . $js_next_args . ')';
		$pre_onclick = 'wnd_ajax_modal(' . $js_pre_args . ')';
	} else {

		// 获取翻页元素的父元素ID
		$container = '\'#\' + $(this).parents(\'nav\').parent().attr(\'id\')';

		$next_onclick = 'wnd_ajax_embed(' . $container . ',' . $js_next_args . ')';
		$pre_onclick = 'wnd_ajax_embed(' . $container . ',' . $js_pre_args . ')';
	}

	$html = '<nav class="pagination is-centered" role="navigation" aria-label="pagination">';
	$html .= '<ul class="pagination-list">';
	if ($current_page >= 2) {
		$html .= '<li><a class="pagination-link" onclick="' . $pre_onclick . '">上一页</a>';
	}
	if ($post_count >= $args['posts_per_page']) {
		$html .= '<li><a class="pagination-link" onclick="' . $next_onclick . '">下一页</a>';
	}

	$html .= '</ul>';
	$html .= '</nav>';
	return $html;

}

/**
 *@since 2019.02.15
 *以表格形式输出WordPress文章列表
 *@param $args wp_query参数
 */
function _wnd_table_list($args = '') {

	$args = wp_parse_args($args);

	$query = new WP_Query($args);

	if ($query->have_posts()):

		$html = '<table class="table is-fullwidth is-hoverable is-striped">';

		$html .= '<thead>';
		$html .= '<tr>';
		$html .= '<th class="is-narrow is-hidden-mobile">日期</th>';
		$html .= '<th>标题</th>';
		$html .= '<th class="is-narrow is-hidden-mobile">状态</th>';
		$html .= '<th class="is-narrow is-hidden-mobile">操作</th>';
		$html .= '</tr>';
		$html .= '</thead>';

		$html .= '<tbody>';
		while ($query->have_posts()) {
			$query->the_post();

			global $post;
			$post = apply_filters('_wnd_table_list_data', $post);

			$html .= '<tr>';
			$html .= '<td class="is-narrow is-hidden-mobile">' . get_the_time('m-d H:i') . '</td>';
			$html .= '<td><a href="' . get_permalink() . '" target="_blank">' . $post->post_title . '</a></td>';
			if (current_user_can('edit_post', $post->ID)) {
				$html .= '<th class="is-narrow is-hidden-mobile">' . $post->post_status . '</th>';
			}

			$html .= '<td class="is-narrow is-hidden-mobile">';
			$html .= '<a onclick="wnd_ajax_modal(\'_wnd_post_info\',\'post_id=' . $post->ID . '\')"><i class="fas fa-info-circle"></i></a>';
			if (current_user_can('edit_post', $post->ID)) {
				$html .= '&nbsp<a onclick="wnd_ajax_modal(\'_wnd_post_status_form\',\'' . $post->ID . '\')"><i class="fas fa-cog"></i></a>';
			}
			$html .= '</td>';
			$html .= '</tr>';
		}
		$html .= '</tbody>';

		$html .= '</table>';

		wp_reset_postdata(); //重置查询

		// 没有内容
	else:
		$no_more_text = ($args['paged'] >= 2) ? '没有更多内容！' : '没有匹配的内容！';
		$html = '<div class="message is-warning"><div class="message-body">' . $no_more_text . '</div></div>';
	endif;

	// 分页
	if (!wnd_doing_ajax()) {
		$html .= _wnd_next_page($args['posts_per_page'], $query->post_count, 'page');
	} else {
		$html .= _wnd_ajax_next_page(__FUNCTION__, $args, $query->post_count);
	}

	return $html;

}

/**
 *@since 2019.03.05
 *调用主题文章输出列表模板
 *@param $args  wp_query $args
 */
function _wnd_post_list($args = '') {

	$html = '';
	$args = wp_parse_args($args);

	$query = new WP_Query($args);

	if ($query->have_posts()) {

		while ($query->have_posts()) {
			$query->the_post();
			global $post;

			/**
			 *@since 2019.04.18
			 *默认输出带链接的标题，外部函数通过filter实现自定义
			 */
			$list = '<h3><a href="' . get_permalink($post) . '">' . $post->post_title . '</a></h3>';
			$html .= apply_filters('_wnd_post_list_tpl', $list, $post);

		}
		wp_reset_postdata(); //重置查询

		// 没有内容
	} else {

		$no_more_text = ($args['paged'] >= 2) ? '没有更多内容！' : '没有匹配的内容！';
		$html .= '<div class="message is-warning"><div class="message-body">' . $no_more_text . '</div></div>';
	}

	// 分页
	if (!wnd_doing_ajax()) {
		$html .= _wnd_next_page($args['posts_per_page'], $query->post_count, 'page');
	} else {
		$html .= _wnd_ajax_next_page(__FUNCTION__, $args, $query->post_count);
	}

	return $html;

}

/**
 *@since 名片列表
 */
function _wnd_post_list_tpl($post) {

	$html = '<div class="post-list columns is-multiline is-tablet people-list">';

	$html .= '<div class="column">';
	$html .= '<h3><a href="' . get_permalink($post) . '">' . $post->post_title . '</a>';
	$html .= '</h3>';
	$html .= '</div>';

	$html .= '<div class="column is-narrow">';
	$html .= '</div>';

	$html .= '<div class="column is-full">' . wp_trim_words($post->post_excerpt, 100) . '</div>';

	$html .= '</div>';

	return $html;
}