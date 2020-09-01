<?php
namespace Wnd\Admin;

use Wnd\View\Wnd_Form_Option;

/**
 * 短信配置表单
 * @since 0.8.62
 */
class Wnd_Admin_Menu_Transaction extends Wnd_Admin_Menus {

	// 子菜单基本属性
	protected $page_title = '支付及订单设置';
	protected $menu_title = '支付及订单设置';
	protected $menu_slug  = 'wnd-frontend-transaction';

	/**
	 *构造表单
	 */
	public function build_page() {
		$form = new Wnd_Form_Option($this->option_name, $this->append);

		$form->add_number(
			[
				'name'        => 'commission_rate',
				'placeholder' => '当用户发布的付费内容产生消费时，作者获得的佣金比例（0.00 ~ 1.00）',
				'label'       => '作者佣金设置',
				'min'         => 1,
				'step'        => 1,
			]
		);

		$form->add_radio(
			[
				'name'    => 'enable_anon_order',
				'options' => ['禁用' => 0, '启用' => 1],
				'label'   => '匿名支付订单',
				'class'   => 'is-checkradio is-danger',
			]
		);

		$form->add_url(
			[
				'name'        => 'pay_return_url',
				'label'       => '充值返回',
				'required'    => false,
				'placeholder' => '用户充值后跳转地址',
			]
		);

		$form->set_submit_button('保存', 'is-danger');
		$form->build();

		echo $form->html;
	}
}