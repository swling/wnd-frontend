<?php
namespace Wnd\Admin;

use Wnd\View\Wnd_Form_Option;

/**
 * 短信配置表单
 * @since 0.8.62
 */
class Wnd_Menu_Captcha extends Wnd_Menus {

	// 子菜单基本属性
	protected $page_title = '验证码配置';
	protected $menu_title = '验证码配置';
	protected $menu_slug  = 'wnd-frontend-captcha';

	/**
	 *构造表单
	 */
	public function build_form() {
		$form = new Wnd_Form_Option($this->option_name, $this->append);

		$form->add_radio(
			[
				'name'    => 'captcha_service',
				'options' => ['关闭' => '', '腾讯云' => 'Qcloud'],
				'label'   => '验证码服务',
				'class'   => 'is-checkradio is-danger',
			]
		);

		$form->add_text(
			[
				'name'        => 'captcha_appid',
				'label'       => 'AppID / SiteKey',
				'required'    => false,
				'placeholder' => 'AppID / SiteKey',
			]
		);

		$form->add_text(
			[
				'name'        => 'captcha_appkey',
				'label'       => 'AppKey / SiteSecret',
				'required'    => false,
				'placeholder' => 'AppKey / SiteSecret',
			]
		);

		$form->set_submit_button('保存', 'is-danger');
		$form->build();

		return $form->html;
	}
}
