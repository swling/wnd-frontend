<?php
namespace Wnd\View;

use Wnd\Controller\Wnd_Controller;
use Wnd\Controller\Wnd_Request;
use Wnd\Getway\Wnd_Captcha;
use Wnd\View\Wnd_Gallery;

/**
 * 适配本插件的ajax表单类
 * 常规情况下，未选中的checkbox 和radio等字段不会出现在提交的表单数据中
 * 在本环境中，为实现字段name nonce校验，未选中的字段也会发送一个空值到后台（通过 hidden字段实现），在相关数据处理上需要注意
 * 为保障表单不被前端篡改，会提取所有字段的name值，结合算法生成校验码，后端通过同样的方式提取数据，并做校验
 * @since 2019.03.08
 */
class Wnd_Form_WP extends Wnd_Form {

	protected $user;

	protected $filter = '';

	protected $form_names = [];

	protected $is_ajax_submit;

	protected $enable_captcha;

	protected $enable_verification_captcha;

	protected $captcha_service;

	/**
	 * 是否已执行被插件特定构造
	 * @since 09.25
	 */
	private $constructed = false;

	public static $primary_color;

	public static $second_color;

	/**
	 * @since 2019.07.17
	 *
	 * @param bool $is_ajax_submit 	是否ajax提交
	 * @param bool $enable_captcha 	提交时是否进行人机校验
	 * @param bool $is_horizontal  	水平表单
	 */
	public function __construct(bool $is_ajax_submit = true, bool $enable_captcha = false, $is_horizontal = false) {
		// 继承基础变量
		parent::__construct($is_horizontal);

		/**
		 * 基础属性
		 *
		 * - 人机校验：若当前未配置人机校验服务，则忽略传参，统一取消表单相关属性
		 */
		$this->user            = wp_get_current_user();
		$this->captcha_service = wnd_get_config('captcha_service');
		static::$primary_color = wnd_get_config('primary_color');
		static::$second_color  = wnd_get_config('second_color');
		$this->is_ajax_submit  = $is_ajax_submit;
		$this->enable_captcha  = $this->captcha_service ? $enable_captcha : false;

		/**
		 * @since 2019.07.17 ajax表单
		 */
		if ($this->is_ajax_submit) {
			$this->add_form_attr('onsubmit', 'return false');
		}

		/**
		 * OSS 浏览器直传选项
		 * @since 0.9.33.7
		 */
		if (static::is_oss_direct_upload()) {
			$this->add_form_attr('data-oss-direct-upload', '1');
		}
	}

	/**
	 * 是否为浏览器直传 OSS
	 * @since 0.9.33.7
	 */
	private static function is_oss_direct_upload(): bool {
		if (!wnd_get_config('enable_oss')) {
			return false;
		}

		$local_storage = (int) wnd_get_config('oss_local_storage');
		return ($local_storage < 0) ? true : false;
	}

	/**
	 * 设置表单字段数据 filter
	 */
	public function set_filter(string $filter) {
		$this->filter = $filter;
	}

	/**
	 * 设置表单提交至 Rest API
	 * @since 0.9.19
	 *
	 * @param $route    	string 		Rest API 路由
	 * @param $endpoint 	string 		Rest API 路由对应的后端处理本次提交的类名
	 */
	public function set_route(string $route, string $endpoint) {
		$this->add_form_attr('route', $route);
		$this->method = Wnd_Controller::$routes[$route]['methods'] ?? '';
		$this->action = Wnd_Controller::get_route_url($route, $endpoint);
		parent::set_action($this->action, $this->method);
	}

	/**
	 * @since 2019.05.26 表单按钮默认配色
	 */
	public function set_submit_button(string $text, string $class = '', bool $disabled = false) {
		$class = $class ?: 'is-' . static::$primary_color;
		$class .= $this->is_ajax_submit ? ' ajax-submit' : '';
		$class .= $this->enable_captcha ? ' captcha' : '';
		parent::set_submit_button($text, $class, $disabled);
	}

	/**
	 * 直接新增表单names数组元素
	 * 用于nonce校验，如直接通过html方式新增的表单字段，无法被提取，需要通过这种方式新增name，以通过nonce校验
	 * @since 2019.05.10
	 */
	public function add_input_name(string $name) {
		$this->form_names[] = $name;
	}

	// 富文本编辑器可能需要上传文件操作新增 nonce
	public function add_editor(array $args) {
		$this->add_form_attr('data-editor', '1');
		$args['type']       = 'editor';
		$args['upload_url'] = wnd_get_route_url('action', 'common/wnd_upload_file');
		parent::add_field($args);
	}

