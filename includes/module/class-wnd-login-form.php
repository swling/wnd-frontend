<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Form_User;

/**
 *@since 2019.01.13 登录框
 *@since 2019.03.10 Wnd_Form_WP
 */
class Wnd_Login_Form extends Wnd_Module {

	public static function build() {
		// 已登录
		if (is_user_logged_in()) {
			return self::build_error_massage('已登录');
		}

		$form = new Wnd_Form_User();
		$form->add_form_attr('class', 'user-form');
		$form->set_form_title('<span class="icon"><i class="fa fa-user"></i></span>登录', true);
		$form->add_user_login();
		$form->add_user_password();
		$form->add_checkbox(
			array(
				'name'    => 'remember',
				'value'   => 1,
				'label'   => '保持登录',
				'checked' => '1', //default checked value
			)
		);
		$form->add_hidden('redirect_to', $_SERVER['HTTP_REFERER'] ?? home_url());
		// 与该表单数据匹配的后端处理函数
		$form->set_action('wnd_login');
		$form->set_submit_button('登录');
		// 以当前函数名设置filter hook
		$form->set_filter(__CLASS__);
		// 构造表单
		$form->build();

		// 输出表单
		return $form->html;
	}
}
