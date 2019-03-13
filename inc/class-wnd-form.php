<?php

/**
 * Class for creating dynamic Bulma forms.
 *@since 2019.03
 *Base on form-builder @link https://github.com/swling/form-builder
 *@link https://wndwp.com
 *contact: tangfou@gmail.com
 *这是一个独立的php表单生成器，可于任何PHP环境中使用
 */

class Wnd_Form {

	protected $form_attr;

	protected $input_values;

	protected $submit;

	protected $submit_style;

	protected $action;

	protected $method;

	protected $size;

	protected $upload;

	public $form_title;

	public $html;

	static protected $defaults = array(
		'name' => '',
		'placeholder' => '',
		'label' => '',
		'checked' => '',
		'value' => '',
		'required' => '',
		'options' => NULL,
		'has_icons' => NULL,
		'icon' => '',
		'addon' => null,
		'autofocus' => '',
		'id' => NULL,

	);

	// 初始化构建
	function __construct() {
		$this->input_values = array();
		$this->form_title = null;
		$this->form_attr = 'id="wnd-form"';
		$this->submit = 'Submit';
	}

	// 允许外部更改私有变量
	function __set($var, $val) {
		$this->$var = $val;
	}

	/**
	 *@since 2019.03.10 设置表单属性
	 */
	function set_form_title($form_title) {
		$this->form_title = $form_title;
	}

	function set_form_attr($form_attr) {
		$this->form_attr = $form_attr;
	}

	// Submit
	function set_submit_button($label, $submit_style = '') {
		$this->submit = $label;
		$this->submit_style = $submit_style;
	}

	// _action
	function set_action($method, $action) {
		$this->method = $method;
		$this->action = $action;
	}

	// _size (e.g. 'is-large', 'is-small')
	function set_size($size) {
		$this->size = $size;
	}

	/**
	 *@since 2019.03.10 设置常规input 字段
	 */
	// _text
	function add_text($args = array()) {

		$args = array_merge(Wnd_form::$defaults, $args);

		array_push($this->input_values, array(
			'type' => 'text',
			'name' => $args['name'],
			'placeholder' => $args['placeholder'],
			'label' => $args['label'],
			'value' => $args['value'],
			'required' => $args['required'],
			'autofocus' => $args['autofocus'],
			'has_icons' => $args['has_icons'],
			'icon' => $args['icon'],
			'addon' => $args['addon'],
			'id' => NULL,
		));
	}

	/**
	 *@since 20190.30.10 设置常规input 字段
	 */
	// _text
	function add_number($args = array()) {

		$args = array_merge(Wnd_form::$defaults, $args);

		array_push($this->input_values, array(
			'type' => 'number',
			'name' => $args['name'],
			'placeholder' => $args['placeholder'],
			'label' => $args['label'],
			'value' => $args['value'],
			'required' => $args['required'],
			'autofocus' => $args['autofocus'],
			'has_icons' => $args['has_icons'],
			'icon' => $args['icon'],
			'addon' => $args['addon'],
			'id' => NULL,
		));
	}	

	// _hidden
	function add_hidden($name, $value) {
		array_push($this->input_values, array(
			'type' => 'hidden',
			'name' => $name,
			'value' => $value,
			'id' => NULL,
		));
	}

	// _textarea
	function add_textarea($args = array()) {

		$args = array_merge(Wnd_form::$defaults, $args);

		array_push($this->input_values, array(
			'type' => 'textarea',
			'name' => $args['name'],
			'placeholder' => $args['placeholder'],
			'label' => $args['label'],
			'value' => $args['value'],
			'required' => $args['required'],
			'id' => NULL,
		));
	}

	function add_email($args = array()) {

		$args = array_merge(Wnd_form::$defaults, $args);

		array_push($this->input_values, array(
			'type' => 'email',
			'name' => $args['name'],
			'placeholder' => $args['placeholder'],
			'label' => $args['label'],
			'value' => $args['value'],
			'required' => $args['required'],
			'autofocus' => $args['autofocus'],
			'has_icons' => $args['has_icons'],
			'icon' => $args['icon'],
			'addon' => $args['addon'],
			'id' => NULL,
		));
	}

