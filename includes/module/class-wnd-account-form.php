<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Form_User;

/**
 *@since 2019.01.23 用户更新账户表单
 */
class Wnd_Account_Form extends Wnd_Module {

	public static function build() {
		if (!is_user_logged_in()) {
			return self::build_error_message(__('请登录', 'wnd'));
		}
		if (!wp_get_current_user()->data->user_email) {
			$html = '<div class="has-text-centered content">';
			$html .= '<button class="button is-' . wnd_get_option('wnd', 'wnd_primary_color') . '" onclick="wnd_ajax_modal(\'wnd_bind_email_form\')">' . __('请绑定邮箱') . '</button>';
			$html .= '</div>';
			return $html;
		}

		$form = new Wnd_Form_User();
		$form->add_form_attr('class', 'user-form');
		$form->add_user_password(__('密码', 'wnd'), __('当前密码', 'wnd'));
		$form->add_user_new_password(__('新密码', 'wnd'), __('新密码', 'wnd'));
		$form->add_user_new_password_repeat(__('确认新密码', 'wnd'), __('确认新密码', 'wnd'));
		$form->set_action('wnd_update_account');
		$form->set_submit_button(__('保存', 'wnd'));
		$form->set_filter(__CLASS__);
		$form->build();

		/**
		 *@since 2019.09.19
		 *绑定邮箱或手机
		 */
		$html = '<a onclick="wnd_ajax_modal(\'wnd_bind_email_form\')">' . __('邮箱设置', 'wnd') . '</a> | ';
		$html .= wnd_get_option('wnd', 'wnd_enable_sms') ? '<a onclick="wnd_ajax_modal(\'wnd_bind_phone_form\')">' . __('手机设置', 'wnd') . '</a> | ' : '';
		$html .= '<a onclick="wnd_ajax_modal(\'wnd_user_center\',\'do=reset_password\')">' . __('重置密码', 'wnd') . '</a>';

		return $form->html . wnd_message($html, 'is-' . wnd_get_option('wnd', 'wnd_second_color'), true);
	}
}
