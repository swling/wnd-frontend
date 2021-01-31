<?php
namespace Wnd\View;

/**
 *Class for creating dynamic Bulma forms.
 *@since 2019.03
 *Base on form-builder @link https://github.com/swling/form-builder
 *@link https://wndwp.com
 *@author swling tangfou@gmail.com
 *这是一个独立的php表单生成器，可于任何PHP环境中使用
 */
class Wnd_Form {

	protected $id;

	protected $form_attr = [];

	protected $form_title;

	protected $is_title_centered = false;

	protected $message = '';

	protected $message_class = 'message';

	protected $input_values = [];

	protected $with_upload;

	protected $submit_text = 'Submit';

	protected $submit_class = 'button';

	protected $submit_disabled;

	protected $action;

	protected $method;

	protected $thumbnail_width = 100;

	protected $thumbnail_height = 100;

	public $html = '';

	protected static $defaults = [
		'id'          => '',
		'class'       => '',
		'name'        => '',
		'value'       => '',
		'label'       => '',
		'options'     => [], //value of select/radio. Example: [label=>value]
		'checked'     => '', // checked value of select/radio; bool of checkbox
		'selected'    => '', // selected value if select
		'required'    => false,
		'disabled'    => false,
		'autofocus'   => false,
		'readonly'    => false,
		'placeholder' => '',
		'size'        => '',
		'maxlength'   => '',
		'min'         => '',
		'max'         => '',
		'step'        => '',
		'pattern'     => '',
		'multiple'    => '',

		// icon and addon
		'icon_left'   => '',
		'icon_right'  => '',
		'addon_left'  => '',
		'addon_right' => '',
	];

	/**
	 *@since 2020.04.17
	 *input字段类型
	 */
	protected static $input_types = [
		'text',
		'number',
		'email',
		'password',
		'url',
		'color',
		'date',
		'range',
		'tel',
	];

	// 初始化构建
	public function __construct() {
		$this->id = 'wnd-' . uniqid();
		$this->add_form_attr('id', $this->id);
	}

	/**
	 *@since 2019.03.10 设置表单属性
	 */
	public function set_form_title(string $form_title, bool $is_title_centered = false) {
		$this->form_title        = $form_title;
		$this->is_title_centered = $is_title_centered;
	}

	/**
	 *设置表单提示信息
	 */
	public function set_message(string $message, $class = '') {
		$this->message       = $message;
		$this->message_class = $this->message_class . ' ' . $class;
	}

	/**
	 *设置表单缩略图尺寸
	 *@param int 	$width
	 *@param int 	$height
	 */
	public function set_thumbnail_size(int $width, int $height) {
		$this->thumbnail_width  = $width;
		$this->thumbnail_height = $height;
	}

	// Submit
	public function set_submit_button(string $text, string $class = '', bool $disabled = false) {
		$this->submit_text     = $text;
		$this->submit_class    = $this->submit_class . ' ' . $class;
		$this->submit_disabled = $disabled;
	}

	// action
	public function set_action(string $action, string $method = 'POST') {
		$this->method = $method;
		$this->action = $action;

		$this->add_form_attr('method', $this->method);
		$this->add_form_attr('action', $this->action);
	}

	// 直接设置当前表单的组成数组（通常用于配合 filter 过滤）
	public function set_input_values(array $input_values) {
		$this->input_values = $input_values;
	}

	/**
	 *@since 2019.08.29
	 *设置表单属性
	 **/
	public function add_form_attr(string $key, string $value) {
		$this->form_attr[$key] = $value;
	}

	/**
	 *@since 2019.03.10 设置常规input 字段
	 */
	// text
	public function add_text(array $args) {
		$args                 = array_merge(static::$defaults, $args);
		$args['type']         = 'text';
		$this->input_values[] = $args;
	}

	// number
	public function add_number(array $args) {
		$args                 = array_merge(static::$defaults, $args);
		$args['type']         = 'number';
		$this->input_values[] = $args;
	}