	// _password
	function add_password($args = array()) {

		$args = array_merge(Wnd_form::$defaults, $args);

		array_push($this->input_values, array(
			'type' => "password",
			'name' => $args['name'],
			'placeholder' => $args['placeholder'],
			'label' => $args['label'],
			'value' => $args['value'],
			'required' => $args['required'],
			'autofocus' => $args['autofocus'],
			'has_icons' => $args['has_icons'],
			'icon' => $args['icon'],
			'addon' => $args['addon'],
			'id' => NULL,
		));
	}

	// _select
	function add_select($args = array()) {

		$args = array_merge(Wnd_form::$defaults, $args);

		array_push($this->input_values, array(
			'type' => "select",
			'name' => $args['name'],
			'label' => $args['label'],
			'checked' => $args['checked'],
			'required' => $args['required'],
			'options' => $args['options'],
			'id' => NULL,
		));
	}

	// _radio
	function add_radio($args) {

		$args = array_merge(Wnd_form::$defaults, $args);

		array_push($this->input_values, array(
			'type' => "radio",
			'name' => $args['name'],
			'placeholder' => $args['placeholder'],
			'label' => $args['label'],
			'checked' => $args['checked'],
			'value' => $args['value'],
			'required' => $args['required'],
			'options' => NULL,
			'id' => NULL,
		));
	}

	// _checkbox
	function add_checkbox($args = array()) {

		$args = array_merge(Wnd_form::$defaults, $args);

		array_push($this->input_values, array(
			'type' => "checkbox",
			"name" => $args['name'],
			'label' => $args['label'],
			'checked' => $args['checked'],
			'value' => $args['value'],
			'required' => $args['required'],
			'id' => NULL,
		));
	}

	// _switch (custom, see https://wikiki.github.io/form/switch/)
	// The id is related to how the switch should look like. For instance, you can pass 'switchThinColorInfo'
	// in order to display a thin switch with info color. See documentation for details.
	function add_switch($args) {

		$args = array_merge(Wnd_form::$defaults, $args);

		array_push($this->input_values, array(
			'type' => "switch",
			'name' => $args['name'],
			'placeholder' => NULL,
			'label' => $args['label'],
			'checked' => $args['checked'],
			'value' => NULL,
			'required' => NULL,
			'options' => NULL,
			'id' => $args['id'],
			// 'is_public' => NULL,
		));
	}

	// TinyMCE textarea
	function addTinyMCE($name, $placeholder, $label, $isPublic) {
		array_push($this->input_values, array(
			'type' => "tinymce",
			'name' => $args['name'],
			'placeholder' => $args['placeholder'],
			'label' => $args['label'],
			'checked' => NULL,
			'value' => NULL,
			'required' => NULL,
			'options' => NULL,
			'id' => NULL,
			'is_public' => $args['isPublic'],
		));
	}

	// Image upload
	function add_image_upload($args) {

		$defaults = array(
			'name' => 'file',
			'label' => 'Image upland',
			'thumbnail' => '',
			'thumbnail_size' => array('height' => '100', 'width' => '100'),
			'required' => null,
			'file_id' => 0,
			'data' => array(),
			'id' => 'image-upload-field',
		);
		$args = array_merge($defaults, $args);

		array_push($this->input_values, array(
			'type' => "image",
			'name' => $args['name'],
			'label' => $args['label'],
			'thumbnail' => $args['thumbnail'],
			'thumbnail_size' => $args['thumbnail_size'],
			'required' => $args['required'],
			'file_id' => $args['file_id'],
			'data' => $args['data'],
			'id' => $args['id'],
		));
		if (!$this->upload) {
			$this->upload = true;
		}

	}

	// File upload
	function add_file_upload($args) {

		$defaults = array(
			'name' => 'file',
			'label' => 'File upload',
			'file_name' => 'file name',
			'file_id' => 0,
			'data' => array(),
			'required' => null,
			'id' => 'file-upload-field',
		);
		$args = array_merge($defaults, $args);

		array_push($this->input_values, array(
			'type' => "file",
			'name' => $args['name'],
			'label' => $args['label'],
			'file_name' => $args['file_name'],
			'file_id' => $args['file_id'],
			'data' => $args['data'],
			'required' => $args['required'],
			'id' => $args['id'],
		));
		if (!$this->upload) {
			$this->upload = true;
		}

	}

