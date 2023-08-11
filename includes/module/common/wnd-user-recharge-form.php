<?php
namespace Wnd\Module\Common;

use Wnd\Getway\Wnd_Payment_Getway;
use Wnd\Model\Wnd_Recharge;
use Wnd\Module\Wnd_Module_Form;
use Wnd\View\Wnd_Form_WP;

/**
 * 充值表单
 *
 * @since 2019.01.21
 */
class Wnd_User_Recharge_Form extends Wnd_Module_Form {

	protected static function configure_form(array $args = []): object {
		$form = new Wnd_Form_WP();
		if (!is_user_logged_in()) {
			$form->set_message(__('匿名充值仅限当前浏览器24小时内有效，切勿大额充值！', 'wnd'), 'is-danger');
		}
		$form->add_html('<div class="has-text-centered field">');
		$form->add_radio(
			[
				'name'    => 'total_amount',
				'options' => Wnd_Recharge::get_recharge_amount_options(),
				'class'   => 'is-checkradio is-danger',
			]
		);
		$form->add_number(
			[
				'name'        => 'custom_total_amount',
				'placeholder' => '自定义金额',
				'min'         => 0.01,
				'step'        => 0.01,
				'value'       => $args['amount'] ?? '',
			]
		);
		$form->add_radio(
			[
				'name'     => 'payment_gateway',
				'options'  => Wnd_Payment_Getway::get_gateway_options(),
				'required' => true,
				'checked'  => Wnd_Payment_Getway::get_default_gateway(),
				'class'    => 'is-checkradio is-danger',
			]
		);
		$form->add_html('</div>');
		$form->add_hidden('type', 'recharge');
		$form->set_route('action', 'common/wnd_do_payment');
		$form->set_submit_button(__('充值', 'wnd'));

		return $form;
	}
}
