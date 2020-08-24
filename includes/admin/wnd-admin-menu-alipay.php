<?php
namespace Wnd\Admin;

use Wnd\View\Wnd_Form_Option;

/**
 * 短信配置表单
 * @since 0.8.62
 */
class Wnd_Admin_Menu_Alipay extends Wnd_Admin_Menus {

	// 子菜单基本属性
	protected $page_title = '支付宝配置';
	protected $menu_title = '支付宝配置';
	protected $menu_slug  = 'wnd-frontend-alipay';

	/**
	 *构造表单
	 */
	public function build_page() {
		$form = new Wnd_Form_Option($this->option_name, $this->append);

		$form->add_radio(
			[
				'name'    => 'alipay_qrcode',
				'options' => ['常规接口' => 0, '当面付接口' => 1],
				'label'   => '是否为当面付接口',
				'class'   => 'is-checkradio is-danger',
			]
		);

		$form->add_radio(
			[
				'name'    => 'alipay_sandbox',
				'options' => ['生产环境' => 0, '沙箱调试' => 1],
				'label'   => '沙箱调试',
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

		$form->set_submit_button('保存', 'is-danger');
		$form->build();

		echo $form->html;
	}
}
