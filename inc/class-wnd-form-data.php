<?php

/**
 *############################################
 *允许通过表单更新的用户及文章字段
 *后台保存表单数据检测name是否在下面允许列表中，否则舍弃
 *@since 2019.03.04
 */
class Wnd_Form_Data {

	static $enable_white_list;
	static $allowed_post_meta_key;
	static $allowed_wp_post_meta_key;
	static $allowed_user_meta_key;
	static $allowed_wp_user_meta_key;
	public $form_data;

	function __construct() {
		Wnd_Form_Data::$enable_white_list = wnd_get_option('wnd', 'wnd_enable_white_list');
		Wnd_Form_Data::$allowed_post_meta_key = explode(',', wnd_get_option('wnd', 'wnd_allowed_post_meta_key'));
		Wnd_Form_Data::$allowed_wp_post_meta_key = explode(',', wnd_get_option('wnd', 'wnd_allowed_wp_post_meta_key'));
		Wnd_Form_Data::$allowed_user_meta_key = explode(',', wnd_get_option('wnd', 'wnd_allowed_user_meta_key'));
		Wnd_Form_Data::$allowed_wp_user_meta_key = explode(',', wnd_get_option('wnd', 'wnd_allowed_wp_user_meta_key'));
		$this->form_data = apply_filters('wnd_form_data', $_POST);
	}

	// 0、获取WordPress user数据数组
	public function get_user_array() {

		$user_array = array();

		foreach ($this->form_data as $key => $value) {

			if (strpos($key, '_user_') === 0) {
				$key = str_replace('_user_', '', $key);
				$user_array = array_merge($user_array, array($key => $value));
			}

		}unset($key, $value);

		return $user_array;
	}

	// 1、获取WordPress原生use meta数据数组
	public function get_wp_user_meta_array() {

		$wp_user_meta_array = array();

		foreach ($this->form_data as $key => $value) {

			if (strpos($key, '_wpusermeta_') === 0) {
				$key = str_replace('_wpusermeta_', '', $key);
				if (Wnd_Form_Data::$enable_white_list == 1 and !in_array($key, Wnd_Form_Data::$allowed_wp_user_meta_key)) {
					continue;
				}
				$wp_user_meta_array = array_merge($wp_user_meta_array, array($key => $value));
			}

		}unset($key, $value);

		return $wp_user_meta_array;
	}

	// 2、获取自定义WndWP user meta数据数组
	public function get_user_meta_array() {

		$user_meta_array = array();

		foreach ($this->form_data as $key => $value) {

			if (strpos($key, '_usermeta_') === 0) {
				$key = str_replace('_usermeta_', '', $key);
				if (Wnd_Form_Data::$enable_white_list == 1 and !in_array($key, Wnd_Form_Data::$allowed_user_meta_key)) {
					continue;
				}
				$user_meta_array = array_merge($user_meta_array, array($key => $value));
			}

		}unset($key, $value);

		return $user_meta_array;
	}

	// 3、获取WordPress原生post meta数据数组
	public function get_post_array() {

		$post_array = array();

		foreach ($this->form_data as $key => $value) {

			if (strpos($key, '_post_') === 0) {
				$key = str_replace('_post_', '', $key);
				$post_array = array_merge($post_array, array($key => $value));
			}

		}unset($key, $value);

		return $post_array;
	}

	// 4、获取WordPress原生post meta数据数组
	public function get_wp_post_meta_array() {

		$wp_post_meta_array = array();

		foreach ($this->form_data as $key => $value) {

			if (strpos($key, '_wpmeta_') === 0) {
				$key = str_replace('_wpmeta_', '', $key);
				if (Wnd_Form_Data::$enable_white_list == 1 and !in_array($key, Wnd_Form_Data::$allowed_wp_post_meta_key)) {
					continue;
				}
				$wp_post_meta_array = array_merge($wp_post_meta_array, array($key => $value));
			}

		}unset($key, $value);

		return $wp_post_meta_array;
	}

	// 5、获取WndWP post meta数据数组
	public function get_post_meta_array() {

		$post_meta_array = array();

		foreach ($this->form_data as $key => $value) {

			if (strpos($key, '_meta_') === 0) {
				$key = str_replace('_meta_', '', $key);
				if (Wnd_Form_Data::$enable_white_list == 1 and !in_array($key, Wnd_Form_Data::$allowed_post_meta_key)) {
					continue;
				}
				$post_meta_array = array_merge($post_meta_array, array($key => $value));
			}

		}unset($key, $value);

		return $post_meta_array;
	}

	// 6、获取WordPress分类：term数组
	public function get_term_array() {

		$term_array = array();

		foreach ($this->form_data as $key => $value) {

			if (strpos($key, '_term_') === 0) {
				$key = str_replace('_term_', '', $key);
				$term_array = array_merge($term_array, array($key => $value));
			}

		}unset($key, $value);

		return $term_array;
	}

}
