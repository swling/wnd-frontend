<?php

/**
 *根据表单name提取标题数据
 *@since 2019.03.04
 */
class Wnd_Form_Data {

	static $enable_white_list;
	public $form_data;

	function __construct() {

		Wnd_Form_Data::$enable_white_list = wnd_get_option('wnd', 'wnd_enable_white_list');

		if (Wnd_Form_Data::$enable_white_list and !$this->verify_nonce()) {
			exit('表单数据为空或已被篡改！');
		}

		// 允许修改表单提交数据
		$this->form_data = apply_filters('wnd_form_data', $_POST);
	}

	/**
	 *@since 2019.05.09 校验表单字段是否被篡改
	 *@see Wnd_Ajax_Form -> build_form_nonce()
	 *此处应该校验全局变量 $_POST 而非 $this->form_data 因为后者可能被filter修改，而修改后的数据与表单提交可能不同
	 *通过filter修改的数据视为自动允许的数据
	 */
	public function verify_nonce() {

		if (!isset($_POST['_wnd_form_nonce'])) {
			return false;
		}

		// 提取POST数组键值并排序
		$form_names = array();
		foreach ($_POST as $key => $value) {
			array_push($form_names, $key);
		}
		unset($key, $value);
		sort($form_names);

		// 校验数组键值是否一直
		return wp_verify_nonce($_POST['_wnd_form_nonce'], md5(implode('', $form_names)));
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