	/**
	 *@since 2019.03.06 在表单当前位置插入指定html代码以补充现有方法无法实现的效果
	 */
	function add_html($html) {
		array_push($this->input_values, array(
			'type' => 'html',
			'value' => $html,
		));
	}

	/**
	 *@since 2019.03.06
	 *表单构造函数
	 **/
	function build() {
		$this->build_form_header();
		$this->build_input_values();
		$this->build_submit_button();
		$this->build_form_footer();
	}

	protected function build_form_header() {
		$html = '<form';
		if (!is_null($this->method)) {
			$html .= ' method="' . $this->method . '"';
		}
		if (!is_null($this->action)) {
			$html .= ' action="' . $this->action . '"';
		}
		if ($this->upload) {
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
		foreach ($this->input_values as $input_value) {
			switch ($input_value['type']) {
			case 'text':
			case 'number':
			case 'email':
			case 'password':
				$html .= $this->build_input($input_value);
				break;
			case 'hidden':
				$html .= $this->build_hidden($input_value);
				break;
			case 'radio':
				$html .= $this->build_radio($input_value);
				break;
			case 'checkbox':
				$html .= $this->build_checkbox($input_value);
				break;
			case 'select':
				$html .= $this->build_select($input_value);
				break;
			case 'image':
				$html .= $this->build_image_upload($input_value);
				break;
			case 'file':
				$html .= $this->build_file_upload($input_value);
				break;
			case 'tinymce':
				$html .= $this->buildTinyMCE($input_value);
				break;
			case 'textarea':
				$html .= $this->build_textarea($input_value);
				break;
			case 'switch':
				$html .= $this->build_switch($input_value);
				break;
			case 'html':
				$html .= $this->build_html($input_value);
				break;
			default:
				break;
			}
		}
		$this->html .= $html;
	}

	protected function build_select($input_value) {
		$html = '<div class="field">';
		if (!empty($input_value['label'])) {
			$html .= '<label class="label">' . $input_value['label'] . '</label>';
		}
		$html .= '<div class="control">';
		$html .= '<div class="select">';
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
		$html .= '<div class="control">';
		foreach ($input_value['value'] as $key => $value) {
			$html .= '<label class="radio">';
			$html .= '<input type="radio" name="' . $input_value['name'] . '" value="' . $value . '"' . $this->get_required($input_value);
			$html .= ($input_value['checked'] == $value) ? ' checked="checked" >' : ' >';
			$html .= ' ' . $key;
			$html .= '</label>';
		}unset($key, $value);
		$html .= '</div>';
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
			$html .= '<input class="input' . $this->get_size() . '" name="' . $input_value['name'] . '" type="' . $input_value['type'] . '" placeholder="' . $input_value['placeholder'] . '"' . $this->get_autofocus($input_value) . ' value="' . $this->get_value($input_value) . '"' . $this->get_required($input_value) . '>';
			$html .= '<span class="icon' . $this->get_size() . ' is-' . $input_value['has_icons'] . '">' . $input_value['icon'] . '</span>';
			$html .= '</div>';

		} else {

			$html .= $input_value['addon'] ? '<div class="control is-expanded">' : '<div class="control">';
			$html .= '<input class="input' . $this->get_size() . '" name="' . $input_value['name'] . '" type="' . $input_value['type'] . '" placeholder="' . $input_value['placeholder']
			. '"' . $this->get_autofocus($input_value) . ' value="' . $this->get_value($input_value) . '"' . $this->get_required($input_value) . '>';
			$html .= '</div>';

		}

		if ($input_value['addon']) {
			$html .= '<div class="control">' . $input_value['addon'] . '</div>';
		}

		$html .= '</div>';
		return $html;
	}

	protected function build_checkbox($input_value) {

		$html = '<label class="checkbox">';
		$html .= '<input type="checkbox" name="' . $input_value['name'] . '" value="' . $input_value['value'] . '"' . $this->get_required($input_value);
		$html .= $input_value['checked'] ? ' checked="checked" >' : ' >';
		$html .= ' ' . $input_value['label'];
		$html .= '</label>&nbsp;&nbsp;';
		return $html;
	}

