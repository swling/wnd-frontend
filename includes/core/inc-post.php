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

	// 设置my post meta
	if (!empty($meta_array) && is_array($meta_array)) {
		wnd_update_post_meta_array($post_id, $meta_array);
	}

	// 设置原生 post meta
	if (!empty($wp_meta_array) && is_array($wp_meta_array)) {
		foreach ($wp_meta_array as $key => $value) {
			// 空值，如设置了表单，但无数据的情况，过滤
			if ($value == '') {
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
			if ($term !== '-1') {
				//排除下拉菜单为选择的默认值
				wp_set_post_terms($post_id, $term, $taxonomy, 0);
			}
		}
		unset($taxonomy, $term);
	}

}

/**
 *如果需要上传图片等，需要在提交文章之前，预先获取一篇文章
 *1、查询当前用户的草稿，如果存在获取最近一篇草稿用于编辑
 *2、若当前用户没有草稿，则查询其他用户超过一天未发布的自动草稿，并初始化标题、清除相关附件等
 *3、以上皆无结果，则创建一篇新文章
 *@since 2018.11.12
 *默认：获取当前用户，一天以前编辑过的文章（一天内更新过的文章表示正处于编辑状态）
 */
function wnd_get_draft_post($post_type = 'post', $interval_time = 86400) {

	$user_id = get_current_user_id();

	// 未登录
	if (!$user_id) {
		return array('status' => 0, 'msg' => '未登录用户！');
	}

	/**
	 *@since 2019.02.19
	 * 写入post type检测
	 *@since 2019.7.17
	 *attachment为附件类型，前端可能会允许编辑更新attachment post信息，但此种类型应该是上传文件后由WordPress自动创建
	 *@see media_handle_upload()
	 */
	if (!in_array($post_type, wnd_get_allowed_post_types()) or $post_type == 'attachment') {
		return array('status' => 0, 'msg' => '类型无效！');
	}

	//1、 查询当前用户否存在草稿，有则调用最近一篇草稿编辑
	$query_array = array(
		'post_status' => 'auto-draft',
		'post_type' => $post_type,
		'author' => $user_id,
		'orderby' => 'ID',
		'order' => 'ASC',
		'cache_results' => false,
		'posts_per_page' => 1,
	);
	$draft_post_array = get_posts($query_array);

	// 有草稿：返回第一篇草稿ID
	if ($draft_post_array) {

		$post_id = $draft_post_array[0]->ID;
		// 更新草稿状态
		$post_id = wp_update_post(array('ID' => $post_id, 'post_status' => 'auto-draft', 'post_title' => 'Auto-draft', 'post_author' => $user_id));
		if (!is_wp_error($post_id)) {
			return array('status' => 1, 'data' => $post_id, 'msg' => '以获取当前用户草稿');
		} else {
			return array('status' => 0, 'msg' => $post_id->get_error_message());
		}

	}

	//2、当前用户没有草稿，查询其他用户超过指定时间未编辑的草稿(更新自动草稿时候，modified 不会变需要查询 post_date)
	$date_query = array(
		array(
			'column' => 'post_date',
			'before' => date('Y-m-d H:i', time() - $interval_time),
		),
	);
	$query_array = array_merge($query_array, array('date_query' => $date_query));
	unset($query_array['author']);
	$draft_post_array = get_posts($query_array);

	// 有符合条件的其他用户创建的草稿
	if ($draft_post_array) {

		$post_id = $draft_post_array[0]->ID;
		// 更新草稿状态
		$post_id = wp_update_post(
			array(
				'ID' => $post_id,
				'post_status' => 'auto-draft',
				'post_title' => 'Auto-draft',
				'post_author' => $user_id,
			)
		);

		//清空之前的附件
		if ($post_id) {
			$attachments = get_children(array('post_type' => 'attachment', 'post_parent' => $post_id));
			foreach ($attachments as $attachment) {
				wp_delete_attachment($attachment->ID, 'true');
			}
			unset($attachment);
		}

		// 返回值
		if (!is_wp_error($post_id)) {
			return array('status' => 1, 'data' => $post_id, 'msg' => '已获取并更新其他用户草稿');
		} else {
			return array('status' => 0, 'msg' => $post_id->get_error_message());
		}

	}

	//3、 全站没有可用草稿，创建新文章用于编辑
	$post_id = wp_insert_post(
		array(
			'post_title' => 'Auto-draft',
			'post_name' => uniqid(),
			'post_type' => $post_type,
			'post_author' => $user_id,
			'post_status' => 'auto-draft',
		)
	);
	if (!is_wp_error($post_id)) {
		return array('status' => 2, 'data' => $post_id, 'msg' => '创建新草稿');
	} else {
		return array('status' => 0, 'msg' => $post_id->get_error_message());
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
function wnd_is_title_repeated($title, $exclude_id = 0, $post_type = 'post') {

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
