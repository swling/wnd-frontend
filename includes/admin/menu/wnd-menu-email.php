<?php
namespace Wnd\Admin\Menu;

use Wnd\View\Wnd_Form_Option;

/**
 * 第三方 Email 推送配置表单
 * @since 0.9.58.3
 */
class Wnd_Menu_Email extends Wnd_Menus {

	// 子菜单基本属性
	protected $page_title = 'Email 配置';
	protected $menu_title = 'Email 配置';
	protected $menu_slug  = 'wnd-frontend-Email';

	protected function build_form_json(Wnd_Form_Option $form): string{
		$form->add_email(
			[
				'name'        => 'aliyun_dm_account',
				'label'       => '阿里云DM发信地址',
				'required'    => false,
				'placeholder' => '阿里云邮件推送发件地址',
				'help'        => ['text' => '配置后会接管默认 wp_mail 函数，且不支持附件。阿里云 DM 需要配置好回信地址（状态须验证通过）'],
			]
		);
		$form->set_submit_button('保存', 'is-danger');

		return $form->get_json();
	}
}
