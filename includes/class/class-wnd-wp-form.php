<?php

/**
 *适配本插件的ajax表单类
 *@since 2019.03.08
 *常规情况下，未选中的checkbox 和radio等字段不会出现在提交的表单数据中
 *在本环境中，为实现字段name nonce校验，未选中的字段也会发送一个空值到后台（通过 hidden字段实现），在相关数据处理上需要注意
 *为保障表单不被前端篡改，会提取所有字段的name值，结合算法生成校验码，后端通过同样的方式提取$_POST数据，并做校验
 *
 *@param bool $is_ajax_submit 是否ajax提交
 */
class Wnd_WP_Form extends Wnd_Form {

	protected $filter     = null;
	protected $form_names = array();
	protected $message    = null;
	protected $is_ajax_submit;

	public static $primary_color;
	public static $second_color;

	public function __construct($is_ajax_submit = true) {

		/**
		 *@since 2019.07.17
		 *添加是否为ajax提交选项
		 */
		$this->is_ajax_submit = $is_ajax_submit;

		// 色调
		self::$primary_color = wnd_get_option('wnd', 'wnd_primary_color');
		self::$second_color  = wnd_get_option('wnd', 'wnd_second_color');

		// 继承基础变量
		parent::__construct();
	}

	public function set_filter($filter) {
		$this->filter = $filter;
	}

	// ajax提交只需要设置 action 但常规表单action包含提交地址和提交方式，在类中，必须保持参数个数一致
	public function set_action($action, $method = 'POST') {
		if ($this->is_ajax_submit) {
			$this->add_hidden('action', $action);
			$this->add_hidden('_ajax_nonce', wnd_create_nonce($action));
		} else {
			parent::set_action($action, $method);
		}
	}

	public function set_message($message) {
		$this->message = $message;
	}

	/**
	 *@since 2019.05.26 表单按钮默认配色
	 */
	public function set_submit_button($submit_text, $submit_class = '') {
		$submit_class = $submit_class ?: 'is-' . self::$primary_color;
		$submit_class .= $this->is_ajax_submit ? ' ajax-submit' : '';
		parent::set_submit_button($submit_text, $submit_class);
	}

	/**
	 *@since 2019.05.10
	 *直接新增表单names数组元素
	 *用于nonce校验，如直接通过html方式新增的表单字段，无法被提取，需要通过这种方式新增name，以通过nonce校验
	 **/
	public function add_input_name($name) {
		$this->form_names[] = $name;
	}

	/**
	 *@since 2019.05.09
	 *未被选中的radio 与checkbox将不会发送到后端，会导致wnd_form_nonce 校验失败，此处通过设置hidden字段修改
	 */
	public function add_radio($args) {
		$this->add_hidden($args['name'], '');
		parent::add_radio($args);
	}

	public function add_checkbox($args) {
		$this->add_hidden($args['name'], '');
		parent::add_checkbox($args);
	}

	/**
	 *短信校验
	 *@param string 	$type 		  	register / reset_password / bind / verify
	 *@param string 	$template 		短信模板
	 *注册时若当前手机已注册，则无法发送验证码
	 *找回密码时若当前手机未注册，则无法发送验证码
	 **/
	public function add_sms_verify($type = 'verify', $template = '') {
		$this->add_html('<div class="field"><label class="label">手机<span class="required">*</span></label>');

		// 当前用户为绑定手机或更换绑定手机
		if (!wnd_get_user_phone(get_current_user_id()) or 'bind' == $type) {
			$this->add_text(
				array(
					'name'        => 'phone',
					'icon_left'   => '<i class="fa fa-phone-square"></i>',
					'required'    => 'required',
					'label'       => '',
					'placeholder' => '手机号码',
				)
			);
		}

		$this->add_text(
			array(
				'name'        => 'auth_code',
				'icon_left'   => '<i class="fas fa-comment-alt"></i>',
				'required'    => 'required',
				'label'       => '',
				'placeholder' => '短信验证码',
				'addon_right' => '<button type="button" class="send-code button is-outlined is-' . self::$primary_color . '" data-type="' . $type . '" data-template="' . $template . '" data-nonce="' . wnd_create_nonce('wnd_ajax_send_code') . '" data-is_email="0">获取验证码</button>',
			)
		);

		$this->add_html('</div>');
	}

