<?php
namespace Wnd\Admin;

use Wnd\View\Wnd_Form_Option;

/**
 * 短信配置表单
 * @since 0.8.62
 */
class Wnd_Menu_Sms extends Wnd_Menus {

	// 子菜单基本属性
	protected $page_title = '短信/注册配置';
	protected $menu_title = '短信/注册配置';
	protected $menu_slug  = 'wnd-frontend-sms';

	/**
	 * 构造表单
	 */
	protected function build_form_json(Wnd_Form_Option $form): string{

		$form->add_radio(
			[
				'name'    => 'disable_email_reg',
				'options' => ['允许邮箱注册' => '', '强制手机注册' => 1],
				'label'   => '禁止邮箱注册',
				'class'   => 'is-checkradio is-danger',
			]
		);

		$form->add_radio(
			[
				'name'    => 'disable_user_login',
				'options' => ['开启用户名' => '', '禁止用户名' => 1],
				'label'   => '禁止用户名',
				'class'   => 'is-checkradio is-danger',
			]
		);

		$form->add_radio(
			[
				'name'    => 'enable_sms',
				'options' => ['关闭短信' => '', '开启短信' => 1],
				'label'   => '启用短信功能',
				'class'   => 'is-checkradio is-danger',
			]
		);

		$form->add_radio(
			[
				'name'    => 'sms_sp',
				'options' => ['腾讯云' => 'Qcloud', '阿里云' => 'Aliyun'],
				'label'   => '选择短信服务商',
				'class'   => 'is-checkradio is-danger',
			]
		);

		$form->add_number(
			[
				'name'        => 'min_verification_interval',
				'placeholder' => '验证码发送间隔频率控制，单位：秒',
				'label'       => '间隔频率控制',
				'min'         => 10,
				'step'        => 1,
			]
		);

		$form->add_text(
			[
				'name'        => 'sms_appid',
				'label'       => '短信APP ID（仅腾讯云）',
				'required'    => false,
				'placeholder' => '短信APP ID（仅腾讯云）',
			]
		);

		$form->add_text(
			[
				'name'        => 'sms_sign',
				'label'       => '短信签名',
				'required'    => false,
				'placeholder' => '短信签名',
			]
		);

		$form->add_text(
			[
				'name'        => 'sms_template_r',
				'label'       => '注册短信模板 ID',
				'required'    => false,
				'placeholder' => '注册短信模板 ID',
			]
		);

		$form->add_text(
			[
				'name'        => 'sms_template_v',
				'label'       => '变更/校验 短信模板 ID',
				'required'    => false,
				'placeholder' => '变更/校验 短信模板 ID',
			]
		);

		$form->set_submit_button('保存', 'is-danger');

		return $form->get_json();
	}
}
