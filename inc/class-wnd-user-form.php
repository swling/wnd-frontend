<?php

/**
 *适配本插件的ajax User表单类
 *@since 2019.03.11
 */
class Wnd_User_Form extends Wnd_Ajax_Form {

	public $user;

	// 初始化构建
	function __construct() {

		// 继承基础变量
		parent::__construct();

		// 新增拓展变量
		$this->user = wp_get_current_user();
	}

	function add_user_login($placeholder = '用户名、手机、邮箱') {
		parent::add_text(
			array(
				'name' => '_user_user_login',
				'value' => '',
				'placeholder' => $placeholder,
				'label' => '用户名 <span class="required">*</span>',
				'has_icons' => 'left', //icon position "left" orf "right"
				'icon' => '<i class="fas fa-user"></i>', // icon html @link https://fontawesome.com/
				'autofocus' => 'autofocus',
				'required' => true,
			)
		);
	}

	function add_user_email($placeholder = '邮箱') {
		parent::add_text(
			array(
				'name' => '_user_user_email',
				'value' => $this->user->user_email,
				'label' => '邮箱 <span class="required">*</span>',
				'has_icons' => 'left',
				'icon' => '<i class="fa fa-at"></i>',
				'required' => 'required',
				'placeholder' => $placeholder,
			)
		);
	}

	function add_user_password($placeholder = '密码') {
		parent::add_password(
			array(
				'name' => '_user_user_pass',
				'value' => '',
				'label' => '密码 <span class="required">*</span>',
				'placeholder' => $placeholder,
				'has_icons' => 'left',
				'icon' => '<i class="fas fa-unlock-alt"></i>',
				'required' => true,
			)
		);
	}

	function add_user_new_password($placeholder = '新密码') {
		parent::add_password(
			array(
				'name' => '_user_new_pass',
				'value' => '',
				'label' => '新密码 <span class="required">*</span>',
				'placeholder' => $placeholder,
				'has_icons' => 'left',
				'icon' => '<i class="fas fa-unlock-alt"></i>',
				'required' => true,
			)
		);
	}

	function add_user_new_password_repeat($placeholder = '确认新密码') {
		parent::add_password(
			array(
				'name' => '_user_new_pass_repeat',
				'value' => '',
				'label' => '确认新密码 <span class="required">*</span>',
				'placeholder' => $placeholder,
				'has_icons' => 'left',
				'icon' => '<i class="fas fa-unlock-alt"></i>',
				'required' => true,
			)
		);
	}

	function add_user_display_name($placeholder = '名称') {
		parent::add_text(
			array(
				'name' => '_user_display_name',
				'value' => $this->user->display_name,
				'label' => '名称 <span class="required">*</span>',
				'placeholder' => $placeholder,
				'has_icons' => 'left',
				'icon' => '<i class="fas fa-user"></i>',
				'required' => true,
			)
		);
	}

	function add_user_url($placeholder = '网站链接') {
		parent::add_text(
			array(
				'name' => '_user_user_url',
				'value' => $this->user->user_url,
				'label' => '网站',
				'placeholder' => $placeholder,
				'has_icons' => 'left',
				'icon' => '<i class="fas fa-link"></i>',
				'required' => false,
			)
		);
	}

	function add_user_description($placeholder = '资料简介') {
		parent::add_textarea(
			array(
				'name' => '_wpusermeta_description',
				'label' => '简介',
				'placeholder' => $placeholder,
				'value' => $this->user->description,
			)
		);
	}

	function add_user_avatar($thumbnail_size = 150, $save_size = 200) {
		$args = array(
			'label' => '',
			'thumbnail_size' => array('width' => $thumbnail_size, 'height' => $thumbnail_size),
			'thumbnail' => WNDWP_URL . '/static/images/default.jpg',
			'data' => array(
				'meta_key' => 'avatar',
				'save_width' => $save_size,
				'save_height' => $save_size,
			),
			'delete_button' => false,
		);
		parent::add_image_upload($args);
	}

	/**
	 *@since 2019.04.28 上传字段简易封装
	 *如需更多选项，请使用 add_image_upload、add_file_upload 方法 @see Wnd_Ajax_Form
	 */
	function add_user_image_upload($meta_key, $size = array('width' => 200, 'height' => 200), $label = '') {
		$args = array(
			'label' => $label,
			'thumbnail_size' => array('width' => $size['width'], 'height' => $size['height']),
			'thumbnail' => WNDWP_URL . '/static/images/default.jpg',
			'data' => array(
				'user_id' => $this->user->ID,
				'meta_key' => $meta_key,
				'save_width' => $size['width'],
				'save_height' => $size['height'],
			),
			'delete_button' => false,
		);
		self::add_image_upload($args);
	}

	function add_user_file_upload($meta_key, $label = '文件上传') {
		self::add_file_upload(
			array(
				'label' => $label,
				'data' => array( // some hidden input,maybe useful in ajax upload
					'meta_key' => $meta_key,
					'user_id' => $this->user->ID, //如果设置了post parent, 则上传的附件id将保留在对应的wnd_post_meta 否则保留为 wnd_user_meta
				),
			)
		);
	}

}
