<?php

namespace Wnd\Admin\Menu;

use Wnd\View\Wnd_Form_Option;

/**
 * 优化选项
 * @since 0.9.86
 */
class Wnd_Menu_Optimize extends Wnd_Menus {

	// 子菜单基本属性
	protected $page_title = '优化选项';
	protected $menu_title = '优化选项';
	protected $menu_slug  = 'wnd-frontend-optimize';

	protected function build_form_json(Wnd_Form_Option $form): string {
		$form->add_radio(
			[
				'name'     => 'convert_webp',
				'options'  => ['启用' => 1, '禁用' => -1],
				'label'    => '转 webp',
				'help'     => ['text' => '将上传图片转换为 webp 格式，支持 jpg、png、gif 格式的图片（浏览器直传时无效）'],
				'class'    => 'is-checkradio is-danger',
				'required' => true,
			]
		);
		$form->add_number(
			[
				'name'        => 'webp_quality',
				'label'       => 'webp 质量',
				'placeholder' => '0-100',
				'min'         => 0,
				'max'         => 100,
				'step'        => 1,
			]
		);
		$form->set_submit_button('保存', 'is-danger');

		return $form->get_json();
	}
}
