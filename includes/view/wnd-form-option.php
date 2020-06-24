<?php
namespace Wnd\View;

use Exception;
use Wnd\Model\Wnd_Term;
use Wnd\Utility\Wnd_Form_Data;

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
	 *
	 *@param $option_name 	option 名称
	 *@param $append 		是否已附加数据的方式更新（表单中不含的字段，将继续保留），默认将以本表单数据完全替换之前的数据
	 */
	public function __construct(string $option_name, bool $append = false) {
		if (false !== stripos($option_name, '_')) {
			throw new Exception(__('$option_name 不得包含下划线', 'wnd'));
		}

		// 继承基础变量
		parent::__construct();

		// 设置Filter
		$this->filter = __CLASS__;
		add_filter($this->filter, [$this, 'filter'], 10, 1);

		// 本类定义
		$this->option_name = $option_name;
		$this->add_hidden('append', $append ? '1' : '0');
		$this->add_hidden('option_name', $option_name);
		$this->set_action('wnd_update_option');
	}

	/**
	 *统一设置表单字段名前缀
	 *根据表单字段名自动读取option value
	 *
	 *@see $this->build_form_name
	 */
	public function filter(array $input_values): array{
		foreach ($input_values as $key => $input) {
			if (!isset($input_values[$key]['name'])) {
				continue;
			}

			$ignore = ['option_name', 'action', '_ajax_nonce', Wnd_Form_Data::$form_nonce_name];
			if (in_array($input_values[$key]['name'], $ignore)) {
				continue;
			}

			// 表单字段名自动添加统一前缀
			$input_values[$key]['name'] = $this->build_form_name($input['name']);

			// 根据表单字段名读取数据
			$value                          = static::get_option_value_by_form_name($input_values[$key]['name']);
			$input_values[$key]['value']    = !is_array($value) ? $value : '';
			$input_values[$key]['selected'] = $value;
			$input_values[$key]['checked']  = $value;
		}unset($key, $input);

		return $input_values;
	}

	/**
	 *页面下拉
	 */
	public function add_page_select($option_key, $label = '', $required = false) {
		$args = array(
			'depth'                 => 0,
			'child_of'              => 0,
			'selected'              => 0,
			'echo'                  => 1,
			'name'                  => 'page_id',
			'id'                    => '',
			'class'                 => '',
			'show_option_none'      => '',
			'show_option_no_change' => '',
			'option_none_value'     => '',
			'value_field'           => 'ID',
		);
		$pages = get_pages($args);

		$options = [];
		foreach ($pages as $page) {
			$options[$page->post_title] = $page->ID;
		}

		// select
		$this->add_select(
			[
				'name'     => $option_key,
				'options'  => $options,
				'label'    => $label,
				'required' => $required,
				'selected' => '',
			]
		);
	}

	// Term 分类单选下拉：本方法不支持复选
	public function add_term_select($option_key, $args_or_taxonomy, $label = '', $required = true, $dynamic_sub = false) {
		$taxonomy        = is_array($args_or_taxonomy) ? $args_or_taxonomy['taxonomy'] : $args_or_taxonomy;
		$taxonomy_object = get_taxonomy($taxonomy);
		if (!$taxonomy_object) {
			return;
		}

		// 获取taxonomy下的 term 键值对
		$option_data = Wnd_Term::get_terms_data($args_or_taxonomy);
		$option_data = array_merge(['- ' . $taxonomy_object->labels->name . ' -' => -1], $option_data);

		// 新增表单字段
		$this->add_select(
			[
				'name'     => $option_key,
				'options'  => $option_data,
				'required' => $required,
				'selected' => '',
				'label'    => $label,
				'class'    => $taxonomy . ($dynamic_sub ? ' dynamic-sub' : false),
				'data'     => ['child_level' => 0],
			]
		);
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
				'meta_key'    => $this->build_form_name($option_key),
				'save_width'  => $save_width,
				'save_height' => $save_height,
			],
			'delete_button' => true,
		];
		parent::add_image_upload($args);
	}

	/**
	 *文件上传
	 */
	public function add_file_upload($option_key, $label = '文件上传') {
		parent::add_file_upload(
			[
				'label' => $label,
				'data'  => [
					'meta_key' => $this->build_form_name($option_key),
				],
			]
		);
	}

	/**
	 *根据规则统一构造表单name值
	 */
	protected function build_form_name($option_key): string {
		return '_option_' . $this->option_name . '_' . $option_key;
	}

	/**
	 *解析表单名，获取对应 option_name / option_key
	 *为准确匹配本规则，要求 option_name 不得包含下划线
	 */
	protected static function parse_form_name($form_name): array{
		$arr = explode('_', $form_name, 4);

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

		// 可能为多选字段：需要移除'[]'
		$option_key = rtrim($option_key, '[]');

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
