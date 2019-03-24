<?php

/**
 * 获取当前分类下的标签列表
 *@since 2018
 */
function _wnd_list_tags_under_category($args = array()) {

	$defaults = array(
		'cat_id' => 0,
		'tag_taxonomy' => 'any',
		'limit' => 10,
		'show_count' => false,
		'template' => 'link',
		'key' => 'tag_id',
		'remove_keys' => array(),
		'title' => '全部',
	);
	$args = wp_parse_args($args, $defaults);

	$cat_id = $args['cat_id'];
	$tag_taxonomy = $args['tag_taxonomy'];
	$limit = $args['limit'];
	$show_count = $args['show_count'];
	$template = $args['template'];
	$key = $args['key'];
	$remove_keys = $args['remove_keys'];
	$title = $args['title'];

	// 获取缓存
	$tag_array = wnd_get_tags_under_category($cat_id, $tag_taxonomy, $limit);
	if (!$tag_array) {
		return;
	}

	$tag_list = '<ul class="list-tags-under-' . $cat_id . ' list-tags-under-category menu-list" >' . PHP_EOL;
	if ($template == 'query_arg') {
		$current_term_id = $_GET[$key] ?? false;
		$all_class = !$current_term_id ? 'class="on"' : false;
		$tag_list .= '<li><a href="' . remove_query_arg($key) . '" ' . $all_class . ' >' . $title . '</a></li>';
	}

	foreach ($tag_array as $value) {

		$tag_id = $value->tag_id;
		$tag_id = (int) $tag_id;
		$tag_taxonomy = $value->tag_taxonomy;

		$tag = get_term($tag_id);
		//输出常规链接
		if ($template == 'link') {
			if ($show_count) {
				$tag_list .= '<li><a href="' . get_term_link($tag_id) . '" >' . $tag->name . '</a>（' . $tag->count . '）</li>' . PHP_EOL;
			} else {
				$tag_list .= '<li><a href="' . get_term_link($tag_id) . '" >' . $tag->name . '</a></li>' . PHP_EOL;
			}

			//输出参数查询链接 ?tag_taxonomy=tag_id
		} elseif ($template == 'query_arg') {
			$class = (isset($_GET[$key]) && $_GET[$key] == $tag_id) ? 'class="on"' : '';
			$tag_list .= '<li><a href="' . add_query_arg($key, $tag_id, remove_query_arg($remove_keys)) . '" ' . $class . '>' . $tag->name . '</a></li>' . PHP_EOL;
		}
	}
	unset($value);

	$tag_list .= '</ul>' . PHP_EOL;

	echo $tag_list;

}

/**
 *获取指定taxonomy的分类列表并附带下属标签
 *@since 2018
 */
function _wnd_list_categories_with_tags($cat_taxonomy, $tag_taxonomy = 'any', $limit = 10, $show_count = false, $hide_empty = 1) {

	$args = array('hide_empty' => $hide_empty, 'orderby' => 'count', 'order' => 'DESC');
	$terms = get_terms($cat_taxonomy, $args);

	if (!empty($terms) && !is_wp_error($terms)) {

		echo '<div class="list-' . $cat_taxonomy . '-with-tags list-categories-with-tags">' . PHP_EOL;

		foreach ($terms as $term) {

			// 获取分类
			echo '<div id="category-' . $term->term_id . '" class="category-with-tags">' . PHP_EOL . '<h3><span class="iconfont"></span><a href="' . get_term_link($term) . '">' . $term->name . '</a></h3>' . PHP_EOL;
			// 获取分类下的标签
			_wnd_list_tags_under_category(array(
				'cat_id' => $term->term_id,
				'tag_taxonomy' => $tag_taxonomy,
				'limit' => $limit,
				'show_count' => $show_count,
			)
			);

			echo '</div>' . PHP_EOL;

		}
		unset($term);

		echo '</div>' . PHP_EOL;
	}

}

//###################################################### 生成taxonomy 多重查询参数 默认 GET name 为 term_id, GET值为 id
function _wnd_terms_query_arg($query = array(), $key = 'term_id', $remove_key = '', $title = '全部') {

	if (!$query) {
		return false;
	}

	$terms = get_terms($query);
	$current_term_id = isset($_GET[$key]) ? $_GET[$key] : false;
	$all_class = !$current_term_id ? 'class="is-active"' : false;

	if ($terms) {
		echo '<ul class="' . $query['taxonomy'] . '-query-args term-query-args">';
		array_push($remove_key, $key);
		echo '<li><a href="' . remove_query_arg($remove_key) . '" ' . $all_class . ' >' . $title . '</a></li>';
		foreach ($terms as $term) {

			$current = ($current_term_id == $term->term_id) ? 'class="is-active"' : null;
			echo '<li><a href="' . add_query_arg($key, $term->term_id, $url = remove_query_arg($remove_key)) . '" ' . $current . '>' . $term->name . '</a></li>' . PHP_EOL;

		}
		unset($term);
		echo '</ul>';
	}

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
		if ($terms) {
			$all_class = isset($_GET[$key]) ? '' : 'class="on"';

			array_push($remove_key, $key);
			echo '<ul class="' . $related_taxonmy . '">';
			echo '<li><a href="' . remove_query_arg($remove_key) . '" ' . $all_class . ' >' . $title . '</a></li>';
			foreach ($terms as $term) {
				$is_active = $_GET[$key] == $term->term_id ? 'class="is-active"' : '';
				echo '<li><a href="' . add_query_arg($key, $term->term_id, $url = remove_query_arg($remove_key)) . '"  ' . $is_active . '>' . $term->name . '</a></li>';
			}
			unset($term);
			echo '</ul>';
		}
	}

}