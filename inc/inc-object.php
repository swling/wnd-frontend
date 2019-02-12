<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.01.30 写入支付数据库
 *@return int 成功返回主键ID 失败返回 0
 */
function wnd_insert_object($object_arr) {

	global $wpdb;

	//数据组合
	$user_id = get_current_user_id();
	$defaults = array(
		'object_id' => 0,
		'user_id' => $user_id,
		'content' => '',
		'title' => '',
		'type' => '',
		'status' => '',
		'value' => '',
		'time' => time(),
		'parent' => 0,
	);
	$object_arr = wp_parse_args($object_arr, $defaults);

	// 数据过滤
	$object_arr['content'] = sanitize_textarea_field($object_arr['content']);
	$object_arr['title'] = sanitize_text_field($object_arr['title']);
	$object_arr['type'] = sanitize_text_field($object_arr['type']);
	$object_arr['status'] = sanitize_text_field($object_arr['status']);
	$object_arr['value'] = sanitize_text_field($object_arr['value']);

	// 权限判断
	$wnd_can_insert_object = apply_filters('wnd_can_insert_object', array('status' => 1, 'msg' => '默认通过'), $object_arr);
	if ($wnd_can_insert_object['status'] === 0) {
		return $wnd_can_insert_object;
	}

	// 更新
	if (!empty($object_arr['ID'])) {
		$action = $wpdb->update(
			$wpdb->wnd_objects,
			$object_arr,
			array('ID' => $object_arr['ID'])
		);
		if ($action) {
			return $object_arr['ID'];
		} else {
			return 0;
		}
	}

	// 写入
	if (false === $wpdb->insert($wpdb->wnd_objects, $object_arr)) {
		return 0;
	}
	/**
	 *@since 2019.02.11
	 *执行数据插入后，应该立即使用 $wpdb->insert_id 否则可能导致返回的主键错误
	 */
	$object_id = (int) $wpdb->insert_id;

	do_action('wnd_insert_object', $object_id, $object_arr['type']);

	return $object_id;

}

/**
 *@since 2019.01.30 更新支付数据
 *采用类似wp update post的格式，必须输入主键ID，以数组形式注入更新
 */
function wnd_update_object($object_arr) {

	if (empty($object_arr['ID'])) {
		return false;
	}

	global $wpdb;
	$object = $wpdb->get_row("SELECT * FROM $wpdb->wnd_objects WHERE ID = {$object_arr['ID']}", ARRAY_A);
	if (!$object) {
		return;
	}

	$object_arr = array_merge($object, $object_arr);
	$object_id = wnd_insert_object($object_arr);

	// add action hook
	if ($object_id) {
		do_action('wnd_update_object', $object_id);
	}

	return $object_id;

}

/**
 *@since 2019.01.31 获取指定ID对象数据
 *@return OBJECT
 */
function wnd_get_object($ID) {

	if (!$ID) {
		return array();
	}

	global $wpdb;
	$object = $wpdb->get_row("SELECT * FROM $wpdb->wnd_objects WHERE ID = {$ID}", OBJECT);
	return $object;
}

/**
 *@since 2019.01.31 获取指定文章对象数据
 */
function wnd_get_objects_by_object($post_id, $type = '', $status = '') {

	if (!$post_id) {
		return array();
	}

	global $wpdb;
	$objects = $wpdb->get_results("SELECT * FROM $wpdb->wnd_objects WHERE object_id = {$post_id}", ARRAY_A);
	return $objects;
}

/**
 *@since 2019.01.31 获取指定用户对象数据
 */
function wnd_get_objects_by_user($user_id, $type = '', $status = '') {

	if (!$user_id) {
		return array();
	}

	global $wpdb;
	$objects = $wpdb->get_results("SELECT * FROM $wpdb->wnd_objects WHERE user_id = {$user_id}", ARRAY_A);
	return $objects;
}
