<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Form_User;

/**
 *@since 2019.07.23 用户设置邮箱表单
 */
class Wnd_Bind_Phone_Form extends Wnd_Module_User {

	protected static function build(): string{
		$current_user       = wp_get_current_user();
		$current_user_phone = wnd_get_user_phone($current_user->ID);

		$form = new Wnd_Form_User();
		$form->set_form_title('<span class="icon"><i class="fas fa-mobile-alt"></i></span>' . __('绑定手机', 'wnd'), true);

		// 如果当前用户更改手机号，需要验证密码，首次绑定不需要
		if ($current_user_phone) {
			$form->add_text(
				[
					'value'    => $current_user_phone,
					'disabled' => true,
				]
			);
			$form->add_user_password(__('密码', 'wnd'), __('密码', 'wnd'));
		}

		$form->add_phone_verification('bind', wnd_get_config('sms_template_v'), false);
		$form->set_ajax_action('action', 'wnd_bind_account');
		$form->set_submit_button(__('保存', 'wnd'));
		$form->build();

		return $form->html;
	}
}