	protected function build_image_upload($input_value) {

		$data = ' data-id="' . $input_value['id'] . '"';
		foreach ($input_value['data'] as $key => $value) {
			$data .= ' data-' . $key . '="' . $value . '" ';
		}unset($key, $value);

		$html = '<div ' . $this->get_id($input_value) . ' class="field upload-field">';
		if ($input_value['label']) {
			$html .= '<label class="label">' . $input_value['label'] . '</label>';
		}
		$html .= '<div class="field"><div class="ajax-msg"></div></div>';

		$html .= '<div class="field">';
		$html .= '<a><img class="thumbnail" src="' . $input_value['thumbnail'] . '" height="' . $input_value['thumbnail_size']['height'] . '" width="' . $input_value['thumbnail_size']['height'] . '"></a>';
		$html .= '<a class="delete" data-id="' . $input_value['id'] . '" data-file_id="' . $input_value['file_id'] . '"></a>';
		$html .= '<div class="file">';
		$html .= '<input type="file" class="file-input" name="' . $input_value['name'] . '[]' . '"' . $data . 'accept="image/*" >';
		$html .= '</div>';
		$html .= '</div>';

		$html .= '
		<script type="text/javascript">
			var fileupload = document.querySelector("#' . $input_value['id'] . ' input[type=\'file\']");
			var image = document.querySelector("#' . $input_value['id'] . ' .thumbnail");
			image.onclick = function () {
			    fileupload.click();
			};
		</script>';

		$html .= '</div>';
		return $html;
	}

	protected function build_file_upload($input_value) {

		$data = ' data-id="' . $input_value['id'] . '"';
		foreach ($input_value['data'] as $key => $value) {
			$data .= ' data-' . $key . '="' . $value . '" ';
		}unset($key, $value);

		$html = '<div ' . $this->get_id($input_value) . ' class="field upload-field">';

		$html .= '<div class="field"><div class="ajax-msg"></div></div>';
		$html .= '<div class="columns is-mobile">';

		$html .= '<div class="column">';
		$html .= '<div class="file has-name is-fullwidth">';
		$html .= '<label class="file-label">';
		$html .= '<input type="file" class="file-input" name="' . $input_value['name'] . '[]' . '"' . $data . '>';
		$html .= '<span class="file-cta">';
		$html .= '<span class="file-icon"><i class="fa fa-upload"></i></span>';
		$html .= '<span class="file-label">' . $input_value['label'] . '</span>';
		$html .= '</span>';
		$html .= '<span class="file-name">' . $input_value['file_name'] . '</span>';
		$html .= '</label>';
		$html .= '</div>';
		$html .= '</div>';

		$html .= '<div class="column is-narrow">';
		$html .= '<a class="delete" data-id="' . $input_value['id'] . '" data-file_id="' . $input_value['file_id'] . '"></a>';
		$html .= '</div>';
		$html .= '</div>';

		$html .= '</div>';
		return $html;
	}

	protected function build_html($input_value) {
		return $input_value['value'];
	}

	protected function build_switch($input_value) {
		$html = '<div class="field">';
		$checked = $input_value['checked'] ? 'checked="checked"' : '';
		$id = $input_value['id'];
		$html .= '<input id="' . $id . '" type="checkbox" name="' . $input_value['name'] . '" class="switch" ' . $checked . '">';
		$html .= '<label for="' . $id . '">' . $input_value['label'] . '</label>';
		$html .= '</div>';
		return $html;
	}

	protected function build_textarea($input_value) {

		$html = '<div class="field">';
		if (!empty($input_value['label'])) {
			$html .= '<label class="label">' . $input_value['label'] . '</label>';
		}
		$html .= '<textarea class="textarea" name="' . $input_value['name'] . '"' . $this->get_required($input_value) . ' placeholder="' . $input_value['placeholder'] . '" >' . $input_value['value'] . '</textarea>';
		$html .= '</div>';
		return $html;
	}

	protected function build_submit_button() {
		$this->html .= '<div class="field is-grouped is-grouped-centered">';
		$this->html .= '<button type="submit" class="button ' . $this->submit_style . '">' . $this->submit . '</button>';
		$this->html .= '</div>';
	}

	protected function build_form_footer() {
		$this->html .= '</form>';
	}

	protected function get_value($input_value) {
		if (empty($input_value['value'])) {
			return '';
		}
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

	protected function get_size() {
		if ($this->size) {
			return ' ' . $this->size;
		}
		return '';
	}

	protected function get_autofocus($input_value) {
		if ($input_value['autofocus']) {
			return ' autofocus="autofocus"';
		}
		return '';
	}

}
