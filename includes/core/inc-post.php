<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 初始化
 *批量设置文章 meta 及 term
 */
function wnd_update_post_meta_and_term($post_id, $meta_array, $wp_meta_array, $term_array) {
	if (!get_post($post_id)) {
		return false;
	}

	// 设置wnd post meta
	if (!empty($meta_array) && is_array($meta_array)) {
		wnd_update_post_meta_array($post_id, $meta_array);
	}

	// 设置原生 post meta
	if (!empty($wp_meta_array) && is_array($wp_meta_array)) {
		foreach ($wp_meta_array as $key => $value) {
			// 空值，如设置了表单，但无数据的情况，过滤
			if ('' == $value) {
				delete_post_meta($post_id, $key);
			} else {
				update_post_meta($post_id, $key, $value);
			}

		}
		unset($key, $value);
	}

	//  设置 term
	if (!empty($term_array) && is_array($term_array)) {
		foreach ($term_array as $taxonomy => $term) {
			if ($term != '-1') {
				//排除下拉菜单为选择的默认值
				wp_set_post_terms($post_id, $term, $taxonomy, 0);
			}
		}
		unset($taxonomy, $term);
	}
}

/**
 *@since 2019.02.19
 *当前用户可以写入或管理的文章类型
 *@return array : post type name数组
 */
function wnd_get_allowed_post_types() {
	$post_types = get_post_types(array('public' => true), 'names', 'and');
	// 排除页面/站内信
	unset($post_types['page'], $post_types['mail']);
	return apply_filters('wnd_allowed_post_types', $post_types);
}

/**
 *@since 初始化
 *标题去重
 *@return int or false
 */
function wnd_is_title_duplicated($title, $exclude_id = 0, $post_type = 'post') {
	if (empty($title)) {
		return false;
	}

	global $wpdb;
	$results = $wpdb->get_var($wpdb->prepare(
		"SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = %s AND  ID != %d  limit 1",
		$title,
		$post_type,
		$exclude_id
	));

	return $results ?: false;
}

/**
 *@since 2019.02.17 根据post name 获取post
 *@return post object or null
 */
function wnd_get_post_by_slug($post_name, $post_type = 'post', $post_status = 'publish') {
	global $wpdb;
	$post_name = urlencode($post_name);
	$post = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_name = %s AND post_type = %s AND post_status = %s LIMIT 1", $post_name, $post_type, $post_status));
	if ($post) {
		return $post[0];
	}
	return false;
}

/**
 *@since 2019.06.11
 *精选置顶文章
 *精选post id存储方式：
 *option：二维数组 wnd_sticky_posts[$post_type]['post'.$post_id]
 *@param $post_id
 **/
function wnd_stick_post($post_id) {
	$post_type = get_post_type($post_id);
	if (!$post_type) {
		return;
	}

	$max = wnd_get_option('wnd', 'wnd_max_stick_posts') ?: 10;
	$old_sticky_posts = wnd_get_option('wnd_sticky_posts', $post_type);
	$old_sticky_posts = is_array($old_sticky_posts) ? $old_sticky_posts : array();

	// 创建以post+id作为键名，id作为键值的数组，并合并入数组（注意顺序）
	$sticky_post = array('post' . $post_id => $post_id);
	$new_sticky_posts = array_merge($sticky_post, $old_sticky_posts);

	// 仅保留指定个数元素（按最新）
	$new_sticky_posts = array_slice($new_sticky_posts, 0, $max);

	return wnd_update_option('wnd_sticky_posts', $post_type, $new_sticky_posts);
}

/**
 *@since 2019.06.11
 *取消精选置顶文章
 *@param $post_id
 **/
function wnd_unstick_post($post_id) {
	$post_type = get_post_type($post_id);
	if (!$post_type) {
		return;
	}

	$sticky_posts = wnd_get_option('wnd_sticky_posts', $post_type);
	$sticky_posts = is_array($sticky_posts) ? $sticky_posts : array();

	// 移除指定post id
	unset($sticky_posts['post' . $post_id]);

	return wnd_update_option('wnd_sticky_posts', $post_type, $sticky_posts);
}

/**
 *@since 2019.06.11
 *获取精选置顶文章
 *@param 	$post_type 		文章类型
 *@return 	array or false 	文章id数组
 **/
function wnd_get_sticky_posts($post_type) {
	$sticky_posts = wnd_get_option('wnd_sticky_posts', $post_type);
	return is_array($sticky_posts) ? $sticky_posts : array();
}
