<?php
namespace Wnd\Admin;

use Wnd\View\Wnd_Form_Option;

/**
 * 第三方平台 AccessKey
 * @since 0.8.73
 */
class Wnd_Menu_Accesskey extends Wnd_Menus {

	// 子菜单基本属性
	protected $page_title = '云平台 Access Key';
	protected $menu_title = '云平台 Access Key';
	protected $menu_slug  = 'wnd-access-key';

	/**
	 *构造表单
	 */
	public function build_form() {
		$form = new Wnd_Form_Option($this->option_name, $this->append);
		$form->add_html('
			<h3>腾讯云 API 密钥管理</h3>
			<a href="https://console.cloud.tencent.com/capi" target="_blank">https://console.cloud.tencent.com/capi</a>
			'
		);
		$form->add_text(
			[
				'name'        => 'tencent_secretid',
				'label'       => '腾讯云 Secret ID',
				'required'    => false,
				'placeholder' => '腾讯云 Secret ID',
			]
		);

		$form->add_text(
			[
				'name'        => 'tencent_secretkey',
				'label'       => '腾讯云 Secret Key',
				'required'    => false,
				'placeholder' => '腾讯云 Secret Key',
			]
		);

		$form->add_html('
			<h3>阿里云 API 密钥管理</h3>
			<a href="https://ram.console.aliyun.com/manage/ak" target="_blank">https://ram.console.aliyun.com/manage/ak</a>
			'
		);
		$form->add_text(
			[
				'name'        => 'aliyun_secretid',
				'label'       => '阿里云 AccessKey ID',
				'required'    => false,
				'placeholder' => '阿里云 AccessKey ID',
			]
		);

		$form->add_text(
			[
				'name'        => 'aliyun_secretkey',
				'label'       => '阿里云 AccessKey Secret',
				'required'    => false,
				'placeholder' => '阿里云 AccessKey Secret',
			]
		);

		$form->add_html('
			<h3>百度云 API 密钥管理</h3>
			<a href="https://console.bce.baidu.com/iam/#/iam/accesslist" target="_blank">https://console.bce.baidu.com/iam/#/iam/accesslist</a>
			'
		);
		$form->add_text(
			[
				'name'        => 'baidu_secretid',
				'label'       => '百度云 AccessKey',
				'required'    => false,
				'placeholder' => '百度云 AccessKey',
			]
		);

		$form->add_text(
			[
				'name'        => 'baidu_secretkey',
				'label'       => '百度云 SecretKey',
				'required'    => false,
				'placeholder' => '百度云 SecretKey',
			]
		);

		$form->set_submit_button('保存', 'is-danger');
		$form->build();

		return $form->html;
	}
}
