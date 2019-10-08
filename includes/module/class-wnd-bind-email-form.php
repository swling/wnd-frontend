<?php
namespace Wnd\Module;

use Wnd\View\Wnd_User_Form;

/**
 *@since 2019.07.23 用户设置邮箱表单
 */
class Wnd_Bind_Email_Form extends Wnd_Module {

	public static function build() {
		$current_user = wp_get_current_user();
		if (!$current_user->ID) {
			return '<script>wnd_alert_msg(\'请登录\')</script>';
		}

		$form = new Wnd_User_Form();
		$form->add_form_attr('class', 'user-form');
		$form->set_form_title('<span class="icon"><i class="fa fa-at"></i></span>绑定邮箱', true);

		// 如果当前用户更改邮箱，则需要验证密码，首次绑定不需要
		if ($current_user->data->user_email) {
			$form->add_text(
				array(
					'label'    => '当前邮箱',
					'value'    => $current_user->data->user_email,
					'disabled' => true,
				)
			);
			$form->add_user_password('当前密码');
		}

		$form->add_email_verify('bind');
		$form->set_action('wnd_bind_email');
		$form->set_submit_button('保存');
		$form->build();

		return $form->html;
	}
}
