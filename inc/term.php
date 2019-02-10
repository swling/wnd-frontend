<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *万能的WordPress
 *
 *主要功能：
 *1、关联文章的分类和标签，获取当前分类下，文章所携带的标签、获取指定 taxonomy下所有分类，及各个分类对应的标签
 *2、单独数据表实现，增删term时，及删除文章时，实现同步更新
 *
 *@since 2018.07
 */

//############################################################################ 通过WordPress动作捕捉文章分类及标签设置
add_action('set_object_terms', 'wnd_set_object_terms_action', 10, 6);
function wnd_set_object_terms_action($object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids) {

	/*

		    自定义分类法约定taxonomy：分类为 $post_type_cat 标签为：$post_type_tag
		    默认post：分类 category 标签 post_tag

	*/

	// 选项中是否开启本功能
	if (wnd_get_option('wndwp', 'wnd_term_enable') != 1) {
		return;
	}

	$post_type = get_post_type($object_id);

	// 当前文章类型不包含标签 退出
	if (!taxonomy_exists($post_type . '_tag')) {
		return;
	}

	$cat_taxonomy = ($post_type == 'post') ? 'category' : $post_type . '_cat';
	$tag_taxonomy = $post_type . '_tag';

	// 默认post文章不添加记录，通常用于各类多重筛选，建议通过自定义文章类型使用
	// if( $post_type =='post')
	// return;

	// 1、分类改变
	if ($taxonomy == $cat_taxonomy) {

		// 获取当前修改文章的指定标签
		$tags = get_the_terms($object_id, $tag_taxonomy);
		if (!$tags) {
			return;
		}

		foreach ($tags as $tag) {
			$tag_id = $tag->term_id;

			// 旧数据 递减
			$delete_tt_ids = array_diff($old_tt_ids, $tt_ids);
			if ($delete_tt_ids) {
				foreach ($delete_tt_ids as $cat_id) {
					wnd_update_tag_under_cat($cat_id, $tag_id, $tag_taxonomy, $inc = 0);
				}
				unset($cat_id);
			}

			// 新数据 递增
			$add_tt_ids = array_diff($tt_ids, $old_tt_ids);
			if ($add_tt_ids) {
				foreach ($add_tt_ids as $cat_id) {
					wnd_update_tag_under_cat($cat_id, $tag_id, $tag_taxonomy, $inc = 1);
				}
				unset($cat_id);
			}

		}
		unset($tag);

		// 2、标签改变
	} elseif ($taxonomy == $tag_taxonomy) {

		// 获取当前文章的分类
		$cats = get_the_terms($object_id, $cat_taxonomy);
		if (!$cats) {
			return;
		}

		foreach ($cats as $cat) {
			$cat_id = $cat->term_id;

			// 旧数据 递减
			$delete_tt_ids = array_diff($old_tt_ids, $tt_ids);
			if ($delete_tt_ids) {
				foreach ($delete_tt_ids as $tag_id) {
					wnd_update_tag_under_cat($cat_id, $tag_id, $taxonomy, $inc = 0);
				}
				unset($tag_id);
			}

			// 新数据 递减
			$add_tt_ids = array_diff($tt_ids, $old_tt_ids);
			if ($add_tt_ids) {
				foreach ($add_tt_ids as $tag_id) {
					wnd_update_tag_under_cat($cat_id, $tag_id, $taxonomy, $inc = 1);
				}
				unset($tag_id);
			}

		}
		unset($cat);
	}

}

//############################################################################ 写入标签和分类数据库
function wnd_update_tag_under_cat($cat_id, $tag_id, $tag_taxonomy, $inc = true) {
	global $wpdb;

	// 删除对象缓存
	wp_cache_delete($cat_id . $tag_taxonomy, 'wnd_tag_list_under_cat');

	$result = $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM $wpdb->wnd_terms WHERE cat_id = %d AND tag_id = %d ",
		$cat_id,
		$tag_id
	));

	// 更新
	if ($result) {

		$ID = $result->ID;
		$count = $inc ? $result->count + 1 : $result->count - 1;

		// count为0，删除记录 返回
		if (!$count) {
			$wpdb->delete($wpdb->wnd_terms, array('ID' => $ID));
			return true;
		}

		$do_sql = $wpdb->update(
			$wpdb->wnd_terms, //table
			array('count' => $count), // data
			array('ID' => $ID), // where
			array('%d'), //data format
			array('%d') //where format
		);

		//没有记录，且操作为新增，写入数据
	} elseif ($inc) {

		$do_sql = $wpdb->insert(
			$wpdb->wnd_terms,
			array('cat_id' => $cat_id, 'tag_id' => $tag_id, 'tag_taxonomy' => $tag_taxonomy, 'count' => 1), //data
			array('%d', '%d', '%s', '%d') // data format
		);

		//没有记录无需操作
	} else {

		return false;

	}

	// 返回数据操作结果
	return $do_sql;

}

