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
	protected function build_form_json(Wnd_Form_Option $form): string{

		$form->add_html('<h2 class="title">沙箱测试</h2>');
		$form->add_radio(
			[
				'name'    => 'payment_sandbox',
				'options' => ['生产环境' => '', '沙箱调试' => 1],
				'label'   => '沙箱调试',
				'class'   => 'is-checkradio is-danger',
			]
		);

		$form->add_html('<h2 class="title">支付宝</h2>');
		$form->add_html('<p>本插件统一采用【公钥证书】模式，证书工具下载: https://opendocs.alipay.com/common/02kipl</p>');
		$form->add_radio(
			[
				'name'    => 'alipay_qrcode',
				'options' => ['常规接口' => '', '当面付接口' => 1],
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

		$form->add_text(
			[
				'name'        => 'alipay_root_cert_sn',
				'label'       => '支付宝根证书序列号',
				'required'    => false,
				'placeholder' => '支付宝根证书序列号',
				'help'        => ['text' => '获取方法：AlipayCertClient::getRootCertSNFromContent($certContent); 【对应证书：alipayRootCert.crt】'],
			]
		);

		$form->add_text(
			[
				'name'        => 'alipay_app_cert_sn',
				'label'       => '应用公钥证书序列号',
				'required'    => false,
				'placeholder' => '应用公钥证书序列号',
				'help'        => ['text' => '获取方法：AlipayCertClient::getCertSNFromContent($certContent); 对应证书；【appCertPublicKey_{xxx}.crt】'],
			]
		);

		$form->add_textarea(
			[
				'name'        => 'alipay_app_private_key',
				'label'       => '应用私钥',
				'required'    => false,
				'placeholder' => '应用私钥，由秘钥工具生成位于：CSR/xxx.com_私钥',
			]
		);

		$form->add_textarea(
			[
				'name'        => 'alipay_public_key',
				'label'       => '支付宝公钥',
				'required'    => false,
				'placeholder' => '支付宝公钥，开发者工具生成 .csr 文件后上传至支付宝，再由支付宝生成公钥的证书',
				'help'        => ['text' => '获取方法：AlipayCertClient::getPublicKeyFromContent($certContent); 对应证书：【alipayCertPublicKey_RSA2.crt】'],
			]
		);

		// 微信支付
		$form->add_html('<h2 class="title">微信支付</h2>');
		$form->add_text(
			[
				'name'        => 'wechat_mchid',
				'label'       => '商户ID',
				'required'    => false,
				'placeholder' => '微信支付商户号',
				'help'        => ['text' => 'https://pay.weixin.qq.com/index.php/core/account/info'],
			]
		);
		$form->add_text(
			[
				'name'        => 'wechat_appid',
				'label'       => '默认AppID',
				'required'    => false,
				'placeholder' => '同一商户可绑定多个服务号、小程序、企业微信 AppID，此处填写站内 web 支付对应的 App id',
				'help'        => ['text' => 'https://pay.weixin.qq.com/index.php/extend/merchant_appid/mapay_platform/account_manage'],
			]
		);
		$form->add_text(
			[
				'name'        => 'wechat_apikey',
				'label'       => 'APIv3密钥',
				'required'    => false,
				'placeholder' => 'APIv3密钥',
				'help'        => ['text' => 'https://pay.weixin.qq.com/index.php/core/cert/api_cert#/'],
			]
		);
		$form->add_text(
			[
				'name'        => 'wechat_apicert_sn',
				'label'       => 'API证书序列号',
				'required'    => false,
				'placeholder' => 'API证书序列号',
				'help'        => ['text' => 'https://pay.weixin.qq.com/index.php/core/cert/api_cert#/api-cert-manage'],
			]
		);

		$form->add_textarea(
			[
				'name'        => 'wechat_private_key',
				'label'       => '商户私钥',
				'required'    => false,
				'placeholder' => 'API证书中 apiclient_key.pem 的文本',
				'help'        => ['text' => '编辑器打开证书【apiclient_key.pem】-----BEGIN RSA PRIVATE KEY----- [复制粘贴此部分] -----END RSA PRIVATE KEY-----'],
			]
		);

		// Paypal
		$form->add_html('<h2 class="title">PayPal</h2>');
		$form->add_text(
			[
				'name'        => 'paypal_clientid',
				'label'       => 'REST API Client ID',
				'required'    => false,
				'placeholder' => 'PayPal Client ID',
			]
		);

		$form->add_text(
			[
				'name'        => 'paypal_secret',
				'label'       => 'REST API Secret',
				'required'    => false,
				'placeholder' => 'PayPal Secret',
			]
		);

		$form->set_submit_button('保存', 'is-danger');

		return $form->get_json();
	}
}
