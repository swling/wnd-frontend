<?php
namespace Wnd\Module;

use Wnd\Model\Wnd_Payment;
use Wnd\View\Wnd_Form_WP;

/**
 *@since 2020.06.30
 *在线支付订单表单
 *匿名支付订单默认启用人机验证
 */
class Wnd_Order_Payment_Form extends Wnd_Module {

	protected static function build($post_id = 0) {
		$use_id = get_current_user_id();
		$form   = new Wnd_Form_WP(true, !$use_id);
		$form->set_form_title(get_the_title($post_id), true);
		if (!$use_id) {
			$form->add_html(static::build_notification(__('您当前尚未登录，匿名订单仅24小时有效，请悉知！', 'wnd'), true));
		}
		$form->add_html('<div class="has-text-centered field">');
		$form->add_html('<p>' . __('本次消费：¥ ', 'wnd') . '<b>' . wnd_get_post_price($post_id) . '</b></p>');
		$form->add_radio(
			[
				'name'     => 'payment_gateway',
				'options'  => Wnd_Payment::get_gateway_options(),
				'required' => 'required',
				'checked'  => Wnd_Payment::get_default_gateway(),
				'class'    => 'is-checkradio is-danger',
			]
		);
		$form->add_html('</div>');
		$form->set_action('wnd_do_pay');
		$form->add_hidden('post_id', $post_id);
		$form->set_submit_button(__('确定', 'wnd'));
		$form->build();

		return $form->html;
	}
}
