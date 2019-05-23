<?php

/**
 *@since 2019.05.23
 *面包屑导航
 **/
function _wnd_breadcrumb() {

	if (is_home()) {
		return;
	}

	/**
	 *columns
	 **/
	$html = '<div class="columns is-mobile">';

	/**
	 *左侧导航
	 **/
	$html .= '<div class="column">';
	$html .= '<nav class="breadcrumb is-small" aria-label="breadcrumbs">';
	$html .= '<ul>';
	$html .= '<li><a href="' . home_url() . '">首页</a></li>';
	$queried_object = get_queried_object();

	// 内容页
	if (is_single()) {

		$terms_link = '';

		$taxonomies = get_object_taxonomies($queried_object->post_type, $output = 'object');
		if ($taxonomies) {
			foreach ($taxonomies as $taxonomy) {

				if (!is_taxonomy_hierarchical($taxonomy->name) or !$taxonomy->public) {
					continue;
				}

				$terms_link .= get_the_term_list($queried_object->ID, $taxonomy->name, $before = '<li>', $sep = '', $after = '</li>');

			}
			unset($taxonomy);
		}

		$html .= $terms_link . '<li class="is-active"><a href="#">' . get_post_type_object($queried_object->post_type)->label . '详情</a></li>';

		//页面
	} elseif (is_page()) {

		$html .= '<li class="is-active"><a href="#">' . $queried_object->post_title . '</a></li>';

		//归档页
	} elseif (is_archive()) {

		$args = http_build_query(array('taxonomy' => $queried_object->taxonomy, 'orderby' => 'name'));
		$html .= '<li><a onclick="wnd_ajax_modal(\'_wnd_terms_list\',\'' . $args . '\')">' . get_taxonomy($queried_object->taxonomy)->label . '</a></li>';
		$html .= '<li class="is-active"><a href="#">' . $queried_object->name . '</a></li>';

	}

	$html .= '</ul>';
	$html .= '</nav>';
	$html .= '</div>';

	/**
	 *左侧导航
	 **/
	$html .= '<div class="column is-narrow is-size-7">';

	// 内页编辑
	if (is_single() and current_user_can('edit_post', $queried_object->ID)) {

		$html .= '<a href="' . get_edit_post_link($queried_object->ID) . '">[编辑]</a>';

		// 分类切换
	} elseif (is_archive()) {

	}

	$html .= '</div>';

	/**
	 *容器结束
	 **/
	$html .= '</div>';

	return $html;

}

/**
 *@since 2019.05.16
 *列出term链接列表
 **/
function _wnd_terms_list($args) {

	$defaults = array(
		'taxonomy' => 'post_tag',
		'number' => 50,
		'hidden_empty' => true,
		'orderby' => 'count',
		'order' => 'DESC',
	);
	$args = wp_parse_args($args, $defaults);

	$html = '<div class="columns has-text-centered" style="flex-wrap: wrap;">';
	$terms = get_terms($args);
	foreach ($terms as $term) {

		$html .= '<div class="column is-half"><a href="' . get_term_link($term->term_id) . '">' . $term->name . '</a></div>';

	}
	unset($term);
	$html .= '</div>';

	return $html;
}
