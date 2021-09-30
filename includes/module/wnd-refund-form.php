<?php
namespace Wnd\Module;

use Exception;
use Wnd\View\Wnd_Form_WP;

/**
 * @since 2020.06.09 退款表单
 */
class Wnd_Refund_Form extends Wnd_Module_Form {

	protected static function configure_form(array $args = []): object {
		if (!$args['transaction_id']) {
			throw new Exception(__('ID无效', 'wnd'));
		}

		$form = new Wnd_Form_WP();
		$form->set_form_title(__('退款', 'wnd'), true);
		$form->add_number(
			[
				'name'        => 'refund_amount',
				'icon_left'   => '<i class="fas fa-yen-sign"></i>',
				'placeholder' => __('留空为全额退款', 'wnd'),
				'required'    => false,
				'step'        => '0.01',
				'min'         => '0',
			]
		);
		$form->add_html('<div class="field is-grouped is-grouped-centered">');
		$form->add_checkbox(
			[
				'name'     => 'confirm',
				'options'  => [
					__('确认', 'wnd') => 'confirm',
				],
				'required' => true,
				'class'    => 'is-switch is-danger',
			]
		);
		$form->add_html('</div>');
		$form->add_hidden('transaction_id', $args['transaction_id']);
		$form->set_route('action', 'wnd_refund');
		$form->set_submit_button(__('确认退款', 'wnd'));
		return $form;
	}

	protected static function check($args) {
		if (!wnd_is_manager()) {
			throw new Exception(__('权限不足', 'wnd'));
		}
	}
}
