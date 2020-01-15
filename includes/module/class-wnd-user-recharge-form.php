<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Form;

/**
 *@since 2019.01.21 充值表单
 */
class Wnd_User_Recharge_Form extends Wnd_Module {

	public static function build() {
		if (!wnd_get_option('wnd', 'wnd_alipay_appid')) {
			return __('未设置支付接口', 'wnd');
		}

		$form = new Wnd_Form;
		$form->add_html('<div class="has-text-centered">');
		$form->add_radio(
			[
				'name'     => 'total_amount',
				'options'  => ['0.01' => '0.01', '10' => '10', '100' => '100', '200' => '200', '500' => '500'],
				'required' => 'required',
				'class'    => 'is-checkradio is-danger',
			]
		);
		$form->add_html('<img src="https://t.alipayobjects.com/images/T1HHFgXXVeXXXXXXXX.png">');
		$form->add_html('</div>');
		$form->set_action(wnd_get_do_url(), 'GET');
		$form->add_hidden('_wpnonce', wnd_create_nonce('payment'));
		$form->add_hidden('action', 'payment');
		$form->set_submit_button(__('充值', 'wnd'), 'is-' . wnd_get_option('wnd', 'wnd_primary_color'));
		$form->build();

		return $form->html;
	}
}
