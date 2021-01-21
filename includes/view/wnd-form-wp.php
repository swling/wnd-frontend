<?php
namespace Wnd\View;

use Wnd\Controller\Wnd_Controller;
use Wnd\Utility\Wnd_Captcha;
use Wnd\Utility\Wnd_Request;
use Wnd\View\Wnd_Gallery;

/**
 *适配本插件的ajax表单类
 *@since 2019.03.08
 *常规情况下，未选中的checkbox 和radio等字段不会出现在提交的表单数据中
 *在本环境中，为实现字段name nonce校验，未选中的字段也会发送一个空值到后台（通过 hidden字段实现），在相关数据处理上需要注意
 *为保障表单不被前端篡改，会提取所有字段的name值，结合算法生成校验码，后端通过同样的方式提取$_POST数据，并做校验
 *
 */
class Wnd_Form_WP extends Wnd_Form {

	protected $user;

	protected $filter = '';

	protected $form_names = [];

	protected $message = '';

	protected $is_ajax_submit;

	protected $enable_captcha;

	protected $enable_verification_captcha;

	protected $captcha_service;

	public static $primary_color;

	public static $second_color;

	/**
	 *@since 2019.07.17
	 *
	 *@param bool $is_ajax_submit 	是否ajax提交
	 *@param bool $enable_captcha 	提交时是否进行人机校验
	 */
	public function __construct(bool $is_ajax_submit = true, bool $enable_captcha = false) {
		// 继承基础变量
		parent::__construct();

		/**
		 *基础属性
		 *
		 *- 人机校验：若当前未配置人机校验服务，则忽略传参，统一取消表单相关属性
		 */
		$this->user            = wp_get_current_user();
		$this->captcha_service = wnd_get_config('captcha_service');
		static::$primary_color = wnd_get_config('primary_color');
		static::$second_color  = wnd_get_config('second_color');
		$this->is_ajax_submit  = $is_ajax_submit;
		$this->enable_captcha  = $this->captcha_service ? $enable_captcha : false;
	}

	/**
	 *设置表单字段数据 filter
	 */
	public function set_filter(string $filter) {
		$this->filter = $filter;
	}

	/**
	 *@since 0.9.19
	 *设置表单提交至 Rest API
	 *
	 *@param $route 	string 		Rest API 路由
	 *@param $endpoint 	string 		Rest API 路由对应的后端处理本次提交的类名
	 */
	public function set_route(string $route, string $endpoint) {
		if ('action' == $route) {
			$this->add_hidden('_ajax_nonce', wp_create_nonce($endpoint));
		}

		$this->method = Wnd_Controller::$routes[$route]['methods'] ?? '';
		$this->action = Wnd_Controller::get_route_url($route, $endpoint);

		parent::set_action($this->action, $this->method);
	}

	public function set_message(string $message) {
		$this->message = $message;
	}

	/**
	 *@since 2019.05.26 表单按钮默认配色
	 */
	public function set_submit_button(string $text, string $class = '', bool $disabled = false) {
		$class = $class ?: 'is-' . static::$primary_color;
		$class .= $this->is_ajax_submit ? ' ajax-submit' : '';
		$class .= $this->enable_captcha ? ' captcha' : '';
		parent::set_submit_button($text, $class, $disabled);
	}

	/**
	 *@since 2019.05.10
	 *直接新增表单names数组元素
	 *用于nonce校验，如直接通过html方式新增的表单字段，无法被提取，需要通过这种方式新增name，以通过nonce校验
	 **/
	public function add_input_name(string $name) {
		$this->form_names[] = $name;
	}

	/**
	 *@since 2019.05.09
	 *未被选中的radio 与checkbox将不会发送到后端，会导致wnd_form_nonce 校验失败，此处通过设置hidden字段修改
	 *
	 *@since 0.8.76 GET 表单不设置。
	 * - 注意：需要此设置生效，则必须在新增字段之前配置 set_action($action, 'GET')，以修改 $this->method 值
	 */
	public function add_radio(array $args) {
		if ('GET' != $this->method) {
			$this->add_hidden($args['name'], '');
		}
		parent::add_radio($args);
	}