	/**
	 *邮箱校验
	 *@param string 	$type 		  	register / reset_password / bind / verify
	 *注册时若当前邮箱已注册，则无法发送验证码
	 *找回密码时若当前邮箱未注册，则无法发送验证码
	 **/
	public function add_email_verify($type = 'verify', $template = '') {
		$this->add_html('<div class="field"><label class="label">邮箱<span class="required">*</span></label>');

		// 当前用户为绑定邮箱或更换绑定邮箱
		if (!wp_get_current_user()->data->user_email or 'bind' == $type) {
			$this->add_email(
				array(
					'name'        => '_user_user_email',
					'icon_left'   => '<i class="fa fa-at"></i>',
					'required'    => 'required',
					'placeholder' => '电子邮箱',
				)
			);
		}

		$this->add_text(
			array(
				'name'        => 'auth_code',
				'icon_left'   => '<i class="fa fa-key"></i>',
				'required'    => 'required',
				'label'       => '',
				'placeholder' => '邮箱验证码',
				'addon_right' => '<button type="button" class="send-code button is-outlined is-' . self::$primary_color . '" data-type="' . $type . '" data-template="' . $template . '" data-nonce="' . wnd_create_nonce('wnd_ajax_send_code') . '" data-is_email="email">获取验证码</button>',
			)
		);

		$this->add_html('</div>');
	}

	// Image upload
	public function add_image_upload($args) {
		$defaults = array(
			'label'          => 'Image upland',
			'name'           => 'wnd_file',
			'file_id'        => 0,
			'thumbnail'      => WND_URL . 'static/images/default.jpg', //默认缩略图
			'thumbnail_size' => array('height' => '100', 'width' => '100'),
			'data'           => array(),
			'delete_button'  => true,
		);
		$args = array_merge($defaults, $args);

		// 合并$data
		$defaults_data = array(
			'post_parent' => 0,
			'user_id'     => get_current_user_id(),
			'meta_key'    => 0,
			'save_width'  => 0, //图片文件存储最大宽度 0 为不限制
			'save_height' => 0, //图片文件存储最大过度 0 为不限制
		);
		$args['data'] = array_merge($defaults_data, $args['data']);

		// 固定data
		$args['data']['is_image']         = '1';
		$args['data']['upload_nonce']     = wnd_create_nonce('wnd_ajax_upload_file');
		$args['data']['delete_nonce']     = wnd_create_nonce('wnd_ajax_delete_file');
		$args['data']['meta_key_nonce']   = wnd_create_nonce($args['data']['meta_key']);
		$args['data']['thumbnail']        = $args['thumbnail'];
		$args['data']['thumbnail_width']  = $args['thumbnail_size']['width'];
		$args['data']['thumbnail_height'] = $args['thumbnail_size']['height'];
		$args['data']['method']           = $this->is_ajax_submit ? 'ajax' : $this->method;

		// 根据user type 查找目标文件
		if (!$args['file_id']) {
			$file_id = $args['data']['post_parent'] ? wnd_get_post_meta($args['data']['post_parent'], $args['data']['meta_key']) : wnd_get_user_meta($args['data']['user_id'], $args['data']['meta_key']);
		} else {
			$file_id = $args['file_id'];
		}
		$file_url = $file_id ? wnd_get_thumbnail_url($file_id, $args['thumbnail_size']['width'], $args['thumbnail_size']['height']) : '';

		// 如果字段存在，但文件已不存在，例如已被后台删除，删除对应meta key
		if ($file_id and !$file_url) {
			if ($args['data']['post_parent']) {
				wnd_delete_post_meta($args['data']['post_parent'], $args['data']['meta_key']);
			} else {
				wnd_delete_user_meta($args['data']['user_id'], $args['data']['meta_key']);
			}
		}

		$args['thumbnail'] = $file_url ?: $args['thumbnail'];
		$args['file_id']   = $file_id ?: 0;

		parent::add_image_upload($args);
	}

