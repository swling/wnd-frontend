<?php

/**
 *获取指定taxonomy的分类列表并附带下属标签
 *@since 2018
 */
function _wnd_list_categories_with_tags($cat_taxonomy, $tag_taxonomy = 'any', $limit = 10, $show_count = false, $hide_empty = 1) {

	$args = array('hide_empty' => $hide_empty, 'orderby' => 'count', 'order' => 'DESC');
	$terms = get_terms($cat_taxonomy, $args);

	if (empty($terms) || is_wp_error($terms)) {
		return;
	}

	$html = '<div class="list-' . $cat_taxonomy . '-with-tags list-categories-with-tags">' . PHP_EOL;

	foreach ($terms as $term) {

		// 获取分类
		$html .= '<div id="category-' . $term->term_id . '" class="category-with-tags">' . PHP_EOL;
		$html .= '<h3><a href="' . get_term_link($term) . '">' . $term->name . '</a></h3>' . PHP_EOL;

		$tag_list = '<ul class="list-tags-under-' . $term->term_id . ' list-tags-under-category menu-list">' . PHP_EOL;

		$tags = wnd_get_tags_under_category($term->term_id, $tag_taxonomy, $limit);
		foreach ($tags as $tag) {

			$tag_id = $tag->tag_id;
			$tag_id = (int) $tag_id;
			$tag_taxonomy = $tag->tag_taxonomy;

			$tag = get_term($tag_id);
			//输出常规链接
			if ($show_count) {
				$tag_list .= '<li><a href="' . get_term_link($tag_id) . '" >' . $tag->name . '</a>（' . $tag->count . '）</li>' . PHP_EOL;
			} else {
				$tag_list .= '<li><a href="' . get_term_link($tag_id) . '" >' . $tag->name . '</a></li>' . PHP_EOL;
			}

		}
		unset($tag);

		$tag_list .= '</ul>';
		$html .= $tag_list;

		$html .= '</div>' . PHP_EOL;
	}
	unset($term);

	$html .= '</div>' . PHP_EOL;

	return $html;

}

//###################################################### 生成taxonomy 多重查询参数 默认 GET name 为 term_id, GET值为 id
function _wnd_terms_query_arg($query = array(), $key = 'term_id', $remove_key = '', $title = '全部') {

	if (!$query) {
		return false;
	}

	$terms = get_terms($query);
	$current_term_id = isset($_GET[$key]) ? $_GET[$key] : false;
	$all_class = !$current_term_id ? 'class="is-active"' : false;

	if (!$terms) {
		return;
	}

	$html = '<ul class="' . $query['taxonomy'] . '-query-args term-query-args">';
	array_push($remove_key, $key);
	$html .= '<li><a href="' . remove_query_arg($remove_key) . '" ' . $all_class . ' >' . $title . '</a></li>';
	foreach ($terms as $term) {

		$current = ($current_term_id == $term->term_id) ? 'class="is-active"' : null;
		$html .= '<li><a href="' . add_query_arg($key, $term->term_id, $url = remove_query_arg($remove_key)) . '" ' . $current . '>' . $term->name . '</a></li>' . PHP_EOL;

	}
	unset($term);
	$html .= '</ul>';
	return $html;

}

/*
在文章内容比较少的情况下，通过数据库查询的方式获取当前term关联的其他term生成多重查询参数
目的：避免产生过多没有内容的多重筛选
@2018.12.14 首次封装
 */
function _wnd_related_terms_query_arg($current_term_id, $related_taxonmy, $key, $remove_key = array(), $title = 'All', $query_post_max = 1000) {

	$current_term = get_term($current_term_id);

	if ($current_term->count < $query_post_max) {

		$terms = wndbiz_get_related_terms($term_id = $current_term_id, $terms_type = $related_taxonmy);
		if (!$terms) {
			return $terms;
		}
		$all_class = isset($_GET[$key]) ? '' : 'class="on"';

		array_push($remove_key, $key);
		$html = '<ul class="' . $related_taxonmy . '">';
		$html .= '<li><a href="' . remove_query_arg($remove_key) . '" ' . $all_class . ' >' . $title . '</a></li>';
		foreach ($terms as $term) {
			$is_active = $_GET[$key] == $term->term_id ? 'class="is-active"' : '';
			$html .= '<li><a href="' . add_query_arg($key, $term->term_id, $url = remove_query_arg($remove_key)) . '"  ' . $is_active . '>' . $term->name . '</a></li>';
		}
		unset($term);
		$html .= '</ul>';

		return $html;
	}

}