<?php
namespace Wnd\Model;

/**
 *@since 2019.09.25
 *Nonce
 */
class Wnd_Form_Nonce {

	/**
	 *构建表单字段
	 *
	 *@since 2019.10.27
	 */
	public static function create(array $form_names) {
		// nonce自身字段也需要包含在内
		$form_names[] = '_wnd_form_nonce';

		// 去重排序后生成nonce
		$form_names = array_unique($form_names);
		sort($form_names);
		return wp_create_nonce(md5(implode('', $form_names)));
	}

	/**
	 *@since 2019.05.09 校验表单字段是否被篡改
	 *
	 *@see Wnd_Form_WP -> build_form_nonce()
	 */
	public static function verify() {
		if (!isset($_POST['_wnd_form_nonce'])) {
			return false;
		}

		// 提取POST $_FILES数组键值，去重并排序
		$form_names = array_merge(array_keys($_POST), array_keys($_FILES));
		$form_names = array_unique($form_names);
		sort($form_names);

		// 校验数组键值是否一直
		return wp_verify_nonce($_POST['_wnd_form_nonce'], md5(implode('', $form_names)));
	}
}
