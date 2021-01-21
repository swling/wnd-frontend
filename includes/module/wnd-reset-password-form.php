<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Form_User;

/**
 *@since 2019.01.28 找回密码
 */
class Wnd_Reset_Password_Form extends Wnd_Module {

	protected static function build($args = []): string{
		$type           = $args['type'] ?? 'email';
		$enable_captcha = !is_user_logged_in();

		$form = new Wnd_Form_User();
		if ('phone' == $type) {
			$form->set_form_title('<span class="icon"><i class="fas fa-mobile-alt"></i></span>&nbsp;' . __('重置密码', 'wnd'), true);
			$form->add_phone_verification('reset_password', wnd_get_config('sms_template_v'), $enable_captcha);
		} else {
			$form->set_form_title('<span class="icon"><i class="fa fa-at"></i></span>&nbsp;' . __('重置密码', 'wnd') . '</h3>', true);
			$form->add_email_verification('reset_password', '', $enable_captcha);
		}

		$form->add_user_new_password(__('新密码', 'wnd'), __('新密码', 'wnd'), true);
		$form->add_user_new_password_repeat(__('确认新密码', 'wnd'), __('确认新密码', 'wnd'), true);
		$form->set_route('action', 'wnd_reset_password');
		$form->set_submit_button(__('重置密码', 'wnd'));
		$form->set_filter(__CLASS__);
		$form->build();

		return $form->html;
	}
}
