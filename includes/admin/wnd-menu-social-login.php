<?php
namespace Wnd\Admin;

use Wnd\View\Wnd_Form_Option;

/**
 * 社交登录配置表单
 * @since 0.8.76
 */
class Wnd_Menu_Social_Login extends Wnd_Menus {

	// 子菜单基本属性
	protected $page_title = '社交登录';
	protected $menu_title = '社交登录';
	protected $menu_slug  = 'wnd-frontend-social-login';

	/**
	 *构造表单
	 */
	public function build_form() {
		$form = new Wnd_Form_Option($this->option_name, $this->append);

		$form->add_html('<h3>QQ 登录</h3>');

		$form->add_text(
			[
				'name'     => 'qq_appid',
				'label'    => 'QQ 登录APP ID',
				'required' => false,
			]
		);

		$form->add_text(
			[
				'name'     => 'qq_appkey',
				'label'    => 'QQ 登录APP KEY',
				'required' => false,
			]
		);

		$form->add_html('<h3>Google 登录</h3>');

		$form->add_text(
			[
				'name'     => 'google_appid',
				'label'    => 'Google 登录APP ID',
				'required' => false,
			]
		);

		$form->add_text(
			[
				'name'     => 'google_appkey',
				'label'    => 'Google 登录APP KEY',
				'required' => false,
			]
		);

		$form->set_submit_button('保存', 'is-danger');
		$form->build();

		return $form->html;
	}
}
