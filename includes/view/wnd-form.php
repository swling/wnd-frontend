<?php
namespace Wnd\View;

use Exception;

/**
 *表单结构生成器
 * - 以 PHP 数组形式保存表单结构
 * - 可转为 json 供前端渲染 @see [JS Function] _wnd_render_form
 * - PHP 渲染 @see Wnd\View\Wnd_Form_Render
 *
 *@since 2019.03
 *@link https://wndwp.com
 *@author swling tangfou@gmail.com
 */
class Wnd_Form {

	protected $before_html = '';

	protected $after_html = '';

	protected $id;

	protected $size = 'is-normal';

	protected $form_attr = [];

	// 记录分布标识符字段 index
	protected $step_index = [];

	protected $form_title;

	protected $is_title_centered = false;

	protected $message = '';

	protected $message_class = 'form-message';

	protected $input_values = [];

	protected $submit = [
		'text'  => '',
		'attrs' => [
			'is_disabled' => false,
			'class'       => 'button',
		],
	];

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
		'help'        => ['text' => '', 'class' => ''],
	];

	/**
	 *初始化构建
	 *@param bool $is_horizontal 	水平表单
	 */
	public function __construct($is_horizontal = false) {
		$this->id = 'wnd-' . uniqid();
		$this->add_form_attr('id', $this->id);
		$this->add_form_attr('is-horizontal', $is_horizontal);
	}

	/**
	 *表单字段之前 Html
	 */
	public function add_before_html($html) {
		$this->before_html .= $html;
	}

	/**
	 *表单字段之后 Html
	 */
	public function add_after_html($html) {
		$this->after_html .= $html;
	}

	/**
	 *@since 2021.03.03
	 *设置表单 size
	 */
	public function set_form_size(string $size) {
		$this->size = $size;
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
		$this->submit['text']                 = $text;
		$this->submit['attrs']['is_disabled'] = $disabled;
		$this->submit['attrs']['class'] .= ' ' . $class;

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
	 *@since 2021.02.18
	 *添加任意自定义字段，主要用于自定义非标准字段
	 */
	public function add_field(array $args) {
		$type = $args['type'] ?? '';
		if (!$type) {
			throw new Exception('Invalid type');
		}

		$args                 = array_merge(static::$defaults, $args);
		$this->input_values[] = $args;
	}

	/**
	 *@since 2021.03.08
	 *表单分布切割标识
	 */
	public function add_step($text = '') {
		$args['type']         = 'step';
		$args['index']        = count($this->input_values);
		$args['text']         = $text;
		$this->step_index[]   = $args['index'];
		$this->input_values[] = $args;
	}

	/**
	 *@since 2019.03.10 设置常规input 字段
	 */
	public function add_text(array $args) {
		$args['type'] = 'text';
		$this->add_field($args);
	}

	// number
	public function add_number(array $args) {
		$args['type'] = 'number';
		$this->add_field($args);
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
		$args['type'] = 'textarea';
		$this->add_field($args);
	}

	// email
	public function add_email(array $args) {
		$args['type'] = 'email';
		$this->add_field($args);
	}

	// password
	public function add_password(array $args) {
		$args['type'] = 'password';
		$this->add_field($args);
	}

	/**
	 *@since 2019.08.23
	 *新增HTML5 字段
	 */
	// URL
	public function add_url(array $args) {
		$args['type'] = 'url';
		$this->add_field($args);
	}

	// color
	public function add_color(array $args) {
		$args['type'] = 'color';
		$this->add_field($args);
	}

	// date
	public function add_date(array $args) {
		$args['type'] = 'date';
		$this->add_field($args);
	}

	// range
	public function add_range(array $args) {
		$args['type'] = 'range';
		$this->add_field($args);
	}

	// tel
	public function add_tel(array $args) {
		$args['type'] = 'tel';
		$this->add_field($args);
	}

	// select
	public function add_select(array $args) {
		$args['type'] = 'select';
		$this->add_field($args);
	}

	// radio
	public function add_radio(array $args) {
		$args['type'] = 'radio';
		$this->add_field($args);
	}

	// checkbox
	public function add_checkbox(array $args) {
		$args['type'] = 'checkbox';
		$this->add_field($args);
	}

	// Image upload
	public function add_image_upload(array $args) {
		$defaults = [
			'thumbnail'      => '',
			'thumbnail_size' => ['width' => $this->thumbnail_width, 'height' => $this->thumbnail_height],
			'file_id'        => 0,
			'data'           => [],
			'delete_button'  => true,
		];
		$args                 = array_merge(static::$defaults, $defaults, $args);
		$args['type']         = 'image_upload';
		$this->input_values[] = $args;

		$this->add_form_attr('enctype', 'multipart/form-data');
	}

	// File upload
	public function add_file_upload(array $args) {
		$defaults = [
			'file_name'     => 'file name',
			'file_id'       => 0,
			'data'          => [],
			'delete_button' => true,
		];
		$args                 = array_merge(static::$defaults, $defaults, $args);
		$args['type']         = 'file_upload';
		$this->input_values[] = $args;

		$this->add_form_attr('enctype', 'multipart/form-data');
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
	 *@since 2019.03.06
	 *表单构造函数
	 **/
	public function build(): string{
		$structure = $this->get_structure();
		$render    = new Wnd_Form_Render($structure);

		$this->html = $render->render();
		return $this->html;
	}

	// 获取当前表单的组成数据数组（通常用于配合 filter 过滤）
	public function get_input_values(): array{
		return $this->input_values;
	}

	/**
	 *获取表单构造数组数据，可用于前端 JS 渲染
	 */
	public function get_structure(): array{
		return [
			'before_html' => $this->before_html,
			'after_html'  => $this->after_html,
			'attrs'       => $this->form_attr,
			'size'        => $this->size,
			'step_index'  => $this->step_index,
			'title'       => [
				'title' => $this->form_title,
				'attrs' => ['class' => $this->is_title_centered ? 'has-text-centered' : ''],
			],
			'message'     => [
				'message' => $this->message,
				'attrs'   => ['class' => $this->message_class],
			],
			'fields'      => $this->get_input_values(),
			'submit'      => $this->submit,
		];
	}
}
