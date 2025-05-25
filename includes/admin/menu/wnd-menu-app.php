<?php
namespace Wnd\Admin\Menu;

use Wnd\View\Wnd_Form_Option;

/**
 * 第三方 APP 配置表单
 * @since 0.9.56.6
 */
class Wnd_Menu_App extends Wnd_Menus {

	// 子菜单基本属性
	protected $page_title = 'App 配置';
	protected $menu_title = 'App 配置';
	protected $menu_slug  = 'wnd-frontend-app';

	protected function build_form_json(Wnd_Form_Option $form): string{
		$form->add_textarea(
			[
				'name'        => 'wechat_app',
				'label'       => '微信密匙对',
				'required'    => false,
				'placeholder' => '基于本插件开发微信应用时，需填写微信应用 AppId 及 Secret 密匙对，同一个站点可能对应多个微信应用，故需要按 Json 数组格式填写。',
				'help'        => ['text' => '同一个站点可对应多个微信应用，参考格式：{"Appid1":"secret1", "Appid2":"secret2"} （对应 Endpoint\\Wnd_Issue_Token_WeChat）'],
			]
		);
		$form->set_submit_button('保存', 'is-danger');

		return $form->get_json();
	}
}
