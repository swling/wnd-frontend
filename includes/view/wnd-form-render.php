<?php
namespace Wnd\View;

/**
 * Class for creating dynamic Bulma forms.
 * PHP 表单渲染器
 * @author swling tangfou@gmail.com
 *
 * @link https://wndwp.com
 * @since 0.9.26
 */
class Wnd_Form_Render {

	private $structure = [];
	private $html      = '';

	private $is_horizontal;
	private $id;

	private $before_html;
	private $after_html;

	private $title;
	private $fields;
	private $submit;

	/**
	 * input字段类型
	 * @since 2020.04.17
	 */
	private static $input_types = [
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

	/**
	 * 初始化构建
	 * @param array $is_horizontal 	水平表单
	 */
	public function __construct(array $structure) {
		$defaults = [
			'before_html'   => '',
			'after_html'    => '',
			'attrs'         => [],
			'size'          => '',
			'step_index'    => [],
			'title'         => [
				'title' => '',
				'attrs' => [],
			],
			'message'       => [
				'message' => '',
				'attrs'   => [],
			],
			'fields'        => [],
			'submit'        => [],
			'primary_color' => '',
			'second_color'  => '',
			'script'        => '',
		];

		$this->structure = array_merge($defaults, $structure);

		$this->is_horizontal = $this->structure['attrs']['is-horizontal'] ?? false;
		$this->id            = $this->structure['attrs']['id'];
		$this->title         = $this->structure['title']['title'];
		$this->submit        = $this->structure['submit'];
		$this->before_html   = $this->structure['before_html'];
		$this->after_html    = $this->structure['after_html'];
		$this->fields        = $this->structure['fields'];
	}

	/**
	 * 表单构造函数
	 * @since 2019.03.06
	 */
	public function render(): string {
		$this->build_form_header();
		$this->build_input_fields();
		$this->build_submit_button();
		$this->build_form_footer();

		return $this->html;
	}

	private function build_form_header() {
		$html = '<form' . $this->build_form_attr() . '>';

		$html .= $this->before_html;

		if ($this->title) {
			$html .= '<div class="field">';
			$html .= '<h3 ' . static::build_attrs($this->structure['title']['attrs']) . '>' . $this->title . '</h3>';
			$html .= '</div>';
		}

		$html .= '<div ' . static::build_attrs($this->structure['message']['attrs']) . '>' . $this->structure['message']['message'] . '</div>';

		$this->html .= $html;
	}

	private function build_input_fields(): string {
		$input_fields = '';
		foreach ($this->fields as $input_key => $input_value) {
			// horizontal
			if ($this->is_horizontal and !in_array($input_value['type'], ['html', 'hidden'])) {
				$input_fields .= '
				<div class="field is-horizontal">
				<div class="field-label">' . $this->build_label($input_value, true) . '</div>
				<div class="field-body">';
			}

			/**
			 * 常规字段
			 * 其他字段
			 *  - 根据字段类型组合构建字段方法
			 *  - 执行字段构建方法
			 * @since 0.9.0
			 */
			if (in_array($input_value['type'], static::$input_types)) {
				$input_fields .= $this->build_input($input_value, $input_key);
			} else {
				$method = 'build_' . $input_value['type'];
				if (method_exists($this, $method)) {
					$input_fields .= $this->$method($input_value, $input_key);
				}
			}

			// horizontal
			if ($this->is_horizontal and !in_array($input_value['type'], ['html', 'hidden'])) {
				$input_fields .= '</div></div>';
			}
		}
		unset($input_value);

		$this->html .= $input_fields;
		return $input_fields;
	}

	private function build_select(array $input_value, string $input_key): string {
		$html = '<div class="field">';
		$html .= $this->build_label($input_value);
		$html .= '<div class="control">';
		$html .= '<div class="select' . static::get_class($input_value, true) . '">';
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
		$html .= static::build_help($input_value);
		$html .= '</div>';
		return $html;
	}

	private function build_radio(array $input_value, string $input_key): string {
		$html = '<div' . static::build_input_id($input_value) . ' class="field' . static::get_class($input_value, true) . '">';
		$html .= '<div class="control">';
		$html .= $this->build_label($input_value);
		foreach ($input_value['options'] as $key => $value) {
			$html .= '<label class="radio">';
			$html .= '<input value="' . $value . '"' . static::build_input_attr($input_value);
			$html .= ($input_value['checked'] == $value) ? ' checked="checked">' : '>';
			$html .= $key . '</label>';
		}unset($key, $value);
		$html .= '</div>';
		$html .= static::build_help($input_value);
		$html .= '</div>';

		return $html;
	}

	private function build_checkbox(array $input_value, string $input_key): string {
		$html = '<div' . static::build_input_id($input_value) . ' class="field' . static::get_class($input_value, true) . '">';
		$html .= '<div class="control">';
		$html .= $this->build_label($input_value);
		foreach ($input_value['options'] as $key => $value) {
			$html .= '<label class="checkbox">';
			// 新增 hidden 字段，以保证提交数据中包含本字段名（表单签名需要）
			$html .= '<input type="hidden" value="" name="' . $input_value['name'] . '">';
			$html .= '<input value="' . $value . '"' . static::build_input_attr($input_value);
			if (is_array($input_value['checked'])) {
				$html .= in_array($value, $input_value['checked']) ? ' checked="checked">' : '>';
			} else {
				$html .= ($input_value['checked'] == $value) ? ' checked="checked">' : '>';
			}
			$html .= $key . '</label>';
		}unset($key, $value);
		$html .= '</div>';
		$html .= static::build_help($input_value);
		$html .= '</div>';

		return $html;
	}

	private function build_hidden(array $input_value, string $input_key): string {
		$html = '<input' . static::build_input_id($input_value) . static::build_input_attr($input_value) . '>';
		return $html;
	}

	private function build_input($input_value, $input_key) {
		$has_addons = ($input_value['addon_left'] or $input_value['addon_right']) ? true : false;

		if ($has_addons) {
			$html = '<div class="field">';
			$html .= '<div class="field has-addons">';
			$html .= $this->build_label($input_value);
		} else {
			$html = '<div class="field">';
			$html .= $this->build_label($input_value);
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

		$html .= $has_addons ? '</div>' : '';
		$html .= static::build_help($input_value);
		$html .= '</div>';
		return $html;
	}

	private function build_image_upload(array $input_value, string $input_key): string {
		$id                        = ($input_value['id'] ?: $this->id) . '-' . $input_key;
		$input_value['data']['id'] = $id;

		$html = '<div id="' . $id . '" class="field' . static::get_class($input_value, true) . '">';
		$html .= $this->build_label($input_value);
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

		$html .= static::build_help($input_value);
		$html .= '</div>';
		return $html;
	}

	private function build_file_upload(array $input_value, string $input_key): string {
		$id                        = ($input_value['id'] ?: $this->id) . '-' . $input_key;
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

	private function build_html(array $input_value, string $input_key): string {
		return $input_value['value'];
	}

	private function build_textarea(array $input_value, string $input_key): string {
		$html = '<div class="field">';
		$html .= $this->build_label($input_value);
		$html .= '<textarea' . static::build_input_id($input_value) . static::build_input_attr($input_value) . '>' . $input_value['value'] . '</textarea>';
		$html .= static::build_help($input_value);
		$html .= '</div>';
		return $html;
	}

	private function build_submit_button() {
		if (!$this->submit['text']) {
			return;
		}
		$this->html .= '<div class="field is-grouped is-grouped-centered">';
		$this->html .= '<button type="submit" data-text="' . $this->submit['text'] . '" class="' . $this->submit['attrs']['class'] . '"' . ($this->submit['attrs']['is_disabled'] ? ' disabled="disabled"' : '') . '>' . $this->submit['text'] . '</button>';
		$this->html .= '</div>';
	}

	/**
	 * 闭合表单并渲染 Ajax 表单提交脚本
	 * @since 0.8.65
	 */
	private function build_form_footer() {
		$this->html .= $this->after_html;
		$this->html .= $this->render_script();
		$this->html .= '</form>';
	}

	/**
	 * 表单脚本：将在表单结束成后加载
	 * @since 0.8.65
	 */
	private function render_script(): string {
		return $this->structure['script'] ?? '';
	}

	/**
	 * 构造表单属性
	 * @since 2019.08.29
	 */
	private function build_form_attr(): string {
		return static::build_attrs($this->structure['attrs']);
	}

	/**
	 * 构造 HTML 属性
	 * @since 0.9.26
	 */
	private static function build_attrs(array $attrs): string {
		$attr = '';
		foreach ($attrs as $key => $value) {
			if (!$value and !is_numeric($value)) {
				continue;
			}

			$attr .= ' ' . $key . '="' . $value . '"';
		}
		unset($key, $value);

		return $attr;
	}

	private static function build_input_id(array $input_value): string {
		if ($input_value['id'] ?? false) {
			return ' id="' . $input_value['id'] . '"';
		}
		return '';
	}

	/**
	 * 统一封装获取字段attribute
	 * 不含：id
	 * 不含：下拉selected
	 * 不含：单选、复选checked属性
	 * 不含：Textarea value属性
	 * @since 2019.07.19
	 */
	private static function build_input_attr(array $input_value): string {
		$bool_attrs   = ['readonly', 'disabled', 'autofocus', 'required', 'multiple'];
		$normal_attrs = ['class', 'value', 'type', 'name', 'placeholder', 'size', 'maxlength', 'min', 'max', 'step', 'pattern'];
		$attr         = '';

		/**
		 * 文件上传及图像上传
		 * 上传字段type值为自定义值，此处需要矫正为合规的 HTML Type：file
		 * 适应bulma样式，需要统一添加class：file-input
		 * @since 2020.04.17
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
			 * 构建Data数据
			 * @since 2020.04.14
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
	 * 构建label HTML
	 * @var string 	$label
	 * @var boole  	是否为水平 label
	 * @since 2019.08.25
	 */
	private function build_label(array $input_value, $horizontal_label = false): string {
		if ($this->is_horizontal and !$horizontal_label) {
			return '';
		}

		if (empty($input_value['label'])) {
			return '';
		}

		/**
		 * @data 2020.10.12
		 * 同步设置 Class
		 */
		$class = $input_value['class'] ? 'label ' . $input_value['class'] : 'label';
		$label = $input_value['required'] ? ('<span class="required">*</span>' . $input_value['label']) : $input_value['label'];

		return '<label class="' . $class . '">' . $label . '</label>';
	}

	/**
	 * 构建 Help HTML
	 * @var string 	帮助提示信息
	 * @var string 	$required
	 * @since 2021.02.03
	 */
	private static function build_help(array $input_value): string {
		if (empty($input_value['help'])) {
			return '';
		}

		if ($input_value['help']['class'] ?? false) {
			$class = 'help ' . $input_value['help']['class'];
		} else {
			$class = 'help';
		}

		return '<p class="' . $class . '">' . $input_value['help']['text'] . '</p>';
	}

	/**
	 * 辅助函数
	 */
	private static function get_class(array $input_value, bool $space = false): string {
		if ($input_value['class'] ?? false) {
			return $space ? ' ' . $input_value['class'] : $input_value['class'];
		}
		return '';
	}

	/**
	 * 获取表单字段HTML
	 * @since 2019.04.28
	 */
	public function get_input_fields(): string {
		return $this->build_input_fields();
	}
}
