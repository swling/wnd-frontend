<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Form_User;

/**
 *@since 2019.01.13 登录框
 *@since 2019.03.10 Wnd_Form_WP
 */
class Wnd_Login_Form extends Wnd_Module {

	public static function build() {
		if (is_user_logged_in()) {
			return self::build_error_message('已登录');
		}

		$form = new Wnd_Form_User();
		$form->add_form_attr('class', 'user-form');
		$form->set_form_title('<span class="icon"><i class="fa fa-user"></i></span>登录', true);
		$form->add_user_login();
		$form->add_user_password();
		$form->add_checkbox(
			[
				'name'    => 'remember',
				'options' => ['保持登录' => '1'],
				'checked' => '1',
			]
		);
		$form->add_hidden('redirect_to', $_SERVER['HTTP_REFERER'] ?? home_url());
		$form->set_action('wnd_login');
		$form->set_submit_button(__('登录', 'wnd-frontend'));
		$form->set_filter(__CLASS__);
		$form->build();

		return $form->html;
	}
}
