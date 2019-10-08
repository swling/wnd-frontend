<?php
namespace Wnd\Module;

use Wnd\View\Wnd_User_Form;

/**
 *@since 2019.01.21 注册表单
 *@param $type 	string	email/phone
 */
class Wnd_Reg_Form extends Wnd_Module {

	public static function build($type = 'email') {
		// 已登录
		if (is_user_logged_in()) {
			return '<script>wnd_alert_msg("已登录！")</script>';

			//已关闭注册
		} elseif (!get_option('users_can_register')) {
			return '<script>wnd_alert_msg("站点已关闭注册！")</script>';

			// 关闭了邮箱注册（强制手机验证）
		} elseif ($type == 'email' and wnd_get_option('wnd', 'wnd_disable_email_reg') == 1) {
			return "<script>wnd_alert_msg('当前设置禁止邮箱注册！')</script>";

			//为开启手机验证
		} elseif ($type == 'phone' and wnd_get_option('wnd', 'wnd_enable_sms') != 1) {
			return "<script>wnd_alert_msg('当前未配置短信验证！')</script>";

		}

		$form = new Wnd_User_Form();
		$form->add_form_attr('class', 'user-form');
		$form->set_form_title('<span class="icon"><i class="fa fa-user"></i></span>注册', true);

		/**
		 *注册用户通常为手机验证，或邮箱验证，为简化注册流程，可选择禁用用户名字段
		 *后端将随机生成用户名，用户可通过邮箱或手机号登录
		 */
		if (wnd_get_option('wnd', 'wnd_disable_user_login') != 1) {
			$form->add_user_login('用户名', '登录用户名', true);
		}
		$form->add_user_password();

		if ($type == 'phone') {
			$form->add_sms_verify('register', wnd_get_option('wnd', 'wnd_sms_template_r'));
		} else {
			$form->add_email_verify('register');
		}
		if (wnd_get_option('wnd', 'wnd_agreement_url') or 1) {
			$form->add_checkbox(
				array(
					'name'     => 'agreement',
					'value'    => 1,
					'checked'  => 1,
					'label'    => '我已阅读并同意注册协议<a href="' . wnd_get_option('wnd', 'wnd_agreement_url') . '" target="_blank">《注册协议》</a>',
					'required' => 'required',
				)
			);
		}

		$form->set_action('wnd_reg');
		$form->set_submit_button('注册');
		// 以当前函数名设置filter hook
		$form->set_filter(__CLASS__);
		$form->build();

		return $form->html;
	}
}
