<?php
namespace Wnd\Admin;

use Wnd\View\Wnd_Form_Option;

/**
 * 对象存储
 * @since 0.9.29
 */
class Wnd_Menu_OSS extends Wnd_Menus {

	// 子菜单基本属性
	protected $page_title = '对象存储';
	protected $menu_title = '对象存储';
	protected $menu_slug  = 'wnd-frontend-oss';

	/**
	 * 构造表单
	 */
	protected function build_form_json(Wnd_Form_Option $form): string{

		$form->add_radio(
			[
				'name'     => 'enable_oss',
				'options'  => ['启用' => 1, '禁用' => ''],
				'label'    => '对象存储',
				'class'    => 'is-checkradio is-danger',
				'required' => true,
			]
		);

		$form->add_radio(
			[
				'name'     => 'oss_local_storage',
				'options'  => ['删除' => '', '保留' => 1, '前端直传' => -1],
				'label'    => '本地文件',
				'class'    => 'is-checkradio is-danger',
				'required' => true,
				'help'     => ['text' => '*注意：浏览器直传需设置节点允许跨域！'],
			]
		);

		$form->add_radio(
			[
				'name'     => 'oss_sp',
				'options'  => ['腾讯云' => 'Qcloud', '阿里云' => 'Aliyun'],
				'label'    => '服务商',
				'class'    => 'is-checkradio is-danger',
				'required' => true,
			]
		);

		$form->add_url(
			[
				'name'        => 'oss_endpoint',
				'label'       => '节点',
				'required'    => true,
				'placeholder' => '对象存储节点完整 URL（不要忘了 https:// ）',
				'help'        => ['text' => '阿里云 OSS 同一区域可填写内网地址'],
			]
		);

		$form->add_text(
			[
				'name'        => 'oss_dir',
				'label'       => '节点目录',
				'required'    => false,
				'placeholder' => '本应用在指定存储节点的子目录（留空为根目录）',
				'help'        => ['text' => '若多个站点共用一个存储节点强烈建议填写以区分目录'],
			]
		);

		$form->add_url(
			[
				'name'        => 'oss_base_url',
				'label'       => '节点 URL',
				'required'    => true,
				'placeholder' => '节点外网 URL（不要忘了 https:// ）',
				'help'        => ['text' => '通常为【节点公网URL + 目录】或 CDN URL'],
			]
		);

		$form->add_url(
			[
				'name'        => 'oss_endpoint_private',
				'label'       => '私有节点',
				'required'    => false,
				'placeholder' => '对象存储节点完整 URL（不要忘了 https:// ）',
				'help'        => ['text' => '私有节点用于储存需要权限下载的文件，如付费下载等，安全性更高（仅支持插件前端编辑器上传）'],
			]
		);

		$form->add_url(
			[
				'name'        => 'oss_base_url_private',
				'label'       => '私有节点 URL',
				'placeholder' => '节点外网 URL（不要忘了 https:// ）',
				'help'        => ['text' => '通常为【节点公网URL + 目录】，私有节点不应设置 CDN'],
			]
		);

		$form->set_submit_button('保存', 'is-danger');

		return $form->get_json();
	}
}
