<?php
namespace Wnd\Admin;

use Wnd\View\Wnd_Form_Option;

/**
 * 对象存储
 * @since 0.9.29
 */
class Wnd_Menu_CDN extends Wnd_Menus {

	// 子菜单基本属性
	protected $page_title = 'CDN';
	protected $menu_title = 'CDN';
	protected $menu_slug  = 'wnd-frontend-cdn';

	/**
	 * 构造表单
	 */
	protected function build_form(Wnd_Form_Option $form): string{

		$form->add_radio(
			[
				'name'     => 'cdn_enable',
				'options'  => ['启用' => 1, '禁用' => 0],
				'label'    => '启用 CDN',
				'class'    => 'is-checkradio is-danger',
				'required' => true,
			]
		);

		$form->add_url(
			[
				'name'        => 'cdn_url',
				'label'       => 'CDN URL',
				'required'    => true,
				'placeholder' => '对象存储节点完整 URL（不要忘了 https:// ）',
				'help'        => ['text' => '将以此替换静态资源的本地域名：' . get_option('siteurl')],
			]
		);

		$form->add_text(
			[
				'name'        => 'cdn_dir',
				'label'       => 'CDN 目录',
				'placeholder' => 'CDN 作用目录',
				'help'        => ['text' => '哪些目录下的静态资源启用 CDN，以英文逗号区分，留空为 wp-content,wp-includes'],
			]
		);

		$form->add_text(
			[
				'name'        => 'cdn_excludes',
				'label'       => 'CDN 文件后缀排除',
				'placeholder' => '排除的文件后缀名',
				'help'        => ['text' => '以英文逗号区分如：.php,.flv, 留空则为：.php'],
			]
		);

		$form->set_submit_button('保存', 'is-danger');
		$form->build();

		return $form->html;
	}
}
