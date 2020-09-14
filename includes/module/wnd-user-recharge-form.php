<?php
namespace Wnd\Module;

use Wnd\Model\Wnd_Payment;
use Wnd\View\Wnd_Form_WP;

/**
 *@since 2019.01.21 充值表单
 */
class Wnd_User_Recharge_Form extends Wnd_Module_User {

	protected static function build(): string{
		$form = new Wnd_Form_WP();
		$form->add_html('<div class="has-text-centered field">');
		$form->add_radio(
			[
				'name'     => 'total_amount',
				'options'  => Wnd_payment::get_recharge_amount_options(),
				'required' => 'required',
				'class'    => 'is-checkradio is-danger',
			]
		);
		$form->add_radio(
			[
				'name'     => 'payment_gateway',
				'options'  => Wnd_Payment::get_gateway_options(),
				'required' => 'required',
				'checked'  => Wnd_Payment::get_default_gateway(),
				'class'    => 'is-checkradio is-danger',
			]
		);
		// $form->add_html('<img src="https://t.alipayobjects.com/images/T1HHFgXXVeXXXXXXXX.png">');
		$form->add_html('</div>');
		$form->set_action('wnd_do_pay');
		$form->set_submit_button(__('充值', 'wnd'));
		$form->build();

		return $form->html;
	}
}
