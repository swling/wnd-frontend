<?php
namespace Wnd\Admin;

use Wnd\View\Wnd_Form_Option;

/**
 * 支付配置表单
 * @since 0.8.62
 */
class Wnd_Menu_Payment extends Wnd_Menus {

	// 子菜单基本属性
	protected $page_title = '支付接口配置';
	protected $menu_title = '支付接口配置';
	protected $menu_slug  = 'wnd-frontend-payment';

	/**
	 * 构造表单
	 */
	protected function build_form(Wnd_Form_Option $form): string{

		$form->add_html('<h2 class="title">沙箱测试</h2>');

		$form->add_radio(
			[
				'name'    => 'payment_sandbox',
				'options' => ['生产环境' => 0, '沙箱调试' => 1],
				'label'   => '沙箱调试',
				'class'   => 'is-checkradio is-danger',
			]
		);

		$form->add_html('<h2 class="title">支付宝</h2>');

		$form->add_radio(
			[
				'name'    => 'alipay_qrcode',
				'options' => ['常规接口' => 0, '当面付接口' => 1],
				'label'   => '是否为当面付接口',
				'class'   => 'is-checkradio is-danger',
			]
		);

		$form->add_text(
			[
				'name'        => 'alipay_appid',
				'label'       => '支付宝APP ID',
				'required'    => false,
				'placeholder' => '支付宝APP ID',
			]
		);

		$form->add_textarea(
			[
				'name'        => 'alipay_app_private_key',
				'label'       => '支付宝应用私钥',
				'required'    => false,
				'placeholder' => '应用私钥，由开发者自己生成',
			]
		);

		$form->add_textarea(
			[
				'name'        => 'alipay_public_key',
				'label'       => '支付宝公钥',
				'required'    => false,
				'placeholder' => '支付宝公钥，开发者生成公钥后上传至支付宝，再由支付宝生成',
			]
		);

		// Paypal
		$form->add_html('<h2 class="title">PayPal</h2>');

		$form->add_text(
			[
				'name'        => 'paypal_clientid',
				'label'       => 'PayPal REST API Client ID',
				'required'    => false,
				'placeholder' => 'PayPal Client ID',
				'help'        => ['text' => '<a href="https://developer.paypal.com/developer/applications" target="_blank">创建 REST API APP 并获取 Client ID</a>'],
			]
		);

		$form->add_text(
			[
				'name'        => 'paypal_secret',
				'label'       => 'PayPal  REST API Secret',
				'required'    => false,
				'placeholder' => 'PayPal Secret',
				'help'        => ['text' => '<a href="https://developer.paypal.com/developer/applications" target="_blank">创建 REST API APP 并获取 Secret</a>'],
			]
		);

		$form->set_submit_button('保存', 'is-danger');
		$form->build();

		return $form->html;
	}
}