	public function add_checkbox(array $args) {
		if ('GET' != $this->method) {
			$this->add_hidden($args['name'], '');
		}
		parent::add_checkbox($args);
	}

	/**
	 *构建验证码字段
	 *@param string 	$device_type  					email / phone
	 *@param string 	$type 		  					register / reset_password / bind / verify
	 *@param string 	$template 						短信模板
	 *@param bool 		$enable_verification_captcha 	获取验证码时是否进行人机校验
	 *
	 *注册时若当前手机已注册，则无法发送验证码
	 *找回密码时若当前手机未注册，则无法发送验证码
	 **/
	protected function add_verification_field(string $device_type, string $type, string $template = '', bool $enable_verification_captcha = true) {
		/**
		 * @since 0.9.11
		 *
		 * - 人机校验：若当前未配置人机校验服务，则忽略传参，统一取消人机验证
		 * - 同一表单，若设置了验证码（调用本方法），且开启了验证码人机验证，则提交时无效再次进行人机验证
		 */
		$this->enable_verification_captcha = $this->captcha_service ? $enable_verification_captcha : false;
		$this->enable_captcha              = $this->enable_verification_captcha ? false : $this->enable_captcha;

		// 配置手机或邮箱验证码基础信息
		if ('email' == $device_type) {
			$device      = $this->user->data->user_email ?? '';
			$name        = '_user_user_email';
			$label       = __('邮箱', 'wnd');
			$placeholder = __('电子邮箱', 'wnd');
			$icon        = '<i class="fa fa-at"></i>';
		} elseif ('phone' == $device_type) {
			$device      = wnd_get_user_phone($this->user->ID);
			$name        = 'phone';
			$label       = __('手机', 'wnd');
			$placeholder = __('手机号码', 'wnd');
			$icon        = '<i class="fas fa-mobile-alt"></i>';
		}

		// Action 层需要验证表单字段签名(需要完整包含 button data 属性名及固定值 action、device、人机验证字段 )
		$data_keys = [
			'action',
			'type',
			'template',
			'_ajax_nonce',
			'type_nonce',
			'device_type',
			'device_name',
			'device',
			'interval',
		];
		if ($this->enable_verification_captcha) {
			$data_keys[] = Wnd_Captcha::$captcha_name;
			$data_keys[] = Wnd_Captcha::$captcha_nonce_name;
		}
		$sign = Wnd_Request::sign($data_keys);

		// 构建发送按钮
		$button = '<button type="button"';
		$button .= ' class="send-code button is-outlined is-' . static::$primary_color . '"';
		$button .= ' data-action="wnd_send_code"';
		$button .= ' data-type="' . $type . '"';
		$button .= ' data-template="' . $template . '"';
		$button .= ' data-_ajax_nonce="' . wp_create_nonce('wnd_send_code') . '"';
		$button .= ' data-type_nonce="' . wp_create_nonce($device_type . $type) . '"';
		$button .= ' data-device_type="' . $device_type . '"';
		$button .= ' data-device_name="' . $label . '"';
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
					'label'       => $label,
					'placeholder' => $placeholder,
				]
			);

			// 验证当前账户设备
		} elseif ($device) {
			$this->add_text(
				[
					'name'     => $name,
					'label'    => $label,
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
	 *短信校验
	 *@param string 	$type 		  					register / reset_password / bind / verify
	 *@param string 	$template 						短信模板
	 *@param bool 		$enable_verification_captcha 	获取验证码时是否进行人机校验
	 *注册时若当前手机已注册，则无法发送验证码
	 *找回密码时若当前手机未注册，则无法发送验证码
	 **/
	public function add_phone_verification(string $type = 'verify', string $template = '', bool $enable_verification_captcha = true) {
		$this->add_verification_field('phone', $type, $template, $enable_verification_captcha);
	}

	/**
	 *邮箱校验
	 *@param string 	$type 		  					register / reset_password / bind / verify
	 *@param string 	$template 						邮件模板
	 *@param bool 		$enable_verification_captcha 	获取验证码时是否进行人机校验
	 *注册时若当前邮箱已注册，则无法发送验证码
	 *找回密码时若当前邮箱未注册，则无法发送验证码
	 **/
	public function add_email_verification(string $type = 'verify', string $template = '', bool $enable_verification_captcha = true) {
		$this->add_verification_field('email', $type, $template, $enable_verification_captcha);
	}

	// Image upload
	public function add_image_upload(array $args) {
		$defaults = [
			'class'          => 'upload-field',
			'label'          => 'Image upland',
			'name'           => 'wnd_file',
			'file_id'        => 0,
			'thumbnail'      => apply_filters('wnd_default_thumbnail', WND_URL . 'static/images/default.jpg', $this),
			'thumbnail_size' => ['width' => $this->thumbnail_width, 'height' => $this->thumbnail_height],
			'data'           => [],
			'delete_button'  => true,
		];
		$args = array_merge($defaults, $args);

		// 合并$data
		$defaults_data = [
			'post_parent' => 0,
			'user_id'     => $this->user->ID,
			'meta_key'    => 0,
			'save_width'  => 0, //图片文件存储最大宽度 0 为不限制
			'save_height' => 0, //图片文件存储最大过度 0 为不限制
		];
		$args['data'] = array_merge($defaults_data, $args['data']);

		/**
		 *@since 2019.12.13
		 *
		 *将$args['data']数组拓展为变量
		 *$post_parent
		 *$user_id
		 *$meta_key
		 *……
		 */
		extract($args['data']);

		// 固定data
		$args['data']['is_image']         = '1';
		$args['data']['upload_nonce']     = wp_create_nonce('wnd_upload_file');
		$args['data']['delete_nonce']     = wp_create_nonce('wnd_delete_file');
		$args['data']['meta_key_nonce']   = wp_create_nonce($meta_key);
		$args['data']['thumbnail']        = $args['thumbnail'];
		$args['data']['thumbnail_width']  = $args['thumbnail_size']['width'];
		$args['data']['thumbnail_height'] = $args['thumbnail_size']['height'];
		$args['data']['method']           = $this->is_ajax_submit ? 'ajax' : $this->method;

		// 根据 meta_key 查找目标文件
		$file_id  = $args['file_id'] ?: static::get_attachment_id($meta_key, $post_parent, $user_id);
		$file_url = static::get_attachment_url($file_id, $meta_key, $post_parent, $user_id);
		$file_url = $file_url ? wnd_get_thumbnail_url($file_url, $args['thumbnail_size']['width'], $args['thumbnail_size']['height']) : '';

		$args['thumbnail'] = $file_url ?: $args['thumbnail'];
		$args['file_id']   = $file_id ?: 0;

		parent::add_image_upload($args);
	}

	// File upload
	public function add_file_upload(array $args) {
		$defaults = [
			'class'         => 'upload-field',
			'label'         => 'File upload',
			'name'          => 'wnd_file',
			'file_id'       => 0,
			'data'          => [],
			'delete_button' => true,
		];
		$args = array_merge($defaults, $args);

		$defaults_data = [
			'post_parent' => 0,
			'user_id'     => $this->user->ID,
			'meta_key'    => 0,
		];
		$args['data'] = array_merge($defaults_data, $args['data']);

		/**
		 *@since 2019.12.13
		 *
		 *将$args['data']数组拓展为变量
		 *
		 *$post_parent
		 *$user_id
		 *$meta_key
		 *……
		 */
		extract($args['data']);

		// 固定data
		$args['data']['upload_nonce']   = wp_create_nonce('wnd_upload_file');
		$args['data']['delete_nonce']   = wp_create_nonce('wnd_delete_file');
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
	 *相册上传
	 *如果设置了post parent, 则上传的附件id将保留在对应的wnd_post_meta 否则保留为 wnd_user_meta
	 *meta_key: 	gallery
	 */
	public function add_gallery_upload(array $args) {
		/**
		 *@since 0.9.0
		 *
		 *相册字段为纯 HTML 构建，需要新增对应 input_name 以完成表单签名
		 */
		$input_name = 'wnd_file';
		$this->add_input_name($input_name);

		$defaults = [
			'id'             => $this->id,
			'label'          => 'Gallery',
			'thumbnail_size' => ['width' => $this->thumbnail_width, 'height' => $this->thumbnail_height],
			'data'           => [],
			'ajax'           => $this->is_ajax_submit,
			'method'         => $this->method,
			'input_name'     => $input_name,
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

	// 构造表单，可设置WordPress filter 过滤表单的input_values
	public function build() {
		/**
		 *设置表单过滤filter
		 **/
		if ($this->filter) {
			$this->input_values = apply_filters($this->filter, $this->input_values);
		}

		/**
		 *@since 0.8.64
		 *开启验证码字段
		 */
		if ($this->enable_captcha) {
			$this->add_hidden(Wnd_Captcha::$captcha_name, '');
			$this->add_hidden(Wnd_Captcha::$captcha_nonce_name, '');
		}

		/**
		 *@since 2019.05.09 设置表单fields校验，需要在$this->input_values filter 后执行
		 */
		$this->build_sign_field();

		/**
		 *构建表单
		 */
		parent::build();
	}

	/**
	 *@since 2019.05.09
	 *根据当前表单所有字段name生成wp nonce 用于防止用户在前端篡改表单结构提交未经允许的数据
	 */
	protected function build_sign_field() {
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
		$this->add_html(Wnd_Request::build_sign_field($this->form_names));
	}

	/**
	 *构建表单头部
	 */
	protected function build_form_header() {
		/**
		 *@since 2019.07.17 ajax表单
		 */
		if ($this->is_ajax_submit) {
			$this->add_form_attr('onsubmit', 'return false');
		}
		parent::build_form_header();

		$this->html .= '<div class="ajax-message">' . $this->message . '</div>';
	}

	/**
	 *@since 2020.04.13
	 *
	 *根据meta key获取附件ID
	 */
	protected static function get_attachment_id(string $meta_key, int $post_parent, int $user_id): int {
		// option
		if (0 === stripos($meta_key, '_option_')) {
			return (int) Wnd_Form_Option::get_option_value_by_input_name($meta_key);
		}

		// post meta
		if ($post_parent) {
			return wnd_get_post_meta($post_parent, $meta_key);
		}

		// user meta
		return wnd_get_user_meta($user_id, $meta_key);
	}

	/**
	 *@since 2020.04.13
	 *
	 *获取附件URL
	 *如果字段存在，但文件已不存在，例如已被后台删除，删除对应meta_key or option
	 */
	protected static function get_attachment_url(int $attachment_id, string $meta_key, int $post_parent, int $user_id): string{
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
	 *表单脚本：将在表单结束成后加载
	 *@since 0.8.64
	 */
	protected function render_script(): string {
		if ($this->enable_verification_captcha or $this->enable_captcha) {
			$captcha = Wnd_Captcha::get_instance();
		}

		// 短信、邮件
		$send_code_script = '
		<script>
			$(function() {
				$(".send-code").click(function() {
					wnd_send_code($(this));
				});
			});
		</script>';
		$send_code_script = $this->enable_verification_captcha ? $captcha->render_send_code_script() : $send_code_script;

		// 表单提交
		$submit_script = '
		<script>
			$(function() {
				$("[type=\'submit\'].ajax-submit").off("click").on("click", function() {
					var form_id = $(this).closest("form").attr("id");
					if (form_id) {
						wnd_ajax_submit(form_id);
					} else {
						wnd_alert_msg(wnd.msg.system_error, 1);
					}
				});
			});
		</script>';
		$submit_script = $this->enable_captcha ? $captcha->render_submit_form_script() : $submit_script;

		// 构造完整脚本
		return $send_code_script . $submit_script;
	}
}