	// File upload
	public function add_file_upload($args) {
		$defaults = array(
			'label'         => 'File upload',
			'name'          => 'wnd_file',
			'file_id'       => 0,
			'data'          => array(),
			'delete_button' => true,
		);
		$args = array_merge($defaults, $args);

		$defaults_data = array(
			'post_parent' => 0,
			'user_id'     => get_current_user_id(),
			'meta_key'    => 0,
		);
		$args['data'] = array_merge($defaults_data, $args['data']);

		// 固定data
		$args['data']['upload_nonce']   = wnd_create_nonce('wnd_ajax_upload_file');
		$args['data']['delete_nonce']   = wnd_create_nonce('wnd_ajax_delete_file');
		$args['data']['meta_key_nonce'] = wnd_create_nonce($args['data']['meta_key']);
		$args['data']['method']         = $this->is_ajax_submit ? 'ajax' : $this->method;

		// 根据meta key 查找目标文件
		if (!$args['file_id']) {
			$file_id = $args['data']['post_parent'] ? wnd_get_post_meta($args['data']['post_parent'], $args['data']['meta_key']) : wnd_get_user_meta($args['data']['user_id'], $args['data']['meta_key']);
		} else {
			$file_id = $args['file_id'];
		}
		$file_url = $file_id ? wp_get_attachment_url($file_id) : '';

		// 如果字段存在，但文件已不存在，例如已被后台删除，删除对应meta key
		if ($file_id and !$file_url) {
			if ($args['data']['post_parent']) {
				wnd_delete_post_meta($args['data']['post_parent'], $args['data']['meta_key']);
			} else {
				wnd_delete_user_meta($args['data']['user_id'], $args['data']['meta_key']);
			}
		}

		$args['file_id']   = $file_id ?: 0;
		$args['file_name'] = $file_url ? '<a href="' . $file_url . '" target="_blank">查看文件</a>' : '……';

		parent::add_file_upload($args);
	}

	/**
	 *
	 *相册上传
	 *如果设置了post parent, 则上传的附件id将保留在对应的wnd_post_meta 否则保留为 wnd_user_meta
	 *meta_key: 	gallery
	 */
	public function add_gallery_upload($args) {
		$defaults = array(
			'label'          => 'Gallery',
			'thumbnail_size' => array('width' => '160', 'height' => '120'),
			'data'           => array(),
		);
		$args = array_merge($defaults, $args);

		// 合并$data
		$defaults_data = array(
			'post_parent' => 0,
			'user_id'     => get_current_user_id(),
			'save_width'  => 0, //图片文件存储最大宽度 0 为不限制
			'save_height' => 0, //图片文件存储最大过度 0 为不限制
		);
		$args['data'] = array_merge($defaults_data, $args['data']);

		$this->build_gallery_upload($args);
	}

	// 构造表单，可设置WordPress filter 过滤表单的input_values
	public function build() {

		/**
		 *设置表单过滤filter
		 **/
		if ($this->filter) {
			$this->input_values = apply_filters($this->filter, $this->input_values);
		}

		/**
		 *@since 2019.05.09 设置表单fields校验，需要在$this->input_values filter 后执行
		 **/
		$this->build_form_nonce();

		/**
		 *构建表单
		 */
		parent::build();
	}

	/**
	 *@since 2019.05.09
	 * 根据当前表单所有字段name生成wp nonce 用于防止用户在前端篡改表单结构提交未经允许的数据
	 */
	protected function build_form_nonce() {
		// 提取表单字段names 去重，排序
		$this->form_names[] = '_wnd_form_nonce'; // nonce自身字段也需要包含在内
		foreach ($this->get_input_values() as $input_value) {
			if (!isset($input_value['name'])) {
				continue;
			}

			if (isset($input_value['disabled']) and $input_value['disabled']) {
				continue;
			}

			$this->form_names[] = $input_value['name'];
		}
		unset($input_value);

		$this->form_names = array_unique($this->form_names); //针对如 radio checkbox等，存在多个同名字段，但发送到后的只有一个
		sort($this->form_names);

		// 根据表单字段，和表单验证秘钥生成wp nonce
		$nonce = wnd_create_nonce(md5(implode('', $this->form_names)));

		// 将nonce字段添加到表单
		$this->add_hidden('_wnd_form_nonce', $nonce);
	}