	// hidden
	public function add_hidden(string $name, string $value) {
		$this->input_values[] = [
			'type'  => 'hidden',
			'name'  => $name,
			'value' => $value,
		];
	}

	// textarea
	public function add_textarea(array $args) {
		$args                 = array_merge(static::$defaults, $args);
		$args['type']         = 'textarea';
		$this->input_values[] = $args;
	}

	// email
	public function add_email(array $args) {
		$args                 = array_merge(static::$defaults, $args);
		$args['type']         = 'email';
		$this->input_values[] = $args;
	}

	// password
	public function add_password(array $args) {
		$args                 = array_merge(static::$defaults, $args);
		$args['type']         = 'password';
		$this->input_values[] = $args;
	}

	/**
	 *@since 2019.08.23
	 *新增HTML5 字段
	 */
	// URL
	public function add_url(array $args) {
		$args                 = array_merge(static::$defaults, $args);
		$args['type']         = 'url';
		$this->input_values[] = $args;
	}

	// color
	public function add_color(array $args) {
		$args                 = array_merge(static::$defaults, $args);
		$args['type']         = 'color';
		$this->input_values[] = $args;
	}

	// date
	public function add_date(array $args) {
		$args                 = array_merge(static::$defaults, $args);
		$args['type']         = 'date';
		$this->input_values[] = $args;
	}

	// range
	public function add_range(array $args) {
		$args                 = array_merge(static::$defaults, $args);
		$args['type']         = 'range';
		$this->input_values[] = $args;
	}

	// tel
	public function add_tel(array $args) {
		$args                 = array_merge(static::$defaults, $args);
		$args['type']         = 'tel';
		$this->input_values[] = $args;
	}

	// select
	public function add_select(array $args) {
		$args                 = array_merge(static::$defaults, $args);
		$args['type']         = 'select';
		$this->input_values[] = $args;
	}

	// radio
	public function add_radio(array $args) {
		$args                 = array_merge(static::$defaults, $args);
		$args['type']         = 'radio';
		$this->input_values[] = $args;
	}

	// checkbox
	public function add_checkbox(array $args) {
		$args                 = array_merge(static::$defaults, $args);
		$args['type']         = 'checkbox';
		$this->input_values[] = $args;
	}

	// Image upload
	public function add_image_upload(array $args) {
		$defaults = [
			'id'             => 'image-upload-' . $this->id,
			'thumbnail'      => '',
			'thumbnail_size' => ['width' => $this->thumbnail_width, 'height' => $this->thumbnail_height],
			'file_id'        => 0,
			'data'           => [],
			'delete_button'  => true,
		];
		$args                 = array_merge(static::$defaults, $defaults, $args);
		$args['type']         = 'image_upload';
		$this->input_values[] = $args;

		if (!$this->with_upload) {
			$this->with_upload = true;
		}
	}

	// File upload
	public function add_file_upload(array $args) {
		$defaults = [
			'id'            => 'file-upload-' . $this->id,
			'file_name'     => 'file name',
			'file_id'       => 0,
			'data'          => [],
			'delete_button' => true,
		];
		$args                 = array_merge(static::$defaults, $defaults, $args);
		$args['type']         = 'file_upload';
		$this->input_values[] = $args;

		if (!$this->with_upload) {
			$this->with_upload = true;
		}
	}

	/**
	 *@since 2019.03.06 在表单当前位置插入指定html代码以补充现有方法无法实现的效果
	 */
	public function add_html(string $html) {
		$this->input_values[] = [
			'type'  => 'html',
			'value' => $html,
		];
	}

