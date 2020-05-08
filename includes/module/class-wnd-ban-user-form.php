<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Form_WP;

/**
 *@since 2020.04.30 封禁账户操作表单
 */
class Wnd_Ban_User_Form extends Wnd_Module {

	public static function build($user_id = 0) {
		if (!$user_id) {
			return static::build_error_message(__('ID无效', 'wnd'));
		}

		if (!wnd_is_manager()) {
			return static::build_error_message(__('权限不足', 'wnd'));
		}

		$form = new Wnd_Form_WP();
		$form->set_form_title(__('封禁用户', 'wnd') . ' : ' . get_userdata($user_id)->display_name, true);
		$form->set_message(
			static::build_notification('当前状态：' . (wnd_get_user_meta($user_id, 'status') ?: 'ok'))
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
				'class'    => 'is-checkradio is-danger',
			]
		);
		$form->add_html('</div>');
		$form->add_hidden('user_id', $user_id);
		$form->set_action('wnd_ban_user');
		$form->set_submit_button(__('确认', 'wnd'));
		$form->build();

		return $form->html;
	}
}
