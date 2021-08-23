<?php
namespace Wnd\Module;

use Exception;
use Wnd\View\Wnd_Form_User;

/**
 * @since 2019.01.23 用户更新账户表单
 */
class Wnd_Account_Form extends Wnd_Module_Form {

	protected static function configure_form(): object{
		$user       = wp_get_current_user();
		$enable_sms = wnd_get_config('enable_sms');

		/**
		 * 如果当前账户为社交登录，账户设置前必须绑定邮箱或手机
		 */
		if (!$user->data->user_email and !wnd_get_user_phone($user->data->ID)) {
			$message = '<p>' . __('出于安全考虑，请绑定邮箱或手机') . '</p>';
			$message .= wnd_modal_button(__('绑定邮箱'), 'wnd_bind_email_form');

			if ($enable_sms) {
				$message .= '&nbsp;&nbsp;';
				$message .= wnd_modal_button(__('绑定手机'), 'wnd_bind_phone_form');
			}

			throw new Exception($message);
		}

		$form = new Wnd_Form_User(true);
		$form->add_text(['value' => $user->ID, 'label' => 'ID', 'disabled' => true]);
		$form->add_user_password(__('当前密码', 'wnd'), __('当前密码', 'wnd'));
		$form->add_user_new_password(__('新密码', 'wnd'), __('新密码', 'wnd'));
		$form->add_user_new_password_repeat(__('确认新密码', 'wnd'), __('确认新密码', 'wnd'));
		$form->set_route('action', 'wnd_update_account');
		$form->set_submit_button(__('保存', 'wnd'));
		$form->set_filter(__CLASS__);

		/**
		 * 绑定邮箱或手机
		 * @since 2019.09.19
		 */
		$html = '<div class="has-text-centered mt-3">';
		$html .= wnd_modal_link(__('邮箱设置', 'wnd'), 'wnd_bind_email_form') . ' | ';
		$html .= $enable_sms ? (wnd_modal_link(__('手机设置', 'wnd'), 'wnd_bind_phone_form') . ' | ') : '';
		$html .= wnd_modal_link(__('重置密码', 'wnd'), 'wnd_user_center', ['do' => 'reset_password']) . ' | ';
		$html .= wnd_modal_link(__('解除绑定', 'wnd'), 'wnd_unbind_openid_form');
		$html .= '</div>';

		$form->add_html($html);

		return $form;
	}
}
