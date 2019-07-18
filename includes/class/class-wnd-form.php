<?php

/**
 * Class for creating dynamic Bulma forms.
 *@since 2019.03
 *Base on form-builder @link https://github.com/swling/form-builder
 *@link https://wndwp.com
 *@author swling tangfou@gmail.com
 *这是一个独立的php表单生成器，可于任何PHP环境中使用
 */
class Wnd_Form {

	public $id;

	public $form_attr;

	public $form_title = null;

	public $input_values = array();

	public $with_upload;

	public $submit_text = 'Submit';

	public $submit_class;

	public $action;

	public $method;

	public $html;

	static protected $defaults = array(

		'name' => '',
		'value' => '',
		'label' => null,
		'options' => array(), //value of select/radio. Example: array(label=>value)
		'required' => false,
		'placeholder' => '',
		'checked' => null, // checked value od select/radio/checkbox
		'disabled' => false,
		'autofocus' => false,

		'id' => null,
		'class' => null,
		'has_icons' => null, //left or right
		'icon' => null,
		'addon' => null,

	);

	// 初始化构建
	public function __construct() {
		$this->id = uniqid();
	}

	// 允许外部更改私有变量
	public function __set($var, $val) {
		$this->$var = $val;
	}

	/**
	 *@since 2019.03.10 设置表单属性
	 */
	public function set_form_title($form_title) {
		$this->form_title = $form_title;
	}

	public function set_form_attr($form_attr) {
		$this->form_attr = $form_attr;
	}

	// Submit
	public function set_submit_button($submit_text, $submit_class = '') {
		$this->submit_text = $submit_text;
		$this->submit_class = $submit_class;
	}

	// _action
	public function set_action($action, $method) {
		$this->method = $method;
		$this->action = $action;
	}

	// 直接设置当前表单的组成数组（通常用于配合 filter 过滤）
	public function set_input_values($input_values) {
		$this->input_values = $input_values;
	}

	/**
	 *@since 2019.03.10 设置常规input 字段
	 */
	// _text
	public function add_text($args) {

		$args = array_merge(Wnd_Form::$defaults, $args);
		$args['type'] = 'text';
		array_push($this->input_values, $args);
	}

	// _number
	public function add_number($args) {

		$args = array_merge(Wnd_Form::$defaults, $args);
		$args['type'] = 'number';
		array_push($this->input_values, $args);
	}

	// _hidden
	public function add_hidden($name, $value) {
		array_push($this->input_values, array(
			'type' => 'hidden',
			'name' => $name,
			'value' => $value,
			'id' => null,
		));
	}

	// _textarea
	public function add_textarea($args) {

		$args = array_merge(Wnd_Form::$defaults, $args);
		$args['type'] = 'textarea';
		array_push($this->input_values, $args);
	}

	// _email
	public function add_email($args) {

		$args = array_merge(Wnd_Form::$defaults, $args);
		$args['type'] = 'email';
		array_push($this->input_values, $args);

	}

	// _password
	public function add_password($args) {

		$args = array_merge(Wnd_Form::$defaults, $args);

		$args['type'] = 'password';
		array_push($this->input_values, $args);
	}

	// _select
	public function add_select($args) {

		$args = array_merge(Wnd_Form::$defaults, $args);
		$args['type'] = 'select';
		array_push($this->input_values, $args);
	}

	// _radio
	public function add_radio($args) {

		$args = array_merge(Wnd_Form::$defaults, $args);
		$args['type'] = 'radio';
		array_push($this->input_values, $args);

	}

	// _checkbox
	public function add_checkbox($args) {

		$args = array_merge(Wnd_Form::$defaults, $args);
		$args['type'] = 'checkbox';
		array_push($this->input_values, $args);

	}

