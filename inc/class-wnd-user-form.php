<?php

/**
 *适配本插件的ajax User表单类
 *@since 2019.03.11
 */
class Wnd_User_Form extends Wnd_Ajax_Form {

	// 用户表单标题居中
	function build_form_header() {
		$html = '<form action="" method="POST" data-submit-type="ajax" onsubmit="return false" ';

		if ($this->upload) {
			$html .= ' enctype="multipart/form-data"';
		}

		if ($this->form_attr) {
			$html .= ' ' . $this->form_attr;
		}

		$html .= '>';

		if ($this->form_title) {
			$html .= '<div class="field is-grouped is-grouped-centered content">';
			$html .= '<h3>' . $this->form_title . '</h3>';
			$html .= '</div>';
		}

		$html .= '<div class="ajax-msg"></div>';

		$this->html = $html;
	}	

	function add_user_login() {
		parent::add_text(
			array(
				'name' => '_user_user_login',
				'value' => '',
				'placeholder' => '用户名、手机、邮箱',
				'label' => '用户名 <span class="required">*</span>',
				'has_icons' => 'left', //icon position "left" orf "right"
				'icon' => '<i class="fas fa-user"></i>', // icon html @link https://fontawesome.com/
				'autofocus' => 'autofocus',
				'required' => true,
			)
		);
	}

	function add_user_password() {
		parent::add_password(
			array(
				'name' => '_user_user_pass',
				'value' => '',
				'label' => '密码 <span class="required">*</span>',
				'placeholder' => '密码',
				'has_icons' => 'left',
				'icon' => '<i class="fas fa-unlock-alt"></i>',
				'required' => true,
			)
		);
	}

	function add_user_new_password() {
		parent::add_password(
			array(
				'name' => '_user_new_pass',
				'value' => '',
				'label' => '新密码 <span class="required">*</span>',
				'placeholder' => '新密码',
				'has_icons' => 'left',
				'icon' => '<i class="fas fa-unlock-alt"></i>',
				'required' => true,
			)
		);
	}

	function add_user_new_password_repeat() {
		parent::add_password(
			array(
				'name' => '_user_new_pass_repeat',
				'value' => '',
				'label' => '确认新密码 <span class="required">*</span>',
				'placeholder' => '确认新密码',
				'has_icons' => 'left',
				'icon' => '<i class="fas fa-unlock-alt"></i>',
				'required' => true,
			)
		);
	}

	function add_user_display_name($display_name = '') {
		parent::add_text(
			array(
				'name' => '_user_display_name',
				'value' => $display_name,
				'label' => '名称 <span class="required">*</span>',
				'placeholder' => '用户名称',
				'has_icons' => 'left',
				'icon' => '<i class="fas fa-user"></i>',
				'required' => true,
			)
		);
	}

	function add_user_url($user_url = '') {
		parent::add_text(
			array(
				'name' => '_user_user_url',
				'value' => $user_url,
				'label' => '网站',
				'placeholder' => '网站链接',
				'has_icons' => 'left',
				'icon' => '<i class="fas fa-link"></i>',
				'required' => false,
			)
		);
	}

	function add_user_description($description = '') {
		parent::add_textarea(
			array(
				'name' => '_wpusermeta_description',
				'label' => '简介',
				'placeholder' => '简介资料',
				'value' => $description,
			)
		);
	}

	function add_user_avatar($thumbnail_size, $save_size) {
		/*头像上传*/
		$args = array(
			'id' => 'user-avatar',
			'label'	=>'',
			'thumbnail_size' => array('width' => $thumbnail_size, 'height' => $thumbnail_size),
			'thumbnail' => WNDWP_URL . '/static/images/default.jpg',
			'data' => array(
				'meta_key' => 'avatar',
				'save_width' => $save_size,
				'save_height' => $save_size,
			),
		);
		parent::add_image_upload($args);
	}

}