	/**
	 * 构建验证码字段
	 * 注册时若当前手机已注册，则无法发送验证码
	 * 找回密码时若当前手机未注册，则无法发送验证码
	 * @param string 	$device_type                 	email / phone
	 * @param string 	$type                        	register / reset_password / bind / verify
	 * @param string 	$template                    	短信模板
	 * @param bool   	$enable_verification_captcha 	获取验证码时是否进行人机校验
	 */
	private function add_verification_field(string $device_type, string $type, string $template = '', bool $enable_verification_captcha = true) {
		/**
		 * - 人机校验：若当前未配置人机校验服务，则忽略传参，统一取消人机验证
		 * - 同一表单，若设置了验证码（调用本方法），且开启了验证码人机验证，则提交时无效再次进行人机验证
		 * @since 0.9.11
		 */
		$this->enable_verification_captcha = $this->captcha_service ? $enable_verification_captcha : false;
		$this->enable_captcha              = $this->enable_verification_captcha ? false : $this->enable_captcha;

		// 配置手机或邮箱验证码基础信息
		if ('email' == $device_type) {
			$device      = $this->user->data->user_email ?? '';
			$name        = '_user_user_email';
			$placeholder = __('电子邮箱', 'wnd');
			$icon        = '<i class="fa fa-at"></i>';
		} elseif ('phone' == $device_type) {
			$device      = wnd_get_user_phone($this->user->ID);
			$name        = 'phone';
			$placeholder = __('手机号码', 'wnd');
			$icon        = '<i class="fas fa-mobile-alt"></i>';
		}

		// Action 层需要验证表单字段签名(需要完整包含 button data 属性名及固定值 action、device、人机验证字段 )
		$data_keys = ['action', 'type', 'template', 'device_type', 'device', 'interval'];
		if ($this->enable_verification_captcha) {
			$data_keys[] = Wnd_Captcha::$captcha_name;
			$data_keys[] = Wnd_Captcha::$captcha_nonce_name;
		}
		$sign = Wnd_Request::sign($data_keys);

		// 构建发送按钮
		$button = '<button type="button"';
		$button .= ' class="send-code button is-outlined is-' . static::$primary_color . '"';
		$button .= ' data-action="common/wnd_send_auth_code"';
		$button .= ' data-type="' . $type . '"';
		$button .= ' data-template="' . $template . '"';
		$button .= ' data-device_type="' . $device_type . '"';
		$button .= ' data-interval="' . wnd_get_config('min_verification_interval') . '"';
		if ($this->enable_verification_captcha) {
			$button .= ' data-' . Wnd_Captcha::$captcha_name . '=""';
			$button .= ' data-' . Wnd_Captcha::$captcha_nonce_name . '=""';
		}
		$button .= ' data-' . Wnd_Request::$sign_name . '="' . $sign . '"';
		$button .= '>' . __('获取验证码', 'wnd') . '</button>';

		$this->add_html('<div class="field validate-field-wrap">');
		// 当前用户未绑定手机或更换绑定手机
		if (!$device or 'bind' == $type) {
			$this->add_text(
				[
					'name'        => $name,
					'icon_left'   => $icon,
					'required'    => true,
					'placeholder' => $placeholder,
				]
			);

			// 验证当前账户设备
		} elseif ($device) {
			$this->add_text(
				[
					'name'     => $name,
					'value'    => $device,
					'readonly' => true,
					'required' => true,
				]
			);
		}

		$this->add_text(
			[
				'name'        => 'auth_code',
				'icon_left'   => '<i class="fas fa-key"></i>',
				'required'    => 'required',
				'label'       => '',
				'placeholder' => __('验证码', 'wnd'),
				'addon_right' => $button,
			]
		);

		$this->add_html('</div>');
	}

	/**
	 * 短信校验
	 * 注册时若当前手机已注册，则无法发送验证码
	 * 找回密码时若当前手机未注册，则无法发送验证码
	 * @param string 	$type                         					register / reset_password / bind / verify
	 * @param string 	$template                     						短信模板
	 * @param bool   		$enable_verification_captcha 	获取验证码时是否进行人机校验
	 */
	public function add_phone_verification(string $type = 'verify', string $template = '', bool $enable_verification_captcha = true) {
		$this->add_verification_field('phone', $type, $template, $enable_verification_captcha);
	}

	/**
	 * 邮箱校验
	 * 注册时若当前邮箱已注册，则无法发送验证码
	 * 找回密码时若当前邮箱未注册，则无法发送验证码
	 * @param string 	$type                         					register / reset_password / bind / verify
	 * @param string 	$template                     						邮件模板
	 * @param bool   		$enable_verification_captcha 	获取验证码时是否进行人机校验
	 */
	public function add_email_verification(string $type = 'verify', string $template = '', bool $enable_verification_captcha = true) {
		$this->add_verification_field('email', $type, $template, $enable_verification_captcha);
	}