	// Image upload
	public function add_image_upload($args) {

		$defaults = array(
			'id' => 'image-upload-' . $this->id,
			'name' => 'file',
			'label' => 'Image upland',
			'thumbnail' => '',
			'thumbnail_size' => array('height' => '100', 'width' => '100'),
			'required' => null,
			'file_id' => 0,
			'data' => array(),
			'delete_button' => true,
			'disabled' => false,
		);
		$args = array_merge($defaults, $args);

		array_push($this->input_values, array(
			'id' => $args['id'],
			'type' => 'image_upload',
			'name' => $args['name'],
			'label' => $args['label'],
			'thumbnail' => $args['thumbnail'],
			'thumbnail_size' => $args['thumbnail_size'],
			'required' => $args['required'],
			'file_id' => $args['file_id'],
			'data' => $args['data'],
			'delete_button' => $args['delete_button'],
			'disabled' => $args['disabled'],
		));
		if (!$this->with_upload) {
			$this->with_upload = true;
		}

	}

	// File upload
	public function add_file_upload($args) {

		$defaults = array(
			'id' => 'file-upload-' . $this->id,
			'name' => 'file',
			'label' => 'File upload',
			'file_name' => 'file name',
			'file_id' => 0,
			'data' => array(),
			'required' => null,
			'delete_button' => true,
			'disabled' => false,
		);
		$args = array_merge($defaults, $args);

		array_push($this->input_values, array(
			'id' => $args['id'],
			'type' => 'file_upload',
			'name' => $args['name'],
			'label' => $args['label'],
			'file_name' => $args['file_name'],
			'file_id' => $args['file_id'],
			'data' => $args['data'],
			'required' => $args['required'],
			'delete_button' => $args['delete_button'],
			'disabled' => $args['disabled'],
		));
		if (!$this->with_upload) {
			$this->with_upload = true;
		}

	}

	/**
	 *@since 2019.03.06 在表单当前位置插入指定html代码以补充现有方法无法实现的效果
	 */
	public function add_html($html) {
		array_push($this->input_values, array(
			'type' => 'html',
			'value' => $html,
		));
	}

	/**
	 *@since 2019.03.06
	 *表单构造函数
	 **/
	public function build() {
		$this->build_form_header();
		$this->build_input_values();
		$this->build_submit_button();
		$this->build_form_footer();
	}

	protected function build_form_header() {
		$html = '<form';

		$html .= ' id="form-' . $this->id . '"';

		if (!is_null($this->method)) {
			$html .= ' method="' . $this->method . '"';
		}
		if (!is_null($this->action)) {
			$html .= ' action="' . $this->action . '"';
		}
		if ($this->with_upload) {
			$html .= ' enctype="multipart/form-data"';
		}

		if ($this->form_attr) {
			$html .= ' ' . $this->form_attr;
		}
		$html .= '>';

		if ($this->form_title) {
			$html .= '<div class="field content">';
			$html .= '<h3>' . $this->form_title . '</h3>';
			$html .= '</div>';
		}

		$this->html = $html;
	}

	protected function build_input_values() {
		$html = '';
		foreach ($this->input_values as $input_key => $input_value) {
			switch ($input_value['type']) {
			case 'text':
			case 'number':
			case 'email':
			case 'password':
				$html .= $this->build_input($input_value, $input_key);
				break;
			case 'hidden':
				$html .= $this->build_hidden($input_value, $input_key);
				break;
			case 'radio':
				$html .= $this->build_radio($input_value, $input_key);
				break;
			case 'checkbox':
				$html .= $this->build_checkbox($input_value, $input_key);
				break;
			case 'select':
				$html .= $this->build_select($input_value, $input_key);
				break;
			case 'image_upload':
				$html .= $this->build_image_upload($input_value, $input_key);
				break;
			case 'file_upload':
				$html .= $this->build_file_upload($input_value, $input_key);
				break;
			case 'textarea':
				$html .= $this->build_textarea($input_value, $input_key);
				break;
			case 'html':
				$html .= $this->build_html($input_value, $input_key);
				break;
			default:
				break;
			}
		}
		unset($input_value);

		$this->html .= $html;

		return $html;
	}

