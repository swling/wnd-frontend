<?php
namespace Wnd\Module;

use Exception;
use Wnd\View\Wnd_Form_User;

/**
 *@since 2019.01.13 登录框
 *@since 2019.03.10 Wnd_Form_WP
 */
class Wnd_Login_Form extends Wnd_Module_Form {

	protected static function configure_form(): object {
		if (is_user_logged_in()) {
			throw new Exception(__('已登录', 'wnd'));
		}

		/**
		 *移除本插件用户中心相关调用参数
		 *@since 2020.07.03
		 */
		$redirect_to = wnd_doing_ajax() ? false : remove_query_arg(['do', 'type', 'tab', 'wrap'], $_SERVER['HTTP_REFERER'] ?? false);

		$form = new Wnd_Form_User();
		$form->set_form_title('<span class="icon"><i class="fa fa-user"></i></span>&nbsp;' . __('登录', 'wnd'), true);
		$form->add_user_login('', __('用户名、手机、邮箱', 'wnd'), true);
		$form->add_user_password('', __('密码', 'wnd'));
		$form->add_checkbox(
			[
				'name'    => 'remember',
				'options' => [__('记住我', 'wnd') => '1'],
				'checked' => '1',
			]
		);
		$form->add_hidden('redirect_to', $redirect_to);
		$form->set_route('action', 'wnd_login');
		$form->set_submit_button(__('登录', 'wnd'));
		$form->set_filter(__CLASS__);
		return $form;
	}
}
