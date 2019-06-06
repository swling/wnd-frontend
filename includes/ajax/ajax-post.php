<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 初始化
 *保存提交数据
 *@param 	$_POST 						全局表单数据
 *@param 	$verify_form_nonce  bool 	是否校验表单数据来源
 *@return 	array 						操作结果
 **/
function wnd_ajax_insert_post($verify_form_nonce = true) {

	if (empty($_POST)) {
		return array('status' => 0, 'msg' => '数据为空');
	}

	// 实例化当前提交的表单数据
	$form_data = new Wnd_Form_Data($verify_form_nonce);
	$post_array = $form_data->get_post_array();
	$meta_array = $form_data->get_post_meta_array();
	$wp_meta_array = $form_data->get_wp_post_meta_array();
	$term_array = $form_data->get_term_array();

	// 组合数据
	$update_id = $post_array['ID'] ?? 0;
	$post_parent = $post_array['post_parent'] ?? 0;
	$post_type = $post_array['post_type'] ?? 'post';
	$post_name = $post_array['post_name'] ?? uniqid();

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
	 *限制ajax可以创建的post类型，避免功能型post被意外创建
	 *功能型post应通常具有更复杂的权限控制，并wp_insert_post创建
	 */
	if (!in_array($post_type, wnd_get_allowed_post_types())) {
		return array('status' => 0, 'msg' => '类型无效！');
	}

	// 写入及更新权限过滤
	$can_insert_post = apply_filters('wnd_can_insert_post', array('status' => 1, 'msg' => '默认通过'), $post_type, $update_id);
	if ($can_insert_post['status'] === 0) {
		return $can_insert_post;
	}

	// 文章状态过滤
	$post_status = apply_filters('wnd_insert_post_status', 'pending', $post_type, $update_id);

	// 判断是否为更新
	if (!$update_id) {
		$_post_array = array(
			'post_type' => $post_type,
			'post_status' => $post_status,
			'post_name' => $post_name,
		);

		//更新内容，只只允许更新状态及白名单内的字段防止用户通过编辑文章，改变文章类型等敏感数据
	} else {
		$_post_array = array(
			'ID' => $update_id,
			'post_type' => $post_type,
			'post_status' => $post_status,
		);
	}
	// 最终post array数据
	$post_array = array_merge($post_array, $_post_array);

	// 写入或更新文章
	if (!$update_id) {
		$post_id = wp_insert_post($post_array);
	} else {
		$post_id = wp_update_post($post_array);
	}

	// 写入失败，返回错误信息
	if (is_wp_error($post_id)) {
		return array('status' => 0, 'msg' => $post_id->get_error_message());
	}

	// 更新字段，分类，及标签
	wnd_update_post_meta_and_term($post_id, $meta_array, $wp_meta_array, $term_array);

	// 完成返回
	$redirect_to = $_REQUEST['redirect_to'] ?? null;
	$permalink = get_permalink($post_id);

	if ($redirect_to) {
		$return_array = array(
			'status' => 3,
			'msg' => '发布成功！',
			'data' => array(
				'id' => $post_id,
				'url' => $permalink,
				'redirect_to' => $redirect_to,
			),
		);
	} else if ($update_id) {
		$return_array = array(
			'status' => 2,
			'msg' => '发布成功！',
			'data' => array(
				'id' => $post_id,
				'url' => $permalink,
			),
		);
	} else {
		$return_array = array(
			'status' => 3,
			'msg' => '发布成功！',
			'data' => array(
				'id' => $post_id,
				'url' => $permalink,
				'redirect_to' => $permalink,
			),
		);
	}

	// 返回值过滤
	return apply_filters('wnd_insert_post_return', $return_array, $post_type, $post_id);

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

	$_POST['_post_ID'] = $post_id;
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
	if ($can_update_post_status['status'] === 0) {
		return $can_update_post_status;
	}

	// 删除文章
	if ($after_status == 'delete') {
		// 无论是否设置了$force_delete 自定义类型的文章都会直接被删除
		$delete = wp_delete_post($post_id, true);
		if ($delete) {
			return array('status' => 5, 'msg' => '已删除！');
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
		return array('status' => 5, 'msg' => '更新成功！');

		//更新失败
	} else {
		return array('status' => 0, 'msg' => '更新数据失败！');
	}

}
