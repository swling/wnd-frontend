<?php

/**
 *适配本插件的ajax表单类
 *@since 2019.03.08
 */
class Wnd_Ajax_Form extends Wnd_Form {

	// 新增WordPressfilter过滤
	public $filter;

	function set_filter($filter) {
		$this->filter = $filter;
	}

	// 构造表单，可设置WordPress filter 过滤表单的input_values
	function build() {

		if ($this->filter) {
			$this->input_values = apply_filters($this->filter, $this->input_values);
		}
		parent::build();

	}

	protected function build_form_header() {
		$html = '<form id="form-' . $this->id . '" action="" method="POST" data-submit-type="ajax" onsubmit="return false"';

		if ($this->upload) {
			$html .= ' enctype="multipart/form-data"';
		}

		if ($this->form_attr) {
			$html .= ' ' . $this->form_attr;
		}

		$html .= '>';

		if ($this->form_title) {
			$html .= '<div class="field has-text-centered content">';
			$html .= '<h3>' . $this->form_title . '</h3>';
			$html .= '</div>';
		}

		$html .= '<div class="ajax-msg"></div>';

		$this->html = $html;
	}

	// ajax提交只需要设置 action 但常规表单action包含提交地址和提交方式，在类中，必须保持参数个数一致
	function set_action($action, $method = '') {
		parent::add_hidden('action', $action);
		parent::add_hidden('_ajax_nonce', wp_create_nonce($action));
	}

	// 短信验证
	function add_sms_verify($verify_type = 'verify', $template = '') {

		parent::add_html('<div class="field"><label class="label">手机<span class="required">*</span></label>');

		if (!wnd_get_user_phone(get_current_user_id())) {

			parent::add_text(
				array(
					'name' => 'phone',
					'has_icons' => 'left',
					'icon' => '<i class="fa fa-phone-square"></i>',
					'required' => 'required',
					'label' => '',
					'placeholder' => '手机号码',
				)
			);
		}

		parent::add_text(
			array(
				'name' => 'v_code',
				'has_icons' => 'left',
				'icon' => '<i class="fas fa-comment-alt"></i>',
				'required' => 'required',
				'label' => '',
				'placeholder' => '短信验证码',
				'addon' => '<button type="button" class="send-code button is-primary" data-verify-type="' . $verify_type . '" data-template="' . $template . '" data-nonce="' . wp_create_nonce('wnd_ajax_send_code') . '" data-send-type="sms">获取验证码</button>',
			)
		);

		parent::add_html('</div>');

	}

	// 邮箱验证
	function add_email_verify($verify_type = 'verify', $template = '') {

		parent::add_html('<div class="field"><label class="label">邮箱<span class="required">*</span></label>');

		parent::add_text(
			array(
				'name' => '_user_user_email',
				'has_icons' => 'left',
				'icon' => '<i class="fa fa-at"></i>',
				'required' => 'required',
				'placeholder' => '电子邮箱',
			)
		);

		parent::add_text(
			array(
				'name' => 'v_code',
				'has_icons' => 'left',
				'icon' => '<i class="fa fa-key"></i>',
				'required' => 'required',
				'label' => '',
				'placeholder' => '邮箱验证码',
				'addon' => '<button type="button" class="send-code button is-primary" data-verify-type="' . $verify_type . '" data-template="' . $template . '" data-nonce="' . wp_create_nonce('wnd_ajax_send_code') . '" data-send-type="email">获取验证码</button>',
			)
		);

		parent::add_html('</div>');

	}

	// Image upload 后台wnd_file_upload已匹配规则，此处强制input name: file
	function add_image_upload($args) {

		$defaults = array(
			'label' => 'Image upland',
			'thumbnail' => '', //默认缩略图
			'thumbnail_size' => array('height' => '100', 'width' => '100'),
			'data' => array(),
			'delete_button' => true,
		);
		$args = array_merge($defaults, $args);

		// 合并$data
		$defaults_data = array(
			'post_parent' => 0,
			'meta_key' => 0,
			'save_width' => 0, //图片文件存储最大宽度 0 为不限制
			'save_height' => 0, //图片文件存储最大过度 0 为不限制
		);
		$args['data'] = array_merge($defaults_data, $args['data']);

		// 固定data
		$args['data']['is_image'] = '1';
		$args['data']['upload_nonce'] = wp_create_nonce('wnd_ajax_upload_file');
		$args['data']['delete_nonce'] = wp_create_nonce('wnd_ajax_delete_file');
		$args['data']['user_id'] = get_current_user_id();
		$args['data']['thumbnail'] = $args['thumbnail'];

		// 根据user type 查找目标文件
		$file_id = $args['data']['post_parent'] ? wnd_get_post_meta($args['data']['post_parent'], $args['data']['meta_key']) : wnd_get_user_meta($args['data']['user_id'], $args['data']['meta_key']);
		$file_url = $file_id ? wp_get_attachment_url($file_id) : '';

		// 如果字段存在，但文件已不存在，例如已被后台删除，删除对应meta key
		if ($file_id and !$file_url) {
			if ($args['data']['post_parent']) {
				wnd_delete_post_meta($args['data']['post_parent'], $args['data']['meta_key']);
			} else {
				wnd_delete_user_meta($args['data']['user_id'], $args['data']['meta_key']);
			}
		}

		$args['name'] = 'file';
		$args['thumbnail'] = $file_url ?: $args['thumbnail'];
		$args['file_id'] = $file_id ?: 0;

		parent::add_image_upload($args);

	}

