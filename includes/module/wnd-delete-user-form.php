<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Form_WP;

/**
 *@since 2020.04.30 删除账户
 */
class Wnd_Delete_User_Form extends Wnd_Module_Root {

	protected static function build(): string {
		if (!static::$args['user_id']) {
			return static::build_error_message(__('ID无效', 'wnd'));
		}

		$form = new Wnd_Form_WP();
		$form->set_form_title(__('删除用户', 'wnd') . ' : ' . get_userdata(static::$args['user_id'])->display_name, true);
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
		$form->add_hidden('user_id', static::$args['user_id']);
		$form->set_action('wnd_delete_user');
		$form->set_submit_button(__('确认删除', 'wnd'));
		$form->build();

		return $form->html;
	}
}