	/**
	 *构建表单头部
	 */
	protected function build_form_header() {

		/**
		 *@since 2019.07.17 ajax表单
		 */
		if ($this->is_ajax_submit) {
			$this->add_form_attr('action', '');
			$this->add_form_attr('method', 'POST');
			$this->add_form_attr('onsubmit', 'return false');
		}
		parent::build_form_header();

		$this->html .= '<div class="ajax-message">' . $this->message . '</div>';
	}

	// 构建相册上传
	protected function build_gallery_upload($args) {
		// 固定data
		$args['data']['meta_key']         = 'gallery';
		$args['data']['upload_nonce']     = wnd_create_nonce('wnd_ajax_upload_file');
		$args['data']['delete_nonce']     = wnd_create_nonce('wnd_ajax_delete_file');
		$args['data']['meta_key_nonce']   = wnd_create_nonce($args['data']['meta_key']);
		$args['data']['thumbnail_width']  = $args['thumbnail_size']['width'];
		$args['data']['thumbnail_height'] = $args['thumbnail_size']['height'];
		$args['data']['method']           = $this->is_ajax_submit ? 'ajax' : $this->method;

		// 定义一些本方法需要重复使用的变量
		$post_parent      = $args['data']['post_parent'];
		$meta_key         = $args['data']['meta_key'];
		$thumbnail_width  = $args['thumbnail_size']['width'];
		$thumbnail_height = $args['thumbnail_size']['height'];

		// 根据user type 查找目标文件
		$images = $post_parent ? wnd_get_post_meta($post_parent, $meta_key) : wnd_get_user_meta($args['data']['user_id'], $meta_key);
		$images = is_array($images) ? $images : array();

		/**
		 *@since 2019.05.06 构建 html
		 */
		$id   = 'gallery-' . $this->id;
		$data = ' data-id="' . $id . '"';
		foreach ($args['data'] as $key => $value) {
			$data .= ' data-' . $key . '="' . $value . '" ';
		}unset($key, $value);

		$html = '<div id="' . $id . '" class="field upload-field">';
		$html .= '<div class="field"><div class="ajax-message"></div></div>';

		// 上传区域
		$html .= '<div class="field">';
		$html .= '<div class="file">';
		$html .= '<label class="file-label">';
		$html .= '<input type="file" multiple="multiple" class="file-input" name="wnd_file[]' . '"' . $data . 'accept="image/*" >';
		$html .= ' <span class="file-cta"><span class="file-icon"><i class="fas fa-upload"></i></span><span class="file-label">选择图片</span></span>';
		$html .= '</label>';
		$html .= '</div>';
		$html .= '</div>';

		// 遍历输出图片集
		$html .= '<div class="gallery columns is-vcentered has-text-centered">';
		if (!$images) {
			$html .= '<div class="column default-message">';
			$html .= '<p>' . $args['label'] . '</p>';
			$html .= '</div>';
		}
		foreach ($images as $key => $attachment_id) {
			$attachment_url = wp_get_attachment_url($attachment_id);
			$thumbnail_url  = wnd_get_thumbnail_url($attachment_url, $thumbnail_width, $thumbnail_height);
			if (!$attachment_url) {
				unset($images[$key]); // 在字段数据中取消已经被删除的图片
				continue;
			}

			$html .= '<div class="attachment-' . $attachment_id . ' column is-narrow">';
			$html .= '<a><img class="thumbnail" src="' . $thumbnail_url . '" data-url="' . $attachment_url . '"height="' . $thumbnail_height . '" width="' . $thumbnail_width . '"></a>';
			$html .= '<a class="delete" data-id="' . $id . '" data-file_id="' . $attachment_id . '"></a>';
			$html .= '</div>';
		}
		unset($key, $attachment_id);
		wnd_update_post_meta($post_parent, $meta_key, $images); // 若字段中存在被删除的图片数据，此处更新
		$html .= '</div>';
		$html .= '</div>';

		$this->add_html($html);
	}
}
