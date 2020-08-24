<?php
namespace Wnd\Admin;

use Wnd\View\Wnd_Form_Option;

/**
 * 短信配置表单
 * @since 0.8.62
 */
class Wnd_Admin_Menu_Sms extends Wnd_Admin_Menus {

	// 子菜单基本属性
	protected $page_title = '短信配置';
	protected $menu_title = '短信配置';
	protected $menu_slug  = 'wnd-frontend-sms';

	/**
	 *构造表单
	 */
	public function build_page() {
		$form = new Wnd_Form_Option($this->option_name, $this->append);

		$form->add_radio(
			[
				'name'    => 'disable_email_reg',
				'options' => ['允许邮箱注册' => 0, '强制手机注册' => 1],
				'label'   => '禁止邮箱注册',
				'class'   => 'is-checkradio is-danger',
			]
		);

		$form->add_radio(
			[
				'name'    => 'disable_user_login',
				'options' => ['开启用户名' => 0, '禁止用户名' => 1],
				'label'   => '禁止用户名',
				'class'   => 'is-checkradio is-danger',
			]
		);

		$form->add_radio(
			[
				'name'    => 'enable_sms',
				'options' => ['关闭短信' => 0, '开启短信' => 1],
				'label'   => '启用短信功能',
				'class'   => 'is-checkradio is-danger',
			]
		);

		$form->add_radio(
			[
				'name'    => 'sms_sp',
				'options' => ['腾讯云' => 'tx', '阿里云' => 'ali'],
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
				'label'       => '短信APP ID',
				'required'    => false,
				'placeholder' => '短信APP ID',
			]
		);

		$form->add_text(
			[
				'name'        => 'sms_appkey',
				'label'       => '短信APP Key',
				'required'    => false,
				'placeholder' => '短信APP Key',
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
				'label'       => '注册短信模板',
				'required'    => false,
				'placeholder' => '注册短信模板',
			]
		);

		$form->add_text(
			[
				'name'        => 'sms_template_v',
				'label'       => '变更/校验 短信模板',
				'required'    => false,
				'placeholder' => '变更/校验 短信模板',
			]
		);

		$form->set_submit_button('保存', 'is-danger');
		$form->build();

		echo $form->html;
	}
}