	/**
	 *@since 2021.01.31
	 *添加一组被 DIV 包裹的字段
	 *
	 * 若使用 PHP 渲染，可直接两次调用 addd_html 即可将一组字段用任意 html 包裹，更为简洁直观
	 * add_group 方法主要用于将一组字段构建成完整的数组数据，以便于转为 json 后供前端 JavaScript 渲染
	 *
	 *$form->add_group(
	 *	[
	 *		'attrs'  => ['class' => 'has-text-centered field'],
	 *		'fields' => [
	 *			[
	 *				'type'     => 'radio',
	 *				'name'     => 'total_amount',
	 *				'options'  => Wnd\Model\Wnd_Recharge::get_recharge_amount_options(),
	 *				'required' => 'required',
	 *				'class'    => 'is-checkradio is-danger',
	 *			],
	 *			[
	 *				'type'     => 'radio',
	 *				'name'     => 'payment_gateway',
	 *				'options'  => Wnd\Model\Wnd_Payment_Getway::get_gateway_options(),
	 *				'required' => 'required',
	 *				'checked'  => Wnd\Model\Wnd_Payment_Getway::get_default_gateway(),
	 *				'class'    => 'is-checkradio is-danger',
	 *			],
	 *		],
	 *	]
	 *);
	 *
	 */
	public function add_group(array $args) {
		$defaults = [
			'type'   => 'group',
			'fields' => [],
			'attrs'  => [
				'class' => 'field',
			],
		];
		$args = array_merge($defaults, $args);

		// 将组内字段参数与默认参数合并，防止出现未定义
		foreach ($args['fields'] as $key => $value) {
			$args['fields'][$key] = array_merge(static::$defaults, $value);
		}
		unset($key, $value);

		$this->input_values[] = $args;
	}

	/**
	 *@since 2019.03.06
	 *表单构造函数
	 **/
	public function build() {
		$this->build_form_header();
		$this->build_input_fields();
		$this->build_submit_button();
		$this->build_form_footer();
	}

	protected function build_form_header() {
		if ($this->with_upload) {
			$this->add_form_attr('enctype', 'multipart/form-data');
		}

		$html = '<form' . $this->build_form_attr() . '>';

		if ($this->form_title) {
			$html .= $this->is_title_centered ? '<div class="field content has-text-centered">' : '<div class="field content">';
			$html .= '<h3>' . $this->form_title . '</h3>';
			$html .= '</div>';
		}

		$this->html .= '<div class="form-message">' . $this->message . '</div>';

		$this->html .= $html;
	}

	protected function build_input_fields(): string{
		$input_fields = '';
		foreach ($this->input_values as $input_key => $input_value) {
			// input 字段
			if (in_array($input_value['type'], static::$input_types)) {
				$input_fields .= $this->build_input($input_value, $input_key);
				continue;
			}

			/**
			 * @since 0.9.0
			 * 其他字段
			 *  - 根据字段类型组合构建字段方法
			 *  - 执行字段构建方法
			 */
			$method = 'build_' . $input_value['type'];
			$input_fields .= $this->$method($input_value, $input_key);
		}
		unset($input_value);

		$this->html .= $input_fields;

		return $input_fields;
	}

