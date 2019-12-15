<?php
namespace Wnd\Model;

use Exception;
use Wnd\Model\Wnd_Nonce;

/**
 *根据表单name提取数据
 *@since 2019.03.04
 *
 *@param $verify_form_nonce 	bool 	是否校验表单字段经由Wnd_Form_WP表单类生成
 *@param $_POST 				array 	表单数据
 *
 *
 * 前端表单遵循以下规则定义的name，后台获取后自动提取，并更新到数据库
 *	文章：_post_{$field}
 *
 * 	文章字段：
 *	_meta_{$key} (*自定义数组字段)
 *	_wpmeta_{$key} (*WordPress原生字段)
 *
 * 	Term:
 *	_term_{$taxonomy}(*taxonomy)
 *
 *	用户：_user_{$field}
 *	用户字段：
 *	_usermeta_{$key} (*自定义数组字段)
 *	_wpusermeta_{$key} (*WordPress原生字段)
 *
 */
class Wnd_Form_Data {

	public $form_data;

	public function __construct($verify_form_nonce = true) {
		if (empty($_POST)) {
			throw new Exception('表单数据为空');
		}

		/**
		 *@since 2019.05.10
		 *apply_filters('wnd_form_data', $_POST) 操作可能会直接修改$_POST
		 *因而校验表单操作应该在filter应用之前执行
		 *通过filter添加的数据，自动视为被允许提交的数据
		 */
		if ($verify_form_nonce and !Wnd_Nonce::verify_form_nonce()) {
			throw new Exception('表单已被篡改');
		}

		// 允许修改表单提交数据
		$this->form_data = apply_filters('wnd_form_data', $_POST);
	}

	// 0、获取WordPress user数据数组
	public function get_user_array() {
		$user_array = [];

		foreach ($this->form_data as $key => $value) {
			if (strpos($key, '_user_') === 0) {
				$key              = str_replace('_user_', '', $key);
				$user_array[$key] = $value;
			}
		}unset($key, $value);

		return $user_array;
	}

	// 1、获取WordPress原生use meta数据数组
	public function get_wp_user_meta_array() {
		$wp_user_meta_array = [];

		foreach ($this->form_data as $key => $value) {
			if (strpos($key, '_wpusermeta_') === 0) {
				$key                      = str_replace('_wpusermeta_', '', $key);
				$wp_user_meta_array[$key] = $value;
			}
		}unset($key, $value);

		return $wp_user_meta_array;
	}

	// 2、获取自定义WndWP user meta数据数组
	public function get_user_meta_array() {
		$user_meta_array = [];

		foreach ($this->form_data as $key => $value) {
			if (strpos($key, '_usermeta_') === 0) {
				$key                   = str_replace('_usermeta_', '', $key);
				$user_meta_array[$key] = $value;
			}
		}unset($key, $value);

		return $user_meta_array;
	}

	// 3、获取WordPress原生post meta数据数组
	public function get_post_array() {
		$post_array = [];

		foreach ($this->form_data as $key => $value) {
			if (strpos($key, '_post_') === 0) {
				$key              = str_replace('_post_', '', $key);
				$post_array[$key] = $value;
			}
		}unset($key, $value);

		return $post_array;
	}

	// 4、获取WordPress原生post meta数据数组
	public function get_wp_post_meta_array() {
		$wp_post_meta_array = [];

		foreach ($this->form_data as $key => $value) {
			if (strpos($key, '_wpmeta_') === 0) {
				$key                      = str_replace('_wpmeta_', '', $key);
				$wp_post_meta_array[$key] = $value;
			}
		}unset($key, $value);

		return $wp_post_meta_array;
	}

	// 5、获取WndWP post meta数据数组
	public function get_post_meta_array() {
		$post_meta_array = [];

		foreach ($this->form_data as $key => $value) {
			if (strpos($key, '_meta_') === 0) {
				$key                   = str_replace('_meta_', '', $key);
				$post_meta_array[$key] = $value;
			}
		}unset($key, $value);

		return $post_meta_array;
	}

	// 6、获取WordPress分类：term数组
	public function get_term_array() {
		$term_array = [];

		foreach ($this->form_data as $key => $value) {
			if (strpos($key, '_term_') === 0) {
				$key              = str_replace('_term_', '', $key);
				$term_array[$key] = $value;
			}
		}unset($key, $value);

		return $term_array;
	}

	/**
	 *@since 2019.07.17
	 *获取表单数据
	 *返回表单提交数据
	 *与原$_POST相比，此时获取的表单提交数据，执行了wnd_form_handler filter，并通过了表单一致性校验
	 */
	public function get_form_data() {
		return $this->form_data;
	}
}