	// Image upload
	public function add_image_upload(array $args) {
		$defaults = [
			'class'             => 'upload-field',
			'label'             => 'Image upland',
			'file_id'           => 0,
			'default_thumbnail' => apply_filters('wnd_default_thumbnail', WND_URL . 'static/images/default.jpg', $this),
			'thumbnail_size'    => ['width' => $this->thumbnail_width, 'height' => $this->thumbnail_height],
			'data'              => [],
			'delete_button'     => true,
		];
		$args = array_merge($defaults, $args);

		// 合并$data
		$defaults_data = [
			'post_parent' => 0,
			'user_id'     => $this->user->ID,
			'meta_key'    => 0,
			'save_width'  => 0, //图片文件存储最大宽度 0 为不限制
			'save_height' => 0, //图片文件存储最大过度 0 为不限制
			'is_paid'     => false,
		];
		$args['data'] = array_merge($defaults_data, $args['data']);

		/**
		 * 将$args['data']数组拓展为变量
		 * $post_parent
		 * $user_id
		 * $meta_key
		 * ……
		 * @since 2019.12.13
		 */
		extract($args['data']);

		// 固定data
		$args['data']['meta_key_nonce']   = wp_create_nonce($meta_key);
		$args['data']['thumbnail_width']  = $args['thumbnail_size']['width'];
		$args['data']['thumbnail_height'] = $args['thumbnail_size']['height'];

		// 根据 meta_key 查找目标文件
		$file_id  = $args['file_id'] ?: static::get_attachment_id($meta_key, $post_parent, $user_id);
		$file_url = static::get_attachment_url($file_id, $meta_key, $post_parent, $user_id);
		$file_url = $file_url ? wnd_get_thumbnail_url($file_url, $args['thumbnail_size']['width'], $args['thumbnail_size']['height']) : '';

		$args['thumbnail'] = $file_url ?: $args['default_thumbnail'];
		$args['file_id']   = $file_id ?: 0;

		parent::add_image_upload($args);
	}

	// File upload
	public function add_file_upload(array $args) {
		$defaults = [
			'class'         => 'upload-field',
			'label'         => 'File upload',
			'file_id'       => 0,
			'data'          => [],
			'delete_button' => true,
		];
		$args = array_merge($defaults, $args);

		$defaults_data = [
			'post_parent' => 0,
			'user_id'     => $this->user->ID,
			'meta_key'    => 0,
			'is_paid'     => false,
		];
		$args['data'] = array_merge($defaults_data, $args['data']);

		/**
		 * 将$args['data']数组拓展为变量
		 * $post_parent
		 * $user_id
		 * $meta_key
		 * ……
		 * @since 2019.12.13
		 */
		extract($args['data']);

		// 固定data
		$args['data']['meta_key_nonce'] = wp_create_nonce($meta_key);
		$args['data']['method']         = $this->is_ajax_submit ? 'ajax' : $this->method;

		// 根据 meta_key 查找目标文件
		$file_id  = $args['file_id'] ?: static::get_attachment_id($meta_key, $post_parent, $user_id);
		$file_url = static::get_attachment_url($file_id, $meta_key, $post_parent, $user_id);

		$args['file_id']   = $file_id ?: 0;
		$args['file_name'] = $file_url ? '<a href="' . $file_url . '" target="_blank">' . __('查看文件', 'wnd') . '</a>' : '……';

		parent::add_file_upload($args);
	}

	/**
	 *
	 * 相册上传
	 * 如果设置了post parent, 则上传的附件id将保留在对应的wnd_post_meta 否则保留为 wnd_user_meta
	 * meta_key: 	gallery
	 */
	public function add_gallery_upload(array $args) {
		$defaults = [
			'id'             => $this->id,
			'label'          => 'Gallery',
			'thumbnail_size' => ['width' => $this->thumbnail_width, 'height' => $this->thumbnail_height],
			'data'           => [],
			'ajax'           => $this->is_ajax_submit,
			'method'         => $this->method,
		];
		$args = array_merge($defaults, $args);

		// 合并$data
		$defaults_data = [
			'post_parent' => 0,
			'user_id'     => $this->user->ID,
			'meta_key'    => 'gallery',
			'save_width'  => 0, //图片文件存储最大宽度 0 为不限制
			'save_height' => 0, //图片文件存储最大过度 0 为不限制
		];
		$args['data'] = array_merge($defaults_data, $args['data']);

		// 构造 Html
		$this->add_html(Wnd_Gallery::build_gallery_upload($args, false));
	}

	// 构造表单
	public function build(): string{
		// 本插件特定的数据结构
		$this->wnd_structure();

		/**
		 * 构建表单
		 */
		return parent::build();
	}

