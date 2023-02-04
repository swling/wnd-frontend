<?php
namespace Wnd\View;

/**
 * 适配本插件的ajax User表单类
 *
 * @since 2019.03.11
 */
class Wnd_Form_User extends Wnd_Form_WP {

	/**
	 * @since 0.9.26
	 *
	 * @param bool $is_horizontal 是否水平表单
	 */
	public function __construct($is_horizontal = false) {
		parent::__construct(true, false, $is_horizontal);
	}

	public function add_user_login($label = '账号', $placeholder = '用户名、手机、邮箱', $required = true) {
		$this->add_text(
			[
				'name'        => '_user_user_login',
				'value'       => $this->user->data->user_login ?? '',
				'placeholder' => $placeholder,
				'label'       => $label,
				'icon_left'   => '<i class="fas fa-user"></i>',
				'required'    => $required,
			]
		);
	}

	public function add_user_email($label = '邮箱', $placeholder = '邮箱', $required = true) {
		$this->add_email(
			[
				'name'        => '_user_user_email',
				'value'       => $this->user->data->user_email ?? '',
				'label'       => $label,
				'icon_left'   => '<i class="fa fa-at"></i>',
				'required'    => $required,
				'placeholder' => $placeholder,
			]
		);
	}

	/**
	 * 注意：当用户注册时未设置昵称，WP将自动根据用户名生成昵称
	 */
	public function add_user_display_name($label = '名称', $placeholder = '名称', $required = true) {
		$this->add_text(
			[
				'name'        => '_user_display_name',
				'value'       => $this->user->data->display_name ?? '',
				'label'       => $label,
				'placeholder' => $placeholder,
				'icon_left'   => '<i class="fa fa-address-card"></i>',
				'required'    => $required,
			]
		);
	}

	public function add_user_password($label = '密码', $placeholder = '密码', $required = true) {
		$this->__add_password('_user_user_pass', $label, $placeholder, $required);
	}

	public function add_user_password_repeat($label = '确认密码', $placeholder = '密码', $required = true) {
		$this->__add_password('_user_user_pass_repeat', $label, $placeholder, $required);
	}

	public function add_user_new_password($label = '新密码', $placeholder = '新密码', $required = false) {
		$this->__add_password('_user_new_pass', $label, $placeholder, $required);
	}

	public function add_user_new_password_repeat($label = '确认新密码', $placeholder = '确认新密码', $required = false) {
		$this->__add_password('_user_new_pass_repeat', $label, $placeholder, $required);
	}
	/**
	 * 封装密码字段
	 * 命名为 __add_password 而非 add_password 旨在避免和父类冲突
	 * @since 0.9.26
	 */
	private function __add_password($name, $label, $placeholder, $required) {
		parent::add_password(
			[
				'name'        => $name,
				'value'       => '',
				'label'       => $label,
				'placeholder' => $placeholder,
				'icon_left'   => '<i class="fas fa-unlock-alt"></i>',
				'icon_right'  => '<div class="hide-pw"><i class="fas fa-eye" style="pointer-events:all; cursor:pointer"></i></div>',
				'required'    => $required,
			]
		);
	}

	public function add_user_url($label = '网站', $placeholder = '网站链接', $required = false) {
		$this->add_url(
			[
				'name'        => '_user_user_url',
				'value'       => $this->user->data->user_url ?? '',
				'label'       => $label,
				'placeholder' => $placeholder,
				'icon_left'   => '<i class="fas fa-link"></i>',
				'required'    => $required,
			]
		);
	}

	public function add_user_description($label = '简介', $placeholder = '资料简介', $required = false) {
		$this->add_textarea(
			[
				'name'        => '_wpusermeta_description',
				'label'       => $label,
				'placeholder' => $placeholder,
				'value'       => $this->user->description ?? '',
				'required'    => $required,
			]
		);
	}

	public function add_user_avatar($thumbnail_size = 100, $save_size = 200) {
		if (!$this->user->ID) {
			$this->add_html('<div class="notification">' . __('请登录', 'wnd') . '</div>');
			return;
		}

		$args = [
			'label'             => __('头像', 'wnd'),
			'thumbnail_size'    => ['width' => $thumbnail_size, 'height' => $thumbnail_size],
			'default_thumbnail' => wnd_get_user_meta($this->user->ID, 'avatar_url') ?: WND_URL . 'static/images/default.jpg',
			'data'              => [
				'meta_key'    => 'avatar',
				'save_width'  => $save_size,
				'save_height' => $save_size,
			],
			'delete_button'     => false,
		];
		$this->add_image_upload($args);
	}

	/**
	 * 如需更多选项，请使用 add_image_upload、add_file_upload 方法 @see Wnd_Form_WP
	 * @since 2019.04.28 上传字段简易封装
	 */
	public function add_user_image_upload($meta_key, $save_width = 0, $save_height = 0, $label = '') {
		if (!$this->user->ID) {
			$this->add_html('<div class="notification">' . __('请登录', 'wnd') . '</div>');
			return;
		}

		$args = [
			'label'         => $label,
			'thumbnail'     => WND_URL . 'static/images/default.jpg',
			'data'          => [
				'user_id'     => $this->user->ID,
				'meta_key'    => $meta_key,
				'save_width'  => $save_width,
				'save_height' => $save_height,
			],
			'delete_button' => false,
		];
		$this->add_image_upload($args);
	}

	public function add_user_file_upload($meta_key, $label = '文件上传') {
		if (!$this->user->ID) {
			$this->add_html('<div class="notification">' . __('请登录', 'wnd') . '</div>');
			return;
		}

		$this->add_file_upload(
			[
				'label' => $label,
				'data'  => [
					'meta_key' => $meta_key,
					'user_id'  => $this->user->ID,
				],
			]
		);
	}
}
