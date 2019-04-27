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

	function build_form_header() {
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
			'is_image' => '1',
			'thumbnail' => $args['thumbnail'],
			'upload_nonce' => wp_create_nonce('wnd_ajax_upload_file'),
			'delete_nonce' => wp_create_nonce('wnd_ajax_delete_file'),
			'post_parent' => 0,
			'user_id' => get_current_user_id(),
			'meta_key' => 0,
		);
		$args['data'] = array_merge($defaults_data, $args['data']);

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
			'upload_nonce' => wp_create_nonce('wnd_ajax_upload_file'),
			'delete_nonce' => wp_create_nonce('wnd_ajax_delete_file'),
			'post_parent' => 0,
			'user_id' => get_current_user_id(),
			'meta_key' => 0,
		);
		$args['data'] = array_merge($defaults_data, $args['data']);

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

	function add_post_image_upload($post_id, $meta_key, $size = array('width' => 200, 'height' => 200), $label = '') {
		$thumbnail_defaults = array(
			// 'id' => 'thumbnail',
			'label' => $label,
			'thumbnail_size' => array('width' => $size['width'], 'height' => $size['height']),
			'thumbnail' => WNDWP_URL . '/static/images/default.jpg',
			'data' => array(
				'post_parent' => $post_id,
				'meta_key' => $meta_key,
				'save_width' => $size['width'],
				'save_height' => $size['height'],
			),
			'delete_button' => false,
		);
		$thumbnail_args = $thumbnail_defaults;
		$this->add_image_upload($thumbnail_args);
	}

	function add_user_image_upload($user_id, $meta_key, $size = array('width' => 200, 'height' => 200), $label = '') {
		$thumbnail_defaults = array(
			// 'id' => 'thumbnail',
			'label' => $label,
			'thumbnail_size' => array('width' => $size['width'], 'height' => $size['height']),
			'thumbnail' => WNDWP_URL . '/static/images/default.jpg',
			'data' => array(
				'user_id' => $user_id,
				'meta_key' => $meta_key,
				'save_width' => $size['width'],
				'save_height' => $size['height'],
			),
			'delete_button' => false,
		);
		$thumbnail_args = $thumbnail_defaults;
		$this->add_image_upload($thumbnail_args);
	}

	function add_post_file_upload($post_id, $meta_key, $label = '文件上传') {
		$this->add_file_upload(
			array(
				'label' => $label,
				'data' => array( // some hidden input,maybe useful in ajax upload
					'meta_key' => $meta_key,
					'post_parent' => $post_id, //如果设置了post parent, 则上传的附件id将保留在对应的wnd_post_meta 否则保留为 wnd_user_meta
				),
			)
		);
	}

	function add_user_file_upload($user_id, $meta_key, $label = '文件上传') {
		$this->add_file_upload(
			array(
				'label' => $label,
				'data' => array( // some hidden input,maybe useful in ajax upload
					'meta_key' => $meta_key,
					'user_id' => $user_id, //如果设置了post parent, 则上传的附件id将保留在对应的wnd_post_meta 否则保留为 wnd_user_meta
				),
			)
		);
	}

}
