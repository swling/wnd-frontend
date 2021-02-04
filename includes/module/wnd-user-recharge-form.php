<?php
namespace Wnd\Module;

use Wnd\Model\Wnd_Payment_Getway;
use Wnd\Model\Wnd_Recharge;
use Wnd\View\Wnd_Form_WP;

/**
 *@since 2019.01.21 充值表单
 */
class Wnd_User_Recharge_Form extends Wnd_Module_User {

	protected $type = 'form';

	protected function structure(): array{
		$form = new Wnd_Form_WP();
		$form->add_html('<div class="has-text-centered field">');
		$form->add_radio(
			[
				'name'     => 'total_amount',
				'options'  => Wnd_Recharge::get_recharge_amount_options(),
				'required' => 'required',
				'class'    => 'is-checkradio is-danger',
			]
		);
		$form->add_radio(
			[
				'name'     => 'payment_gateway',
				'options'  => Wnd_Payment_Getway::get_gateway_options(),
				'required' => 'required',
				'checked'  => Wnd_Payment_Getway::get_default_gateway(),
				'class'    => 'is-checkradio is-danger',
			]
		);
		$form->add_html('</div>');
		$form->set_route('action', 'wnd_do_pay');
		$form->set_submit_button(__('充值', 'wnd'));
		return $form->get_structure();
	}
}
