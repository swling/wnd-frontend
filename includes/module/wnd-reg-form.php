<?php
namespace Wnd\Module;

use Exception;
use Wnd\View\Wnd_Form_User;

/**
 * @since 2019.01.21 注册表单
 */
class Wnd_Reg_Form extends Wnd_Module_Form {

	protected static function configure_form(array $args = []): object{
		$form = new Wnd_Form_User();
		$form->set_form_title('<span class="icon"><i class="fa fa-user"></i></span>&nbsp;' . __('注册', 'wnd'), true);

		if ('phone' == $args['type']) {
			$form->add_phone_verification('register', wnd_get_config('sms_template_r'), true);
		} else {
			$form->add_email_verification('register', '', true);
		}

		/**
		 * 注册用户通常为手机验证，或邮箱验证，为简化注册流程，可选择禁用用户名字段
		 * 后端将随机生成用户名，用户可通过邮箱或手机号登录
		 */
		if (wnd_get_config('disable_user_login') != 1) {
			$form->add_user_login('', __('账号', 'wnd'));
		}
		$form->add_user_password('', __('密码', 'wnd'));
		$form->add_user_display_name('', __('名称', 'wnd'));

		if (wnd_get_config('agreement_url')) {
			$form->add_checkbox(
				[
					'name'     => 'agreement',
					'options'  => [__('已阅读并同意：', 'wnd') => 1],
					'checked'  => 1,
					'required' => 'required',
				]
			);
			$form->add_html('<i><a href="' . wnd_get_config('agreement_url') . '" target="_blank">' . get_option('blogname') . __('《注册协议》', 'wnd') . '</a></i>');
		}

		$form->set_route('action', 'user/wnd_reg');
		$form->set_submit_button(__('注册', 'wnd', 'wnd'));
		// 以当前函数名设置filter hook
		$form->set_filter(__CLASS__);
		return $form;
	}

	protected static function check($args) {
		// 设定默认值
		$args['type'] = $args['type'] ?? (wnd_get_config('enable_sms') ? 'phone' : 'email');

		// 已登录
		if (is_user_logged_in()) {
			throw new Exception(__('已登录', 'wnd'));
		}

		//已关闭注册
		if (!get_option('users_can_register')) {
			throw new Exception(__('站点已关闭注册', 'wnd'));
		}

		//未开启手机验证
		if ('phone' == $args['type'] and wnd_get_config('enable_sms') != 1) {
			throw new Exception(__('当前未配置短信验证', 'wnd'));
		}

		// 关闭了邮箱注册（强制手机验证）
		if ('email' == $args['type'] and 1 == wnd_get_config('disable_email_reg')) {
			throw new Exception(__('当前设置禁止邮箱注册', 'wnd'));
		}
	}
}
