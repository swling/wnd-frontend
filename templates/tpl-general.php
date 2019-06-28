<?php

/**
 *@since 2019.05.23
 *面包屑导航
 **/
function _wnd_breadcrumb() {

	if (is_home() or is_author()) {
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
	$html .= '<div class="column is-narrow is-size-7 breadcrumb-right">';

	$breadcrumb_right = null;

	// 内页编辑
	if (is_single()) {
		if (current_user_can('edit_post', $queried_object->ID)) {
			$breadcrumb_right .= '<a href="' . get_edit_post_link($queried_object->ID) . '">[编辑]</a>';
			$breadcrumb_right .= '&nbsp<a onclick="wnd_ajax_modal(\'_wnd_post_status_form\',\'' . $queried_object->ID . '\')">[管理]</a>';
		}
	}

	$html .= apply_filters('_wnd_breadcrumb_right', $breadcrumb_right);

	$html .= '</div>';

	/**
	 *容器结束
	 **/
	$html .= '</div>';

	return $html;

}

/**
 *@since 2019.05.26 bulma 颜色下拉选择
 */
function _wnd_dropdown_colors($name, $selected) {

	$colors = array(
		'primary',
		'success',
		'info',
		'link',
		'warning',
		'danger',
		'dark',
		'black',
		'light',
	);

	$html = '<select name="' . $name . '">';

	foreach ($colors as $color) {
		if ($selected == $color) {
			$html .= '<option selected="selected" value="' . $color . '">' . $color . '</option>';
		} else {
			$html .= '<option value="' . $color . '">' . $color . '</option>';
		}

	}
	unset($color);

	$html .= '</select>';

	return $html;
}
