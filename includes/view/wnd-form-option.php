<?php
namespace Wnd\View;

use Exception;

/**
 *适配本插件的 ajax User Option 类
 *@since 2020.06.24
 */
class Wnd_Form_Option extends Wnd_Form_WP {

	protected $option_name;

	/**
	 *存储在 Wnd option中 : _option_{$option_name}_{$option_key}
	 * - 表单name：_option_wnd_logo
	 * - 存储数据：get_option['wnd'][logo]
	 *
	 *为准确匹配本规则，要求option name不得包含下划线
	 */
	public function __construct($option_name) {
		if (false !== stripos($option_name, '_')) {
			throw new Exception(__('$option_name 不得包含下划线', 'wnd'));
		}

		// 继承基础变量
		parent::__construct();

		// 本类设置
		$this->option_name = $option_name;
		$this->add_hidden('option_name', $option_name);
		$this->set_action('wnd_update_option');
	}

	/**
	 *@since 2019.04.28 上传字段简易封装
	 *如需更多选项，请使用 add_image_upload、add_file_upload 方法 @see Wnd_Form_WP
	 */
	public function add_image_upload($option_key, $save_width = 0, $save_height = 0, $label = '') {
		$args = [
			'label'         => $label,
			'thumbnail'     => WND_URL . 'static/images/default.jpg',
			'data'          => [
				'meta_key'    => $this->build_from_name($option_key),
				'save_width'  => $save_width,
				'save_height' => $save_height,
			],
			'delete_button' => true,
		];
		parent::add_image_upload($args);
	}

	public function add_file_upload($option_key, $label = '文件上传') {
		parent::add_file_upload(
			[
				'label' => $label,
				'data'  => [
					'meta_key' => $this->build_from_name($option_key),
				],
			]
		);
	}

	/**
	 *根据规则统一构造表单name值
	 */
	protected function build_from_name($option_key): string {
		return '_option_' . $this->option_name . '_' . $option_key;
	}

	/**
	 *解析表单名，获取对应 option_name / option_key
	 *为准确匹配本规则，要求 option_name 不得包含下划线
	 */
	protected static function parse_form_name($form_name): array{
		$arr = explode('_', $form_name);

		$data                = [];
		$data['option_name'] = $arr[2] ?? '';
		$data['option_key']  = $arr[3] ?? '';
		return $data;
	}

	/**
	 *_option_wnd_logo
	 *返回：wnd
	 */
	public static function get_option_name_by_form_name($form_name) {
		extract(static::parse_form_name($form_name));

		return $option_name;
	}

	/**
	 *_option_wnd_logo
	 *返回：logo
	 */
	public static function get_option_key_by_form_name($form_name) {
		extract(static::parse_form_name($form_name));

		return $option_key;
	}

	/**
	 *获取值
	 *_option_wnd_logo
	 *返回：wnd_get_option('wnd', 'logo')
	 */
	public static function get_option_value_by_form_name($form_name) {
		extract(static::parse_form_name($form_name));

		return wnd_get_option($option_name, $option_key);
	}

	/**
	 *_option_wnd_logo
	 *
	 *更新：wnd_update_option('wnd', 'logo', $value)
	 */
	public static function update_option_by_form_name($form_name, $value) {
		extract(static::parse_form_name($form_name));

		wnd_update_option($option_name, $option_key, $value);
	}

	/**
	 *_option_wnd_logo
	 *
	 *删除：wnd_delete_option('wnd', 'logo')
	 */
	public static function delete_option_by_form_name($form_name) {
		extract(static::parse_form_name($form_name));

		wnd_delete_option($option_name, $option_key);
	}
}
