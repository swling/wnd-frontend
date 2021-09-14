<?php
namespace Wnd\Module;

use Exception;
use Wnd\View\Wnd_Form_WP;

/**
 * @since 2020.04.30 账户状态操作表单
 */
class Wnd_Account_Status_Form extends Wnd_Module_Form {

	protected static function configure_form(array $args = []): object {
		if (!$args['user_id']) {
			throw new Exception(__('ID无效', 'wnd'));
		}

		$current_status = get_user_meta($args['user_id'], 'status', true) ?: 'ok';
		$form           = new Wnd_Form_WP();
		$form->set_form_title(__('封禁用户', 'wnd') . ' : ' . get_userdata($args['user_id'])->display_name, true);
		$form->set_message(
			wnd_notification(__('当前状态：', 'wnd') . $current_status)
		);
		$form->add_html('<div class="field is-grouped is-grouped-centered">');
		$form->add_radio(
			[
				'name'     => 'status',
				'options'  => [
					__('封禁用户', 'wnd') => 'banned',
					__('取消封禁', 'wnd') => 'ok',
				],
				'required' => true,
				'checked'  => $current_status,
				'class'    => 'is-checkradio is-danger',
			]
		);
		$form->add_html('</div>');
		$form->add_hidden('user_id', $args['user_id']);
		$form->set_route('action', 'wnd_update_account_status');
		$form->set_submit_button(__('确认', 'wnd'));
		return $form;
	}
}
