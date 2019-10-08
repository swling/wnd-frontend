<?php
namespace Wnd\Module;

use Wnd\View\Wnd_WP_Form;

/**
 *@since 2019.02.22
 *管理员手动增加用户余额
 */
class Wnd_Admin_Recharge_Form extends Wnd_Module {

	public static function build() {
		$form = new Wnd_WP_Form();
		$form->add_form_attr('id', 'admin-recharge-form');

		$form->add_html('<div class="field is-horizontal"><div class="field-body">');
		$form->add_text(
			array(
				'label'       => '用户',
				'name'        => 'user_field',
				'required'    => 'required',
				'placeholder' => '用户名、邮箱、注册手机',
			)
		);
		$form->add_number(
			array(
				'label'       => '金额',
				'name'        => 'total_amount',
				'required'    => 'required',
				'step'        => 0.1,
				'placeholder' => '充值金额（负数可扣款）',
			)
		);
		$form->add_html('</div></div>');
		$form->add_text(
			array(
				'name'        => 'remarks',
				'placeholder' => '备注（可选）',
			)
		);
		$form->set_action('wnd_admin_recharge');
		$form->set_submit_button('确认充值');
		$form->build();

		return $form->html;
	}
}