	protected function build_select(array $input_value, string $input_key): string{
		$html = '<div class="field">';
		$html .= static::build_label($input_value);
		$html .= '<div class="control">';
		$html .= '<div class="select">';
		$html .= '<select' . static::build_input_id($input_value) . static::build_input_attr($input_value) . '>';
		foreach ($input_value['options'] as $key => $value) {
			if (is_array($input_value['selected'])) {
				$checked = in_array($value, $input_value['selected']) ? ' selected="selected"' : '';
			} else {
				$checked = ($input_value['selected'] == $value) ? ' selected="selected"' : '';
			}

			$html .= '<option value="' . $value . '"' . $checked . '>' . $key . '</option>';
		}unset($key, $value);
		$html .= '</select>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

	protected function build_radio(array $input_value, string $input_key): string{
		$html = '<div' . static::build_input_id($input_value) . ' class="field' . static::get_class($input_value, true) . '">';
		$html .= static::build_label($input_value);
		foreach ($input_value['options'] as $key => $value) {
			$input_id = md5($key . $input_key);
			$html .= '<input id="' . $input_id . '" value="' . $value . '"' . static::build_input_attr($input_value);
			$html .= ($input_value['checked'] == $value) ? ' checked="checked">' : '>';

			$html .= '<label for="' . $input_id . '" class="radio">' . $key . '</label>';
		}unset($key, $value);
		$html .= '</div>';

		return $html;
	}

	protected function build_checkbox(array $input_value, string $input_key): string{
		$html = '<div' . static::build_input_id($input_value) . ' class="field' . static::get_class($input_value, true) . '">';
		$html .= static::build_label($input_value);
		foreach ($input_value['options'] as $key => $value) {
			$input_id = md5($key . $input_key);
			$html .= '<input id="' . $input_id . '" value="' . $value . '"' . static::build_input_attr($input_value);
			if (is_array($input_value['checked'])) {
				$html .= in_array($value, $input_value['checked']) ? ' checked="checked">' : '>';
			} else {
				$html .= ($input_value['checked'] == $value) ? ' checked="checked">' : '>';
			}

			$html .= '<label for="' . $input_id . '" class="checkbox">' . $key . '</label>';
		}unset($key, $value);
		$html .= '</div>';

		return $html;
	}

	protected function build_hidden(array $input_value, string $input_key): string{
		$html = '<input' . static::build_input_id($input_value) . static::build_input_attr($input_value) . '>';
		return $html;
	}

	protected function build_input($input_value, $input_key) {
		$has_addons = ($input_value['addon_left'] or $input_value['addon_right']) ? true : false;

		if ($has_addons) {
			$html = static::build_label($input_value);
			$html .= '<div class="field has-addons">';
		} else {
			$html = '<div class="field">';
			$html .= static::build_label($input_value);
		}

		// class
		$class = '';
		$class .= $has_addons ? ' is-expanded' : '';
		$class .= $input_value['icon_left'] ? ' has-icons-left' : '';
		$class .= $input_value['icon_right'] ? ' has-icons-right' : '';

		// addon left
		if ($input_value['addon_left']) {
			$html .= '<div class="control">' . $input_value['addon_left'] . '</div>';
		}

		// input and icon
		$html .= '<div class="control' . $class . '">';
		$html .= '<input' . static::build_input_id($input_value) . static::build_input_attr($input_value) . '>';
		$html .= $input_value['icon_left'] ? '<span class="icon is-left">' . $input_value['icon_left'] . '</span>' : '';
		$html .= $input_value['icon_right'] ? '<span class="icon is-right">' . $input_value['icon_right'] . '</span>' : '';
		$html .= '</div>';

		// addon right
		if ($input_value['addon_right']) {
			$html .= '<div class="control">' . $input_value['addon_right'] . '</div>';
		}

		$html .= '</div>';
		return $html;
	}

	protected function build_image_upload(array $input_value, string $input_key): string{
		$id                        = $input_value['id'] . '-' . $input_key;
		$input_value['data']['id'] = $id;

		$html = '<div id="' . $id . '" class="field' . static::get_class($input_value, true) . '">';
		$html .= static::build_label($input_value);
		$html .= '<div class="field"><div class="ajax-message"></div></div>';

		$html .= '<div class="field">';
		$html .= '<a><img class="thumbnail" src="' . $input_value['thumbnail'] . '" height="' . $input_value['thumbnail_size']['height'] . '" width="' . $input_value['thumbnail_size']['width'] . '"></a>';
		$html .= $input_value['delete_button'] ? '<a class="delete" data-id="' . $id . '" data-file_id="' . $input_value['file_id'] . '"></a>' : '';
		$html .= '<div class="file">';
		$html .= '<input accept="image/*"' . static::build_input_attr($input_value) . '>';
		$html .= '</div>';
		$html .= '</div>';

		$html .= '
		<script type="text/javascript">
			var fileupload = document.querySelector("#' . $id . ' input[type=\'file\']");
			var image = document.querySelector("#' . $id . ' .thumbnail");
			image.onclick = function () {
			    fileupload.click();
			};
		</script>';

		$html .= '</div>';
		return $html;
	}

	protected function build_file_upload(array $input_value, string $input_key): string{
		$id                        = $input_value['id'] . '-' . $input_key;
		$input_value['data']['id'] = $id;

		$html = '<div id="' . $id . '" class="field' . static::get_class($input_value, true) . '">';
		$html .= '<div class="field"><div class="ajax-message"></div></div>';
		$html .= '<div class="columns is-mobile is-vcentered">';

		$html .= '<div class="column">';
		$html .= '<div class="file has-name is-fullwidth">';
		$html .= '<label class="file-label">';
		$html .= '<input' . static::build_input_attr($input_value) . '>';
		$html .= '<span class="file-cta">';
		$html .= '<span class="file-icon"><i class="fa fa-upload"></i></span>';
		$html .= '<span class="file-label">' . $input_value['label'] . '</span>';
		$html .= '</span>';
		$html .= '<span class="file-name">' . $input_value['file_name'] . '</span>';
		$html .= '</label>';
		$html .= '</div>';
		$html .= '</div>';

		if ($input_value['delete_button']) {
			$html .= '<div class="column is-narrow">';
			$html .= '<a class="delete" data-id="' . $id . '" data-file_id="' . $input_value['file_id'] . '"></a>';
			$html .= '</div>';
		}

		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

	protected function build_html(array $input_value, string $input_key): string {
		return $input_value['value'];
	}

	protected function build_textarea(array $input_value, string $input_key): string{
		$html = '<div class="field">';
		$html .= static::build_label($input_value);
		$html .= '<textarea' . static::build_input_id($input_value) . static::build_input_attr($input_value) . '>' . $input_value['value'] . '</textarea>';
		$html .= '</div>';
		return $html;
	}

	/**
	 *@since 2021.01.31
	 *构建一组字段
	 *
	 * 若使用 PHP 渲染，可直接两次调用 addd_html 即可将一组字段用任意 html 包裹，更为简洁直观
	 * add_group 方法主要用于将一组字段构建成完整的数组数据，以便于转为 json 后供前端 JavaScript 渲染
	 * build_group 以便于同一个表单可同时基于 php 渲染和 JavaScript 渲染
	 *
	 */
	protected function build_group(array $input_value, string $input_key): string{
		$html = '<div ';
		foreach ($input_value['attrs'] as $attr => $attr_value) {
			$html .= $attr . '="' . $attr_value . '" ';
		}
		unset($attr, $attr_value);
		$html .= '>';

		foreach ($input_value['fields'] as $key => $field) {
			$field_method = 'build_' . $field['type'];
			$html .= $this->$field_method($field, $input_key . $key);
		}
		unset($key, $field);

		$html .= '</div>';

		return $html;
	}

	protected function build_submit_button() {
		if (!$this->submit_text) {
			return;
		}
		$this->html .= '<div class="field is-grouped is-grouped-centered">';
		$this->html .= '<button type="submit" data-text="' . $this->submit_text . '" class="' . $this->get_submit_class(true) . '"' . ($this->submit_disabled ? ' disabled="disabled"' : '') . '>' . $this->submit_text . '</button>';
		$this->html .= '</div>';
	}

	/**
	 *@since 0.8.65
	 *闭合表单并渲染 Ajax 表单提交脚本
	 */
	protected function build_form_footer() {
		$this->html .= '</form>' . $this->render_script();
	}

	/**
	 *表单脚本：将在表单结束成后加载
	 *@since 0.8.65
	 */
	protected function render_script(): string {
		return '';
	}

	/**
	 *@since 2019.08.29
	 *构造表单属性
	 */
	protected function build_form_attr(): string{
		$attr = '';
		foreach ($this->form_attr as $key => $value) {
			if (!$value and !is_numeric($value)) {
				continue;
			}

			$attr .= ' ' . $key . '="' . $value . '"';
		}
		unset($key, $value);

		return $attr;
	}

	protected static function build_input_id(array $input_value): string {
		if ($input_value['id'] ?? false) {
			return ' id="' . $input_value['id'] . '"';
		}
		return '';
	}

	/**
	 *@since 2019.07.19
	 *统一封装获取字段attribute
	 *不含：id
	 *不含：下拉selected
	 *不含：单选、复选checked属性
	 *不含：Textarea value属性
	 */
	protected static function build_input_attr(array $input_value): string{
		$bool_attrs   = ['readonly', 'disabled', 'autofocus', 'required', 'multiple'];
		$normal_attrs = ['class', 'value', 'type', 'name', 'placeholder', 'size', 'maxlength', 'min', 'max', 'step', 'pattern'];
		$attr         = '';

		/**
		 *@since 2020.04.17
		 *文件上传及图像上传
		 *
		 *上传字段type值为自定义值，此处需要矫正为合规的 HTML Type：file
		 *适应bulma样式，需要统一添加class：file-input
		 */
		if (in_array($input_value['type'], ['image_upload', 'file_upload'])) {
			$input_value['type']  = 'file';
			$input_value['class'] = 'file-input ' . $input_value['class'];
		}

		foreach ($input_value as $key => $value) {
			// 读取class传参，并根据type设置添加默认class
			if ('class' == $key) {
				if (in_array($input_value['type'], static::$input_types)) {
					$value = 'input ' . $value;
				} else {
					$value = $input_value['type'] . ' ' . $value;
				}

				$attr .= ' ' . $key . '="' . $value . '"';
				continue;
			}

			// Textarea 文本框不设置value属性
			if ('textarea' == $input_value['type'] and 'value' == $key) {
				continue;
			}

			// 移除未设定的属性
			if (!$value and !is_numeric($value)) {
				continue;
			}

			/**
			 *构建Data数据
			 *@since 2020.04.14
			 */
			if ('data' == $key) {
				foreach ($value as $data_key => $data_value) {
					$attr .= ' data-' . $data_key . '="' . $data_value . '" ';
				}unset($data_key, $data_value);
				continue;
			}

			// 设置布尔属性
			if (in_array($key, $bool_attrs)) {
				$attr .= ' ' . $key . '="' . $key . '"';
				continue;
			}

			// 设置常规属性
			if (in_array($key, $normal_attrs)) {
				$attr .= ' ' . $key . '="' . $value . '"';
				continue;
			}
		}
		unset($key, $value);

		return $attr;
	}

	/**
	 *@since 2019.08.25
	 *构建label HTML
	 *
	 *@var string 	$label
	 *@var string 	$required
	 */
	protected static function build_label(array $input_value): string {
		if (empty($input_value['label'])) {
			return '';
		}

		/**
		 * @data 2020.10.12
		 * 同步设置 Class
		 */
		$class = $input_value['class'] ? 'label ' . $input_value['class'] : 'label';
		$label = $input_value['required'] ? $input_value['label'] . '<span class="required">*</span>' : $input_value['label'];

		return '<label class="' . $class . '">' . $label . '</label>';
	}

	/**
	 *辅助函数
	 */
	protected static function get_class(array $input_value, bool $space = false): string {
		if ($input_value['class'] ?? false) {
			return $space ? ' ' . $input_value['class'] : $input_value['class'];
		}
		return '';
	}

	protected function get_submit_class(bool $space = false): string {
		if ($this->submit_class) {
			return $space ? ' ' . $this->submit_class : $this->submit_class;
		}
		return '';
	}

	/**
	 *获取表单字段HTML
	 *@since 2019.04.28
	 */
	public function get_input_fields(): string {
		return $this->build_input_fields();
	}

	// 获取当前表单的组成数据数组（通常用于配合 filter 过滤）
	public function get_input_values(): array{
		return $this->input_values;
	}

	/**
	 *获取表单构造数组数据，可用于前端 JS 渲染
	 */
	public function get_form_structure(): array{
		return [
			'attrs'   => $this->form_attr,
			'title'   => [
				'title' => $this->form_title,
				'attrs' => ['class' => $this->is_title_centered ? 'has-text-centered' : ''],
			],
			'message' => [
				'message' => $this->message,
				'attrs'   => ['class' => $this->message_class],
			],
			'fields'  => $this->get_input_values(),
			'submit'  => [
				'text'  => $this->submit_text,
				'attrs' => [
					'is_disabled' => $this->submit_disabled,
					'class'       => $this->submit_class,
				],
			],
		];
	}
}
