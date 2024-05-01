<?php
namespace Wnd\Admin;

use Wnd\View\Wnd_Form_Option;

/**
 * 营销推广配置表单
 * @since 0.9.70
 */
class Wnd_Menu_Affiliate extends Wnd_Menus {

	// 子菜单基本属性
	protected $page_title = '营销推广';
	protected $menu_title = '营销推广';
	protected $menu_slug  = 'wnd-frontend-affiliate';

	/**
	 * 构造表单
	 */
	protected function build_form_json(Wnd_Form_Option $form): string {

		$form->add_number(
			[
				'name'        => 'reg_commission',
				'placeholder' => '推广链接成功注册后所得佣金',
				'label'       => '推广注册佣金',
				'min'         => 0,
				'step'        => 0.01,
			]
		);

		$form->add_number(
			[
				'name'        => 'reg_bonus',
				'placeholder' => '新注册用户奖励金额',
				'label'       => '新用户激励',
				'min'         => 0,
				'step'        => 0.01,
			]
		);
		$form->set_submit_button('保存', 'is-danger');

		return $form->get_json();
	}
}
