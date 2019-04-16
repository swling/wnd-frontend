<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 初始化
 *保存提交数据
 *@param $_POST 	表单数据
 *@return array
 **/
function wnd_ajax_insert_post($update_id = 0) {

	if (empty($_POST)) {
		return array('status' => 0, 'msg' => '数据为空');
	}

	// 实例化当前提交的表单数据
	$form_data = new Wnd_Form_Data();
	$post_array_temp = $form_data->get_post_array();
	$meta_array = $form_data->get_post_meta_array();
	$wp_meta_array = $form_data->get_wp_post_meta_array();
	$term_array = $form_data->get_term_array();

	// 组合数据
	$user_id = get_current_user_id();
	$post_id = $_POST['_post_ID'] ?? 0;
	$post_parent = $_POST['_post_post_parent'] ?? 0;
	$update_id = $update_id ?: $post_id;
	$post_type = $_POST['_post_post_type'] ?? 'post';
	$post_name = $_POST['_post_post_name'] ?? uniqid();

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
	 *@since 2019.04.15 废弃
	 */
	// if (!$update_id and !in_array($post_type, wnd_get_allowed_post_types())) {
	// 	return array('status' => 0, 'msg' => '类型无效！');
	// }

	// 写入及更新权限过滤
	$can_insert_post = apply_filters('wnd_can_insert_post', array('status' => 1, 'msg' => '默认通过'), $post_type, $update_id);
	if ($can_insert_post['status'] === 0) {
		return $can_insert_post;
	}

	// 文章状态过滤
	$post_status = apply_filters('wnd_insert_post_status', 'pending', $post_type, $update_id);

	// 初始化文章数组
	if (!$update_id) {
		// 判断是否为更新
		$post_array = array(
			'post_author' => $user_id,
			'post_type' => $post_type,
			'post_status' => $post_status,
			'post_parent' => $post_parent,
			'post_name' => $post_name,
		);

		//更新内容，只只允许更新状态及白名单内的字段防止用户通过编辑文章，改变文章类型等敏感数据
	} else {
		$post_array = array(
			'ID' => $update_id,
			'post_status' => $post_status,
			'post_parent' => $post_parent,
		);
	}
	// 最终post array数据
	$post_array = array_merge($post_array_temp, $post_array);

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
 *@param $_POST 	表单数据
 *@param $post_id 	文章id
 *@return array
 *更新文章
 */
function wnd_ajax_update_post($post_id = 0) {

	// 获取被编辑post
	$post_id = $post_id ?: (int) $_POST['_post_ID'];
	$edit_post = get_post($post_id);
	if (!$edit_post) {
		return array('status' => 0, 'msg' => '获取内容ID失败！');
	}

	return wnd_ajax_insert_post($post_id);

}

/**
 *@since 2019.01.21
 *@param  $_POST['post_id']
 *@param  $_POST['post_status']
 *@return array
 *前端快速更改文章状态
 *依赖：wp_update_post、wp_delete_post
 */
function wnd_ajax_update_post_status() {

	// 获取数据
	$post_id = (int) $_POST['post_id'];
	$before_post = get_post($post_id);
	if (!$before_post) {
		return array('status' => 0, 'msg' => '获取内容失败！');
	}

	$after_status = $_POST['post_status'];

	// 在现有注册的post status基础上新增 delete，该状态表示直接删除文章 @since 2019.03.03
	if (!in_array($after_status, array_merge(get_post_stati(), array('delete')))) {
		return array('status' => 0, 'msg' => '未注册的状态！');
	}

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
