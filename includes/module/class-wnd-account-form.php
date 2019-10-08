<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Form_User;

/**
 *@since 2019.01.23 用户更新账户表单
 */
class Wnd_Account_Form extends Wnd_Module {

	public static function build() {
		if (!is_user_logged_in()) {
			return '<script>wnd_alert_msg(\'请登录\')</script>';
		}
		if (!wp_get_current_user()->data->user_email) {
			$html = '<div class="has-text-centered content">';
			$html .= '<button class="button is-' . wnd_get_option('wnd', 'wnd_primary_color') . '" onclick="wnd_ajax_modal(\'wnd_bind_email_form\')">请绑定邮箱</button>';
			$html .= '</div>';
			return $html;
		}

		$form = new Wnd_Form_User();
		$form->add_form_attr('class', 'user-form');
		$form->add_user_password('当前密码');
		$form->add_user_new_password();
		$form->add_user_new_password_repeat();
		$form->set_action('wnd_update_account');
		$form->set_submit_button('保存');
		$form->set_filter(__CLASS__);
		$form->build();

		/**
		 *@since 2019.09.19
		 *绑定邮箱或手机
		 */
		$html = '<div class="message is-' . wnd_get_option('wnd', 'wnd_second_color') . '"><div class="message-body has-text-centered">';
		$html .= '<a onclick="wnd_ajax_modal(\'wnd_bind_email_form\')">邮箱设置</a> | ';
		$html .= 1 == wnd_get_option('wnd', 'wnd_enable_sms') ? '<a onclick="wnd_ajax_modal(\'wnd_bind_phone_form\')">手机设置</a> | ' : '';
		$html .= '<a onclick="wnd_ajax_modal(\'wnd_reset_password_form\')">重置密码</a>';
		$html .= '</div></div>';

		return $form->html . $html;
	}
}
