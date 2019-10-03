<?php
namespace Wnd\Template;

use Wnd\View\Wnd_Form_User;

/**
 *@since 2019.01.28 找回密码
 *@param $type 	string	email/phone
 */
class Wnd_Reset_Pass_Form extends Wnd_Template {

	public static function build($type = 'email') {
		if ($type == 'phone') {
			//1、验证短信重置密码
			if (wnd_get_option('wnd', 'wnd_enable_sms') != 1) {
				return '<script type="text/javascript">wnd_alert_msg(\'短信验证功能未启用！\')</script>';
			}
		}

		$form = new Wnd_Form_User();
		$form->add_form_attr('class', 'user-form');
		if ($type == 'phone') {
			$form->set_form_title('<span class="icon"><i class="fa fa-phone-square"></i></span>手机验证', true);
			$form->add_sms_verify('reset_password', wnd_get_option('wnd', 'wnd_sms_template_v'));
		} else {
			$form->set_form_title('<span class="icon"><i class="fa fa-at"></i></span>邮箱验证</h3>', true);
			$form->add_email_verify('reset_password');
		}

		$form->add_user_new_password('新密码', '新密码', true);
		$form->add_user_new_password_repeat('确认新密码', '确认新密码', true);
		$form->set_action('wnd_user_reset_password');
		$form->set_submit_button('重置密码');
		$form->set_filter(__CLASS__);
		$form->build();

		return $form->html;
	}
}