	// File upload 后台wnd_file_upload已匹配规则，此处强制input name: file
	function add_file_upload($args) {

		$defaults = array(
			'label' => 'File upload',
			'data' => array(),
			'delete_button' => true,
		);
		$args = array_merge($defaults, $args);

		$defaults_data = array(
			'post_parent' => 0,
			'meta_key' => 0,
		);
		$args['data'] = array_merge($defaults_data, $args['data']);

		// 固定data
		$args['data']['upload_nonce'] = wp_create_nonce('wnd_ajax_upload_file');
		$args['data']['delete_nonce'] = wp_create_nonce('wnd_ajax_delete_file');
		$args['data']['user_id'] = get_current_user_id();

		// 根据meta key 查找目标文件
		$file_id = $args['data']['post_parent'] ? wnd_get_post_meta($args['data']['post_parent'], $args['data']['meta_key']) : wnd_get_user_meta($args['data']['user_id'], $args['data']['meta_key']);
		$file_url = $file_id ? wp_get_attachment_url($file_id) : '';

		// 如果字段存在，但文件已不存在，例如已被后台删除，删除对应meta key
		if ($file_id and !$file_url) {
			if ($args['data']['post_parent']) {
				wnd_delete_post_meta($args['data']['post_parent'], $args['data']['meta_key']);
			} else {
				wnd_delete_user_meta($args['data']['user_id'], $args['data']['meta_key']);
			}
		}

		$args['name'] = 'file';
		$args['file_id'] = $file_id ?: 0;
		$args['file_name'] = $file_url ? '<a href="' . $file_url . '" target="_blank">查看文件</a>' : '……';

		parent::add_file_upload($args);

	}

	// 相册上传
	function add_gallery_upload($args) {

		$defaults = array(
			'label' => 'Gallery',
			'thumbnail' => '', //默认缩略图
			'thumbnail_size' => array('height' => '160', 'width' => '120'),
			'data' => array(),
		);
		$args = array_merge($defaults, $args);

		// 固定args
		$args['name'] = 'file';

		// 合并$data
		$defaults_data = array(
			'post_parent' => 0,
			'save_width' => 0, //图片文件存储最大宽度 0 为不限制
			'save_height' => 0, //图片文件存储最大过度 0 为不限制
		);
		$args['data'] = array_merge($defaults_data, $args['data']);

		// 固定data
		$args['data']['is_image'] = '1';
		$args['data']['meta_key'] = 'gallery';
		$args['data']['upload_nonce'] = wp_create_nonce('wnd_ajax_upload_file');
		$args['data']['delete_nonce'] = wp_create_nonce('wnd_ajax_delete_file');
		$args['data']['user_id'] = get_current_user_id();
		$args['data']['thumbnail'] = $args['thumbnail'];

		// 定义一些本方法需要重复使用的变量
		$post_parent = $args['data']['post_parent'];
		$meta_key = $args['data']['meta_key'];
		$thumbnail_width = $args['thumbnail_size']['width'];
		$thumbnail_height = $args['thumbnail_size']['height'];

		// 根据user type 查找目标文件
		$images = $post_parent ? wnd_get_post_meta($post_parent, $meta_key) : wnd_get_user_meta($args['data']['user_id'], $meta_key);

		/**
		 *@since 2019.05.06 构建 html
		 */
		$id = 'gallery-' . $this->id;
		$data = ' data-id="' . $id . '"';
		foreach ($args['data'] as $key => $value) {
			$data .= ' data-' . $key . '="' . $value . '" ';
		}unset($key, $value);

		// 上传区域
		$html = '<div id="' . $id . '" class="field upload-field">';
		$html .= $args['label'] ? '<label class="label">' . $args['label'] . '</label>' : '';
		$html .= '<div class="field"><div class="ajax-msg"></div></div>';

		$html .= '<div class="field">';

		$html .= '<a><img class="thumbnail" src="' . $args['thumbnail'] . '" height="' . $thumbnail_height . '" width="' . $thumbnail_width . '"></a>';

		$html .= '<div class="file">';
		$html .= '<input type="file" multiple="multiple" class="file-input" name="' . $args['name'] . '[]' . '"' . $data . 'accept="image/*" >';
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

		// 遍历输出图片集
		$html .= '<div class="gallery columns is-vcentered" data-thumbnail-width="' . $thumbnail_width . '" data-thumbnail-height="' . $thumbnail_height . '">';
		foreach ($images as $key => $attachment_id) {

			$attachment_url = wp_get_attachment_url($attachment_id);
			if (!$attachment_url) {
				unset($images[$key]); // 在字段数据中取消已经被删除的图片
				continue;
			}

			$html .= '<div id="img' . $attachment_id . '" class="column is-narrow">';
			$html .= '<a><img class="thumbnail" src="' . $attachment_url . '" height="' . $thumbnail_height . '" width="' . $thumbnail_width . '"></a>';
			$html .= '<a class="delete" data-id="' . $id . '" data-file_id="' . $attachment_id . '"></a>';
			$html .= '</div>';

		}
		unset($key, $attachment_id);
		wnd_update_post_meta($post_parent, $meta_key, $images); // 若字段中存在被删除的图片数据，此处更新

		$html .= '</div>';

		$html .= '</div>';

		parent::add_html($html);

	}

}
