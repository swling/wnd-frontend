<?php

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