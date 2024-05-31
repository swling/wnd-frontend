<?php
use Wnd\Model\Wnd_Meta;
use Wnd\Model\Wnd_Meta_User;

//############################################################################ User Meta
function wnd_update_user_meta(int $user_id, string $meta_key, $meta_value) {
	$meta = new Wnd_Meta_User($user_id);
	return $meta->update_wnd_meta($meta_key, $meta_value);
}

function wnd_update_user_meta_array(int $user_id, array $update_meta, bool $append = true) {
	$meta = new Wnd_Meta_User($user_id);
	return $meta->update_wnd_meta_array($update_meta, $append);
}

// 获取user meta数组中的元素值
function wnd_get_user_meta(int $user_id, string $meta_key) {
	$meta = new Wnd_Meta_User($user_id);
	return $meta->get_wnd_meta($meta_key);
}

// 删除用户字段数组元素
function wnd_delete_user_meta(int $user_id, string $meta_key) {
	$meta = new Wnd_Meta_User($user_id);
	return $meta->delete_wnd_meta($meta_key);
}

// 用户字段增量函数
function wnd_inc_user_meta(int $user_id, string $meta_key, float $val = 1) {
	$meta = new Wnd_Meta_User($user_id);
	return $meta->inc_wp_meta($meta_key, $val);
}

// 用户数组字段增量函数
function wnd_inc_wnd_user_meta(int $user_id, string $meta_key, float $val = 1, bool $min_zero = false) {
	$meta = new Wnd_Meta_User($user_id);
	return $meta->inc_wnd_meta($meta_key, $val, $min_zero);
}

//############################################################################ Post Meta
function wnd_update_post_meta(int $post_id, string $meta_key, $meta_value) {
	$meta = new Wnd_Meta($post_id);
	return $meta->update_wnd_meta($meta_key, $meta_value);
}

function wnd_update_post_meta_array(int $post_id, array $update_meta, bool $append = true) {
	$meta = new Wnd_Meta($post_id);
	return $meta->update_wnd_meta_array($update_meta, $append);
}

// 获取post meta数组中的元素值
function wnd_get_post_meta(int $post_id, string $meta_key) {
	$meta = new Wnd_Meta($post_id);
	return $meta->get_wnd_meta($meta_key);
}

// 删除文章字段数组元素
function wnd_delete_post_meta(int $post_id, string $meta_key) {
	$meta = new Wnd_Meta($post_id);
	return $meta->delete_wnd_meta($meta_key);
}

// 文章字段增量函数
function wnd_inc_post_meta(int $post_id, string $meta_key, float $val = 1) {
	$meta = new Wnd_Meta($post_id);
	return $meta->inc_wp_meta($meta_key, $val);
}

// 文章数组字段增量函数
function wnd_inc_wnd_post_meta(int $post_id, string $meta_key, float $val = 1, bool $min_zero = false) {
	$meta = new Wnd_Meta($post_id);
	return $meta->inc_wnd_meta($meta_key, $val, $min_zero);
}

//############################################################################ option
function wnd_update_option(string $option_name, string $key, $value): bool {
	$update_array = [$key => $value];
	return wnd_update_option_array($option_name, $update_array);
}

function wnd_update_option_array(string $option_name, array $update_array, bool $append = true): bool {
	if ($append) {
		$old_array = get_option($option_name) ?: [];
		if (!is_array($old_array)) {
			return false;
		}

		$new_array = array_merge($old_array, $update_array);
	} else {
		$new_array = $update_array;
	}

	/**
	 * - 移除空值，但保留 0 @see wnd_array_filter
	 * - 合并后的 option 有效：更新；合并后的 option 为空：删除
	 */
	$new_array = wnd_array_filter($new_array);
	if ($new_array) {
		return update_option($option_name, $new_array);
	} else {
		return delete_option($option_name);
	}
}

// 获取options数组中的元素值
function wnd_get_option(string $option_name, string $meta_key) {
	$array = get_option($option_name);
	return $array[$meta_key] ?? false;
}

// 删除options数组元素
function wnd_delete_option(string $option_name, string $meta_key): bool {
	$array = get_option($option_name);
	if (!$array or !is_array($array)) {
		return false;
	}

	unset($array[$meta_key]);
	return wnd_update_option_array($option_name, $array, false);
}
