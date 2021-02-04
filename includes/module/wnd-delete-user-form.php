<?php
namespace Wnd\Module;

use Exception;
use Wnd\View\Wnd_Form_WP;

/**
 *@since 2020.04.30 删除账户
 */
class Wnd_Delete_User_Form extends Wnd_Module_Root {

	protected $type = 'form';

	protected function structure($args = []): array{
		if (!$args['user_id']) {
			throw new Exception(__('ID无效', 'wnd'));
		}

		$form = new Wnd_Form_WP();
		$form->set_form_title(__('删除用户', 'wnd') . ' : ' . get_userdata($args['user_id'])->display_name, true);
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
		$form->add_hidden('user_id', $args['user_id']);
		$form->set_route('action', 'wnd_delete_user');
		$form->set_submit_button(__('确认删除', 'wnd'));
		return $form->get_structure();
	}
}
