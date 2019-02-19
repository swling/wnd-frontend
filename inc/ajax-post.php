<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 初始化
 *保存提交数据
 *@return array
 **/
function wnd_insert_post($update_id = 0) {

	if (empty($_POST)) {
		return array('status' => 0, 'msg' => '数据为空');
	}

	// 遍历表单提交的数据，并合并到对应的初始化数组中
	$meta_array = wnd_get_form_data($form_date_type = 'post', 'meta_array');
	$wp_meta_array = wnd_get_form_data($form_date_type = 'post', 'wp_meta_array');
	$post_array_temp = wnd_get_form_data($form_date_type = 'post', 'post_array');
	$term_array = wnd_get_form_data($form_date_type = 'post', 'term_array');

	// 组合数据
	$user_id = get_current_user_id();
	$post_id = (int) $_POST['_post_post_id'] ?? 0;
	$update_id = $update_id ?: $post_id;
	$post_type = $_POST['_post_post_type'] ?? 'post';

	// 更新文章
	if ($update_id) {

		$post_type = get_post_type($update_id);
		if (!$post_type) {
			return array('status' => 0, 'msg' => 'ID无效！');
		}

		// 更新权限过滤
		if (!current_user_can('edit_post', $update_id)) {
			return array('status' => 0, 'msg' => '权限错误！');
		}
	}

	/**
	 *@since 2019.02.19
	 * 写入post type检测
	 */
	$allowed_post_types = apply_filters( '_wnd_allowed_post_types', get_post_types(array('public' => true), 'names', 'and') );
	if (!$update_id and !in_array($post_type, $allowed_post_types)) {
		return array('status' => 0, 'msg' => '类型无效！');
	}

	// 写入及更新权限过滤
	$can_insert_post = apply_filters('wnd_can_insert_post', array('status' => 1, 'msg' => '默认通过'), $post_type, $update_id);
	if ($can_insert_post['status'] === 0) {
		return $can_insert_post;
	}

	// 文章状态过滤
	$post_status = apply_filters('wnd_post_status', 'pending', $post_type, $update_id);

	// 初始化文章数组
	if (!$update_id) {
		// 判断是否为更新
		$post_array = array('post_author' => $user_id, 'post_type' => $post_type, 'post_status' => $post_status);

		//更新内容，只只允许更新状态及白名单内的字段防止用户通过编辑文章，改变文章类型等敏感数据
	} else {
		$post_array = array('ID' => $update_id, 'post_status' => $post_status);
	}
	// 最终post array数据
	$post_array = array_merge($post_array, $post_array_temp);

	// 写入文章
	if (!$update_id) {
		$post_id = wp_insert_post($post_array);
	}

	// 更新文章
	else {
		$post_id = wp_update_post($post_array);
	}

	if (!is_wp_error($post_id)) {

		// 更新字段，分类，及标签
		wnd_update_post_meta_and_term($post_id, $meta_array, $wp_meta_array, $term_array);

		// 完成返回
		$redirect_to = $_REQUEST['redirect_to'] ?? null;
		if ($redirect_to) {
			$return_array = array('status' => 3, 'msg' => $redirect_to);
		} else if ($update_id) {
			$return_array = array('status' => 2, 'msg' => get_permalink($post_id));
		} else {
			$return_array = array('status' => 3, 'msg' => get_permalink($post_id));
		}

		// 写入失败，返回错误信息
	} else {

		$return_array = array('status' => 0, 'msg' => $post_id->get_error_message());

	}

	// 返回值过滤
	$return_array = apply_filters('wnd_insert_post_return', $return_array, $post_type, $post_id);

	return $return_array;

}

/**
 *@since 初始化
 *@return array
 *更新文章
 */
function wnd_update_post($post_id = 0) {

	// 获取被编辑post
	$post_id = $post_id ?: (int) $_POST['_post_post_id'];
	$edit_post = get_post($post_id);
	if (!$edit_post) {
		return array('status' => 0, 'msg' => '获取内容ID失败！');
	}

	return wnd_insert_post($post_id);

}

/**
 *@since 2019.01.21
 *@return array
 *前端快速更改文章状态
 *依赖：wp_update_post、wp_delete_post
 */
function wnd_update_post_status() {

	// 获取数据
	$post_id = (int) $_POST['post_id'];
	$before_post = get_post($post_id);
	if (!$before_post) {
		return array('status' => 0, 'msg' => '获取内容失败！');
	}

	$after_status = $_POST['post_status'];

	// 权限检测
	$can_array = array('status' => current_user_can('edit_post', $post_id) ? 1 : 0, 'msg' => '权限错误！');
	$can_update_post_status = apply_filters('wnd_can_update_post_status', $can_array, $before_post, $after_status);
	if ($can_update_post_status['status'] == 0) {
		return $can_update_post_status;
	}

	// 删除文章
	if ($after_status == 'delete') {
		// 无论是否设置了$force_delete 自定义类型的文章都会直接被删除
		$delete = wp_delete_post($post_id, $force_delete = false);
		if ($delete) {
			return array('status' => 1, 'msg' => '内容已删除！');
		} else {
			return array('status' => 0, 'msg' => '操作失败，请检查！');
		}
	}

	//执行更新
	$post_data = array(
		'ID' => $post_id,
		'post_status' => $after_status,
	);
	$update = wp_update_post($post_data);

	// 完成更新
	if ($update) {
		return array('status' => 1, 'msg' => '更新成功！');

		//更新失败
	} else {
		return array('status' => 0, 'msg' => '更新数据失败！');
	}

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
 *通过本函数，先查询当前用户的草稿，如果存在获取最近一篇草稿用于编辑
 *如果没有草稿，则创建一篇新文章
 *@since 2018.11.12
 *	默认：获取当前用户，一天以前编辑过的文章（一天内更新过的文章表示正处于编辑状态）
 */
function wnd_get_draft_post($post_type = 'post', $interval_time = 3600 * 24) {

	$user_id = get_current_user_id();

	// 未登录
	if (!$user_id) {
		return array('status' => 0, 'msg' => '未登录用户！');
	}

	/**
	 *@since 2019.02.19 只允许已注册的公开的post type
	 */
	if (!in_array($post_type, get_post_types(array('public' => true), 'names', 'and'))) {
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
			return array('status' => 1, 'msg' => $post_id);
		} else {
			return array('status' => 0, 'msg' => $post_id->get_error_message());
		}

		//2、当前用户没有草稿，查询其他用户超过指定时间未编辑的草稿
	} else {

		// 设置时间条件 更新自动草稿时候，modified 不会变需要查询 post date
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
			$post_id = wp_update_post(array('ID' => $post_id, 'post_status' => 'auto-draft', 'post_title' => 'Auto-draft', 'post_author' => $user_id));

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
				return array('status' => 1, 'msg' => $post_id);
			} else {
				return array('status' => 0, 'msg' => $post_id->get_error_message());
			}

		}

	}

	//3、 全站没有可用草稿，创建新文章用于编辑
	$post_id = wp_insert_post(array('post_title' => 'Auto-draft', 'post_name' => '', 'post_type' => $post_type, 'post_author' => $user_id, 'post_status' => 'auto-draft'));
	if (!is_wp_error($post_id)) {
		return array('status' => 2, 'msg' => $post_id);
	} else {
		return array('status' => 0, 'msg' => $post_id->get_error_message());
	}

}
