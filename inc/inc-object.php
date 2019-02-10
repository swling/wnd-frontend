<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.01.30 写入支付数据库
 *@return object id  或者 false
 */
function wnd_insert_object($object_arr) {

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

	// 权限判断
	$wnd_can_insert_object = apply_filters('wnd_can_insert_object', array('status' => 1, 'msg' => '默认通过'), $object_arr);
	if ($wnd_can_insert_object['status'] === 0) {
		return $wnd_can_insert_object;
	}

	// 更新
	if ($object_arr['ID']) {
		return wnd_update_object($object_arr);
	}

	// 写入object数据库
	global $wpdb;
	$object_id = $wpdb->insert($wpdb->wnd_objects, $object_arr);

	// add action hook
	if ($object_id) {
		do_action('wnd_insert_object', $object_id, $user_id);
	}

	return $object_id;

}

/**
 *@since 2019.01.30 更新支付数据
 *采用类似wp update post的格式，必须输入主键ID，以数组形式注入更新
 */
function wnd_update_object($object_arr) {

	if (!$object_arr['ID']) {
		return false;
	}

	global $wpdb;
	$object = $wpdb->get_row("SELECT * FROM $wpdb->wnd_objects WHERE ID = {$object_arr['ID']}", ARRAY_A);
	if (!$object) {
		return;
	}

	$object_arr = array_merge($object, $object_arr);

	$object_id = $wpdb->update(
		$wpdb->wnd_objects,
		$object_arr,
		array('ID' => $object_arr['ID'])
	);

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
function wnd_get_objects_by_object($post_id) {

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
function wnd_get_objects_by_user($user_id) {

	if (!$user_id) {
		return array();
	}

	global $wpdb;
	$objects = $wpdb->get_results("SELECT * FROM $wpdb->wnd_objects WHERE user_id = {$user_id}", ARRAY_A);
	return $objects;
}