//############################################################################ 删除文章时，更新 tag under category数据
add_action('before_delete_post', 'wnd_delete_tag_under_cat', 10, 1);
function wnd_delete_tag_under_cat($object_id) {

	$post_type = get_post_type($object_id);
	// if( $post_type =='post') return;
	$cat_taxonomy = ($post_type == 'post') ? 'category' : $post_type . '_cat';
	$tag_taxonomy = $post_type . '_tag';

	$cats = get_the_terms($object_id, $cat_taxonomy);
	if (!$cats) {
		return;
	}

	$tags = get_the_terms($object_id, $tag_taxonomy);
	if (!$tags) {
		return;
	}

	foreach ($cats as $cat) {

		$cat_id = $cat->term_id;
		if ($cat_id) {

			foreach ($tags as $tag) {

				$tag_id = $tag->term_id;
				wnd_update_tag_under_cat($cat_id, $tag_id, $tag_taxonomy, $inc = 0);

			}
			unset($tag);

		}

	}
	unset($cat);

}

//############################################################################ 删除term时，更新 tag under category数据
// do_action( 'pre_delete_term', $term, $taxonomy );
add_action('pre_delete_term', '', $priority = 10, $accepted_args = 2);
function wnd_pre_delete_term_action($term_id, $taxonmy) {

	global $wpdb;
	if (strpos($taxonmy, '_tag')) {
		$wpdb->delete($wpdb->wnd_terms, array('tag_id' => $term_id));
	} else {
		$wpdb->delete($wpdb->wnd_terms, array('cat_id' => $term_id));
	}

}

//############################################################################ 获取当前分类下的标签列表
function wnd_tag_list_under_cat($args = array()) {

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
	$tag_array = wp_cache_get($cat_id . $tag_taxonomy, 'wnd_tag_list_under_cat');

	// 缓存无效
	if ($tag_array === false) {

		global $wpdb;

		// 一个分类下可能对应多个tag类型此处区分
		if ($tag_taxonomy == 'any') {
			$tag_array = $wpdb->get_results(
				$wpdb->prepare("SELECT * FROM $wpdb->wnd_terms WHERE cat_id = %d ORDER BY count DESC LIMIT %d", $cat_id, $limit)
			);
		} else {
			$tag_array = $wpdb->get_results(
				$wpdb->prepare("SELECT * FROM $wpdb->wnd_terms WHERE cat_id = %d AND tag_taxonomy = %s ORDER BY count DESC LIMIT %d", $cat_id, $tag_taxonomy, $limit)
			);
		}

		// 缓存查询结果
		wp_cache_set($cat_id . $tag_taxonomy, $tag_array, 'wnd_tag_list_under_cat', 3600);
	}

	if ($tag_array) {

		$tag_list = '<ul id="tag-list-under-' . $cat_id . '"  class="tag-list" >' . PHP_EOL;
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

}

//############################################################################ 获取指定taxonomy的分类列表并附带下属标签
function wnd_cat_list_with_tags($cat_taxonomy, $tag_taxonomy = 'any', $limit = 10, $show_count = false, $hide_empty = 0) {

	$args = array('hide_empty' => $hide_empty, 'orderby' => 'count', 'order' => 'DESC');
	$terms = get_terms($cat_taxonomy, $args);

	if (!empty($terms) && !is_wp_error($terms)) {

		echo '<div id="' . $cat_taxonomy . '-list-with-tags" class="cat-list-with-tags">' . PHP_EOL;

		foreach ($terms as $term) {

			// 获取分类
			echo '<div id="cat-' . $term->term_id . '" class="cat-with-tags">' . PHP_EOL . '<h3><i class="iconfont"></i><a href="' . get_term_link($term) . '">' . $term->name . '</a></h3>' . PHP_EOL;
			// 获取分类下的标签
			wnd_tag_list_under_cat(array(
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