	/**
	 * 构建本插件特定的数据结构
	 * - 本方法只应执行一次
	 * @since 0.9.25
	 */
	private function wnd_structure() {
		if ($this->constructed) {
			return false;
		} else {
			$this->constructed = true;
		}

		/**
		 * 设置表单过滤filter
		 *
		 */
		if ($this->filter) {
			$this->input_values = apply_filters($this->filter, $this->input_values);
		}

		/**
		 * 开启验证码字段
		 * @since 0.8.64
		 */
		if ($this->enable_captcha) {
			$this->add_hidden(Wnd_Captcha::$captcha_name, '');
			$this->add_hidden(Wnd_Captcha::$captcha_nonce_name, '');
		}

		/**
		 * @since 2019.05.09 设置表单fields校验，需要在$this->input_values filter 后执行
		 */
		$this->build_sign_field();
	}

	/**
	 * 根据当前表单所有字段name生成wp nonce 用于防止用户在前端篡改表单结构提交未经允许的数据
	 * @since 2019.05.09
	 */
	private function build_sign_field() {
		// 提取表单字段names
		foreach ($this->get_input_values() as $input_value) {
			if (!isset($input_value['name'])) {
				continue;
			}

			if (isset($input_value['disabled']) and $input_value['disabled']) {
				continue;
			}

			// 可能为多选字段：需要移除'[]'
			$this->form_names[] = rtrim($input_value['name'], '[]');
		}
		unset($input_value);

		// 根据表单字段生成wp nonce并加入表单字段
		$this->add_hidden(Wnd_Request::$sign_name, Wnd_Request::sign($this->form_names));
	}

	/**
	 * 根据meta key获取附件ID
	 * @since 2020.04.13
	 */
	private static function get_attachment_id(string $meta_key, int $post_parent, int $user_id): int {
		// option
		if (0 === stripos($meta_key, '_option_')) {
			return (int) Wnd_Form_Option::get_option_value_by_input_name($meta_key);
		}

		// post meta
		if ($post_parent) {
			return (int) wnd_get_post_meta($post_parent, $meta_key);
		}

		// user meta
		return (int) wnd_get_user_meta($user_id, $meta_key);
	}

	/**
	 * 获取附件URL
	 * 如果字段存在，但文件已不存在，例如已被后台删除，删除对应meta_key or option
	 * @since 2020.04.13
	 */
	private static function get_attachment_url(int $attachment_id, string $meta_key, int $post_parent, int $user_id): string{
		$attachment_url = $attachment_id ? wp_get_attachment_url($attachment_id) : false;

		if ($attachment_id and !$attachment_url) {
			if (0 === stripos($meta_key, '_option_')) {
				Wnd_Form_Option::delete_option_by_input_name($meta_key);
			} elseif ($post_parent) {
				wnd_delete_post_meta($post_parent, $meta_key);
			} else {
				wnd_delete_user_meta($user_id, $meta_key);
			}
		}

		return $attachment_url;
	}

	/**
	 * 表单脚本：将在表单结束成后加载
	 * @since 0.8.64
	 */
	private function render_script(): string {
		if ($this->enable_verification_captcha or $this->enable_captcha) {
			$captcha = Wnd_Captcha::get_instance();
		}

		// 短信、邮件
		$send_code_script = '
<script>
var sd_btn = document.querySelectorAll("button.send-code");
if (sd_btn) {
    sd_btn.forEach(function(btn) {
        btn.addEventListener("click", function() {
            wnd_send_code(this);
        });
    });
}
</script>';
		$send_code_script = $this->enable_verification_captcha ? $captcha->render_send_code_script() : $send_code_script;

		/**
		 * 表单提交：本提交代码仅作用域常规 php 渲染的静态表单
		 * vue 表单 @see static/js/form.js method： submit();
		 */
		$submit_script = '
<script>
var sub_btn = document.querySelectorAll("[type=submit]");
if (sub_btn) {
    sub_btn.forEach(function(btn) {
        btn.addEventListener("click", function() {
            wnd_ajax_submit(this);
        });
    });
}
</script>';
		$submit_script = $this->enable_captcha ? $captcha->render_submit_form_script() : $submit_script;

		// 构造完整脚本
		return $send_code_script . $submit_script;
	}

	/**
	 * 获取表单构造数组数据，可用于前端 JS 渲染
	 * @since 0.9.25
	 */
	public function get_structure(): array{
		// 本插件特定的数据结构
		$this->wnd_structure();

		$structure                  = parent::get_structure();
		$structure['script']        = $this->render_script();
		$structure['primary_color'] = static::$primary_color;
		$structure['second_color']  = static::$second_color;
		return $structure;
	}

	/**
	 * JavaScript 渲染表单
	 * @since 0.9.56.1
	 */
	public function render(string $element) {
		$structure = $this->get_structure();
		$json_var  = 'wnd_form_json_' . str_replace('-', '_', $this->id);
		$json      = json_encode($structure);

		echo '<script>let ' . $json_var . '= ' . $json . '; wnd_render_form(\'' . $element . '\',  ' . $json_var . ')</script>';
	}
}
