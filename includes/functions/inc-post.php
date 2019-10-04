<?php
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
 *
 *@param string 	$title
 *@param int 	$exclude_id
 *@param string 	$post_type
 *
 *@return int|false
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
 *
 *@param string $post_name
 *@param string $post_type
 *@param string $post_status
 *
 *@return object|null
 */
function wnd_get_post_by_slug($post_name, $post_type = 'post', $post_status = 'publish') {
	global $wpdb;
	$post_name = urlencode($post_name);

	if ('any' == $post_status) {
		$post = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->posts WHERE post_name = %s AND post_type = %s LIMIT 1",
				$post_name,
				$post_type
			)
		);
	} else {
		$post = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->posts WHERE post_name = %s AND post_type = %s AND post_status = %s LIMIT 1",
				$post_name,
				$post_type,
				$post_status
			)
		);
	}

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

	$max              = wnd_get_option('wnd', 'wnd_max_stick_posts') ?: 10;
	$old_sticky_posts = wnd_get_option('wnd_sticky_posts', $post_type);
	$old_sticky_posts = is_array($old_sticky_posts) ? $old_sticky_posts : array();

	// 创建以post+id作为键名，id作为键值的数组，并合并入数组（注意顺序）
	$sticky_post      = array('post' . $post_id => $post_id);
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