	protected function build_select($input_value) {
		$html = '<div class="field">';
		if (!empty($input_value['label'])) {
			$html .= '<label class="label">' . $input_value['label'] . '</label>';
		}
		$html .= '<div class="control">';
		$html .= '<div class="select"' . $this->get_class($input_value) . '>';
		$html .= '<select name="' . $input_value['name'] . '"' . $this->get_required($input_value) . ' >';
		foreach ($input_value['options'] as $key => $value) {
			$checked = ($input_value['checked'] == $value) ? ' selected="selected"' : '';
			$html .= '<option value="' . $value . '"' . $checked . '>' . $key . '</option>';
		}
		$html .= '</select>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

	protected function build_radio($input_value) {

		$html = '<div class="field">';
		foreach ($input_value['options'] as $key => $value) {
			$input_id = md5($key);
			$html .= '<input type="radio" id="' . $input_id . '" class="' . $this->get_class($input_value) . '" name="' . $input_value['name'] . '" value="' . $value . '"' . $this->get_required($input_value);
			$html .= ($input_value['checked'] == $value) ? ' checked="checked" >' : ' >';

			$html .= '<label for="' . $input_id . '" class="radio">' . $key . '</label>';
		}unset($key, $value);
		$html .= '</div>';

		return $html;

	}

	protected function build_hidden($input_value) {

		$html = '<input name="' . $input_value['name'] . '" type="hidden" value="' . $this->get_value($input_value) . '" >';

		return $html;
	}

	protected function build_input($input_value) {
		$html = $input_value['addon'] ? '<div class="field has-addons">' : '<div class="field">';
		if (!empty($input_value['label'])) {
			$html .= '<label class="label">' . $input_value['label'] . '</label>';
		}

		// input icon
		if ($input_value['has_icons']) {

			$html .= $input_value['addon'] ? '<div class="control is-expanded has-icons-' . $input_value['has_icons'] . '">' : '<div class="control has-icons-' . $input_value['has_icons'] . '">';
			$html .= '<input' . $this->get_id($input_value) . ' class="input' . $this->get_class($input_value) . '" name="' . $input_value['name'] . '" type="' . $input_value['type'] . '" placeholder="' . $input_value['placeholder'] . '"' . $this->get_autofocus($input_value) . ' value="' . $this->get_value($input_value) . '"' . $this->get_required($input_value) . $this->get_disabled($input_value) . '>';
			$html .= '<span class="icon is-' . $input_value['has_icons'] . '">' . $input_value['icon'] . '</span>';
			$html .= '</div>';

		} else {

			$html .= $input_value['addon'] ? '<div class="control is-expanded">' : '<div class="control">';
			$html .= '<input' . $this->get_id($input_value) . ' class="input' . $this->get_class($input_value) . '" name="' . $input_value['name'] . '" type="' . $input_value['type'] . '" placeholder="' . $input_value['placeholder'] . '"' . $this->get_autofocus($input_value) . ' value="' . $this->get_value($input_value) . '"' . $this->get_required($input_value) . $this->get_disabled($input_value) . '>';
			$html .= '</div>';

		}

		if ($input_value['addon']) {
			$html .= '<div class="control">' . $input_value['addon'] . '</div>';
		}

		$html .= '</div>';
		return $html;
	}

	protected function build_checkbox($input_value) {

		$html = '<div class="field">';
		$html .= '<input type="checkbox" id="' . $input_value['name'] . '" class="' . $this->get_class($input_value) . '" name="' . $input_value['name'] . '" value="' . $input_value['value'] . '"' . $this->get_required($input_value);
		$html .= $input_value['checked'] ? ' checked="checked" >' : ' >';
		$html .= '<label  for="' . $input_value['name'] . '" class="checkbox">' . $input_value['label'] . '</label>';
		$html .= '</div>';
		return $html;
	}

	protected function build_image_upload($input_value, $input_key) {

		$id = $input_value['id'] . '-' . $input_key;

		$data = ' data-id="' . $id . '"';
		foreach ($input_value['data'] as $key => $value) {
			$data .= ' data-' . $key . '="' . $value . '" ';
		}unset($key, $value);

		$html = '<div id="' . $id . '" class="field upload-field">';
		if ($input_value['label']) {
			$html .= '<label class="label">' . $input_value['label'] . '</label>';
		}
		$html .= '<div class="field"><div class="ajax-message"></div></div>';

		$html .= '<div class="field">';
		$html .= '<a><img class="thumbnail" src="' . $input_value['thumbnail'] . '" height="' . $input_value['thumbnail_size']['height'] . '" width="' . $input_value['thumbnail_size']['height'] . '"></a>';
		$html .= $input_value['delete_button'] ? '<a class="delete" data-id="' . $id . '" data-file_id="' . $input_value['file_id'] . '"></a>' : '';
		$html .= '<div class="file">';
		$html .= '<input type="file" class="file-input" name="' . $input_value['name'] . '[]' . '"' . $data . 'accept="image/*"' . $this->get_required($input_value) . $this->get_disabled($input_value) . '>';
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

	protected function build_file_upload($input_value, $input_key) {

		$id = $input_value['id'] . '-' . $input_key;

		$data = ' data-id="' . $id . '"';
		foreach ($input_value['data'] as $key => $value) {
			$data .= ' data-' . $key . '="' . $value . '" ';
		}unset($key, $value);

		$html = '<div id="' . $id . '" class="field upload-field">';

		$html .= '<div class="field"><div class="ajax-message"></div></div>';
		$html .= '<div class="columns is-mobile is-vcentered">';

		$html .= '<div class="column">';
		$html .= '<div class="file has-name is-fullwidth">';
		$html .= '<label class="file-label">';
		$html .= '<input type="file" class="file-input" name="' . $input_value['name'] . '[]' . '"' . $data . $this->get_required($input_value) . $this->get_disabled($input_value) . '>';
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

	protected function build_html($input_value) {
		return $input_value['value'];
	}

	protected function build_textarea($input_value) {

		$html = '<div class="field">';
		if (!empty($input_value['label'])) {
			$html .= '<label class="label">' . $input_value['label'] . '</label>';
		}
		$html .= '<textarea' . $this->get_id($input_value) . ' class="textarea' . $this->get_class($input_value) . '" name="' . $input_value['name'] . '"' . $this->get_required($input_value) . $this->get_disabled($input_value) . ' placeholder="' . $input_value['placeholder'] . '" >' . $input_value['value'] . '</textarea>';
		$html .= '</div>';
		return $html;
	}

	protected function build_submit_button() {
		if (!$this->submit_text) {
			return;
		}
		$this->html .= '<div class="field is-grouped is-grouped-centered">';
		$this->html .= '<button type="submit" data-text="' . $this->submit_text . '" class="button' . $this->get_submit_class() . '">' . $this->submit_text . '</button>';
		$this->html .= '</div>';
	}

	protected function build_form_footer() {
		$this->html .= '</form>';
	}

	/**
	 *辅助函数
	 */
	protected function get_value($input_value) {
		return $input_value['value'];
	}

	protected function get_required($input_value) {
		if ($input_value['required']) {
			return ' required="required"';
		}
		return '';
	}

	protected function get_id($input_value) {
		if ($input_value['id']) {
			return ' id="' . $input_value['id'] . '"';
		}
		return '';
	}

	protected function get_autofocus($input_value) {
		if ($input_value['autofocus']) {
			return ' autofocus="autofocus"';
		}
		return '';
	}

	protected function get_class($input_value) {
		if ($input_value['class']) {
			return ' ' . $input_value['class'];
		}
		return '';
	}

	protected function get_disabled($input_value) {
		if ($input_value['disabled']) {
			return ' disabled="disabled"';
		}
		return '';
	}

	protected function get_submit_class() {
		if ($this->submit_class) {
			return ' ' . $this->submit_class;
		}
		return '';
	}

	/**
	 *表单数据获取与设置
	 *@since 2019.04.28
	 */
	public function get_input_fields() {
		return $this->build_input_values();
	}

	// 获取当前表单的组成数据数组（通常用于配合 filter 过滤）
	public function get_input_values() {
		return $this->input_values;
	}

}
