<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Form_WP;

/**
 *@since 2019.02.22
 *管理员手动增加用户余额
 */
class Wnd_Admin_Recharge_Form extends Wnd_Module_Form {

	protected static function configure_form(): object{
		$form = new Wnd_Form_WP();
		$form->add_form_attr('id', 'admin-recharge-form');
		$form->add_html('<div class="field is-horizontal"><div class="field-body">');
		$form->add_text(
			[
				'label'       => __('用户', 'wnd'),
				'name'        => 'user_field',
				'required'    => 'required',
				'placeholder' => __('用户名、邮箱、注册手机', 'wnd'),
			]
		);
		$form->add_number(
			[
				'label'       => '金额',
				'name'        => 'total_amount',
				'required'    => 'required',
				'step'        => 0.01,
				'placeholder' => __('充值金额（负数可扣款）', 'wnd'),
			]
		);
		$form->add_html('</div></div>');
		$form->add_text(
			[
				'name'        => 'remarks',
				'placeholder' => __('备注（可选）', 'wnd'),
			]
		);
		$form->set_route('action', 'wnd_admin_recharge');
		$form->set_submit_button(__('确认充值', 'wnd'));
		return $form;
	}
}
