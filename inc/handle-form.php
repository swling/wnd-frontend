<?php

/**
 *############################################
 *允许通过表单更新的用户及文章字段
 *后台保存表单数据检测name是否在下面允许列表中，否则舍弃
 *@since 2018.7.30
 */
function wnd_get_allowed_fields($subject) {

	$wndwp_options = get_option('wndwp');

	$allowed_post_field = explode(',', $wndwp_options['wnd_allowed_post_field']);
	$allowed_post_meta_key = explode(',', $wndwp_options['wnd_allowed_post_meta_key']);
	$allowed_wp_post_meta_key = explode(',', $wndwp_options['wnd_allowed_wp_post_meta_key']);

	$allowed_user_meta_key = explode(',', $wndwp_options['wnd_allowed_user_meta_key']);
	$allowed_wp_user_meta_key = explode(',', $wndwp_options['wnd_allowed_wp_user_meta_key']);

	switch ($subject) {

	case 'post_field':
		return $allowed_post_field;
		break;

	case 'post_meta_key':
		return $allowed_post_meta_key;
		break;

	case 'wp_post_meta_key':
		return $allowed_wp_post_meta_key;
		break;

	case 'user_meta_key':
		return $allowed_user_meta_key;
		break;

	case 'wp_user_meta_key':
		return $allowed_wp_user_meta_key;
		break;

	default:
		return array();
		break;
	}

}

/**
 *########################################## my get form data
 *通过遍历全局变量，序列化表单提交的内容
 *@since:2018.7.30
 */

/*
Form name规则：
文章：_post_{field}

文章字段：
_meta_{key}
_wpmeta_{key}

分类：
_term_

用户字段：
_usermeta_{key}
_wpusermeta_{key}

获取参数：
meta_array => 自定义数组字段
wp_meta_array =>  wp原生字段
post_array => 文章数据

user_meta_array => 自定义数组字段
wp_user_meta_array => 原生用户字段

term_array =>自定义分类

 */

function wnd_get_form_data($form_date_type = 'all', $array_name) {

	// 空数据
	if (empty($_POST)) {
		return array();
	}

	$user_meta_array = array(); //自定义数组字段 wnd_user_meta
	$wp_user_meta_array = array(); //WordPress 原生用户字段

	$post_array = array(); //文章字段
	$meta_array = array(); //自定义数组字段 wnd_post_meta
	$wp_meta_array = array(); //WordPress原生文章字段
	$term_array = array(); //分类

	// 通过 === 0 判断被查找字符是否出现在最前端
	foreach ($_POST as $key => $value) {

		if ($form_date_type == 'all' or $form_date_type == 'user') {

			//################### 1、 用户字段
			if (strpos($key, '_wpusermeta_') === 0) {

				// 提交的字段名是否在允许范围内
				$key = str_replace('_wpusermeta_', '', $key);
				$allowed_fields = wnd_get_allowed_fields('wp_user_meta_key');
				if (!in_array($key, $allowed_fields)) {
					continue;
				}

				$array_temp = array($key => $value);
				$wp_user_meta_array = array_merge($wp_user_meta_array, $array_temp);
				continue;

			} elseif (strpos($key, '_usermeta_') === 0) {

				// 提交的字段名是否在允许范围内
				$key = str_replace('_usermeta_', '', $key);
				$allowed_fields = wnd_get_allowed_fields('user_meta_key');
				if (!in_array($key, $allowed_fields)) {
					continue;
				}

				$array_temp = array($key => $value);
				$user_meta_array = array_merge($user_meta_array, $array_temp);
				continue;

			}

		}

		if ($form_date_type == 'all' or $form_date_type == 'post') {

			//################### 2、文章字段

			if (strpos($key, '_post_') === 0) {

				// Allowed fields
				$key = str_replace('_post_', '', $key);
				$allowed_fields = wnd_get_allowed_fields('post_field');
				if (!in_array($key, $allowed_fields)) {
					continue;
				}

				$array_temp = array($key => $value);
				$post_array = array_merge($post_array, $array_temp);
				continue;

			} elseif (strpos($key, '_wpmeta_') === 0) {

				// Allowed fields
				$key = str_replace('_wpmeta_', '', $key);
				$allowed_fields = wnd_get_allowed_fields('wp_post_meta_key');
				if (!in_array($key, $allowed_fields)) {
					continue;
				}

				$array_temp = array($key => $value);
				$wp_meta_array = array_merge($wp_meta_array, $array_temp);
				continue;

			} elseif (strpos($key, '_meta_') === 0) {

				// Allowed fields
				$key = str_replace('_meta_', '', $key);
				$allowed_fields = wnd_get_allowed_fields('post_meta_key');
				if (!in_array($key, $allowed_fields)) {
					continue;
				}

				$array_temp = array($key => $value);
				$meta_array = array_merge($meta_array, $array_temp);
				continue;

			} elseif (strpos($key, '_term_') === 0) {

				$key = str_replace('_term_', '', $key);
				$array_temp = array($key => $value);
				$term_array = array_merge($term_array, $array_temp);
				continue;
			}
		}

	}
	unset($key, $value);

	switch ($array_name) {

	// 文章数组
	case 'meta_array':
		return $meta_array;
		break;

	case 'wp_meta_array':
		return $wp_meta_array;
		break;

	case 'post_array':
		return $post_array;
		break;

	case 'term_array':
		return $term_array;
		break;

	//用户数组
	case 'user_meta_array':
		return $user_meta_array;
		break;

	case 'wp_user_meta_array':
		return $wp_user_meta_array;
		break;

	default:
		return array();
		break;
	}
}