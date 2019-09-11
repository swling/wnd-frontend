<?php

/**
 *适配本插件的ajax User表单类
 *@since 2019.03.11
 */
class Wnd_User_Form extends Wnd_WP_Form {

	protected $user;

	// 初始化构建
	public function __construct() {

		// 继承基础变量
		parent::__construct();

		// 新增拓展变量
		$this->user = wp_get_current_user();
	}

	public function add_user_login($label = '用户名', $placeholder = '用户名、手机、邮箱', $required = true) {
		$this->add_text(
			array(
				'name'        => '_user_user_login',
				'value'       => '',
				'placeholder' => $placeholder,
				'label'       => $label,
				'icon_left'   => '<i class="fas fa-user"></i>', // icon html @link https://fontawesome.com/
				'autofocus'   => 'autofocus',
				'required'    => $required,
			)
		);
	}

	public function add_user_email($label = '邮箱', $placeholder = '邮箱', $required = true) {
		$this->add_email(
			array(
				'name'        => '_user_user_email',
				'value'       => $this->user->user_email,
				'label'       => $label,
				'icon_left'   => '<i class="fa fa-at"></i>',
				'required'    => $required,
				'placeholder' => $placeholder,
			)
		);
	}

	public function add_user_display_name($label = '名称', $placeholder = '名称', $required = true) {
		$this->add_text(
			array(
				'name'        => '_user_display_name',
				'value'       => $this->user->display_name,
				'label'       => $label,
				'placeholder' => $placeholder,
				'icon_left'   => '<i class="fas fa-user"></i>',
				'required'    => $required,
			)
		);
	}

	public function add_user_password($label = '密码', $placeholder = '密码', $required = true) {
		$this->add_password(
			array(
				'name'        => '_user_user_pass',
				'value'       => '',
				'label'       => $label,
				'placeholder' => $placeholder,
				'icon_left'   => '<i class="fas fa-unlock-alt"></i>',
				'required'    => $required,
			)
		);
	}

	public function add_user_password_repeat($label = '确认密码', $placeholder = '密码', $required = true) {
		$this->add_password(
			array(
				'name'        => '_user_user_pass_repeat',
				'value'       => '',
				'label'       => $label,
				'placeholder' => $placeholder,
				'icon_left'   => '<i class="fas fa-unlock-alt"></i>',
				'required'    => $required,
			)
		);
	}

	public function add_user_new_password($label = '新密码', $placeholder = '新密码', $required = false) {
		$this->add_password(
			array(
				'name'        => '_user_new_pass',
				'value'       => '',
				'label'       => $label,
				'placeholder' => $placeholder,
				'icon_left'   => '<i class="fas fa-unlock-alt"></i>',
				'required'    => $required,
			)
		);
	}

	public function add_user_new_password_repeat($label = '确认新密码', $placeholder = '确认新密码', $required = false) {
		$this->add_password(
			array(
				'name'        => '_user_new_pass_repeat',
				'value'       => '',
				'label'       => $label,
				'placeholder' => $placeholder,
				'icon_left'   => '<i class="fas fa-unlock-alt"></i>',
				'required'    => $required,
			)
		);
	}

	public function add_user_url($label = '网站', $placeholder = '网站链接', $required = false) {
		$this->add_url(
			array(
				'name'        => '_user_user_url',
				'value'       => $this->user->user_url,
				'label'       => $label,
				'placeholder' => $placeholder,
				'icon_left'   => '<i class="fas fa-link"></i>',
				'required'    => $required,
			)
		);
	}

	public function add_user_description($label = '简介', $placeholder = '资料简介', $required = false) {
		$this->add_textarea(
			array(
				'name'        => '_wpusermeta_description',
				'label'       => $label,
				'placeholder' => $placeholder,
				'value'       => $this->user->description,
				'required'    => $required,
			)
		);
	}

	public function add_user_avatar($thumbnail_size = 100, $save_size = 200) {
		if (!$this->user->ID) {
			$this->add_html('<div class="notification">获取用户ID失败，无法上传头像！</div>');
			return;
		}

		$args = array(
			'label'          => '',
			'thumbnail_size' => array('width' => $thumbnail_size, 'height' => $thumbnail_size),
			'thumbnail'      => wnd_get_user_meta($this->user->ID, 'avatar_url') ?: WND_URL . 'static/images/default.jpg',
			'data'           => array(
				'meta_key'    => 'avatar',
				'save_width'  => $save_size,
				'save_height' => $save_size,
			),
			'delete_button'  => false,
		);
		$this->add_image_upload($args);
	}

	/**
	 *@since 2019.04.28 上传字段简易封装
	 *如需更多选项，请使用 add_image_upload、add_file_upload 方法 @see Wnd_WP_Form
	 */
	public function add_user_image_upload($meta_key, $size = array('width' => 200, 'height' => 200), $label = '') {
		if (!$this->user->ID) {
			$this->add_html('<div class="notification">获取用户ID失败，无法设置图像上传！</div>');
			return;
		}

		$args = array(
			'label'          => $label,
			'thumbnail_size' => array('width' => $size['width'], 'height' => $size['height']),
			'thumbnail'      => WND_URL . 'static/images/default.jpg',
			'data'           => array(
				'user_id'     => $this->user->ID,
				'meta_key'    => $meta_key,
				'save_width'  => $size['width'],
				'save_height' => $size['height'],
			),
			'delete_button'  => false,
		);
		$this->add_image_upload($args);
	}

	public function add_user_file_upload($meta_key, $label = '文件上传') {
		if (!$this->user->ID) {
			$this->add_html('<div class="notification">获取用户ID失败，无法设置文件上传！</div>');
			return;
		}

		$this->add_file_upload(
			array(
				'label' => $label,
				'data'  => array( // some hidden input,maybe useful in ajax upload
					'meta_key' => $meta_key,
					'user_id'  => $this->user->ID, //如果设置了post parent, 则上传的附件id将保留在对应的wnd_post_meta 否则保留为 wnd_user_meta
				),
			)
		);
	}
}
