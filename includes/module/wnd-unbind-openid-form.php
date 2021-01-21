<?php
namespace Wnd\Module;

use Wnd\Model\Wnd_User;
use Wnd\View\Wnd_Form_User;

/**
 *@since 0.9.4
 *解除第三方账户绑定（不含手机及邮箱）
 */
class Wnd_Unbind_Openid_Form extends Wnd_Module_User {

	protected static function build(): string{
		// 获取当前用户绑定账户数据
		$current_user = wp_get_current_user();
		$wnd_user     = (array) Wnd_User::get_wnd_user($current_user->ID);

		// 排除用户ID，邮箱，手机
		unset($wnd_user['user_id'], $wnd_user['email'], $wnd_user['phone']);

		// 组合表单选项数据
		$type_options = [];
		foreach ($wnd_user as $key => $value) {
			$type_options[strtoupper($key)] = $key;
		}
		unset($key, $value);
		if (!$type_options) {
			return static::build_error_notification(__('当前账户未绑定第三方账号', 'wnd'), true);
		}

		// 构建解绑操作提交表单
		$form = new Wnd_Form_User();
		$form->set_form_title('<span class="icon"><i class="fas fa-minus-circle"></i></span>&nbsp' . __('解除绑定', 'wnd'), true);

		$form->add_html('<div class="field is-grouped is-grouped-centered">');
		$form->add_radio(
			[
				'name'     => 'type',
				'required' => true,
				'options'  => $type_options,
				'class'    => 'is-checkradio is-' . wnd_get_config('primary_color'),
			]
		);
		$form->add_html('</div>');

		$form->add_user_password(__('密码', 'wnd'), __('密码', 'wnd'));

		$form->set_route('action', 'wnd_unbind_openid');
		$form->set_submit_button(__('解除绑定', 'wnd'));
		$form->build();

		return $form->html;
	}
}
