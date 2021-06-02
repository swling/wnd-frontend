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
	 *构造表单
	 */
	public function build_form() {
		$form = new Wnd_Form_Option($this->option_name, $this->append);

		$form->add_radio(
			[
				'name'     => 'oss_enable',
				'options'  => ['启用' => 1, '禁用' => 0],
				'label'    => '启用对象存储功能',
				'class'    => 'is-checkradio is-danger',
				'required' => true,
			]
		);

		$form->add_radio(
			[
				'name'     => 'oss_local_storage',
				'options'  => ['删除' => 0, '保留' => 1],
				'label'    => '本地服务器文件',
				'class'    => 'is-checkradio is-danger',
				'required' => true,
			]
		);

		$form->add_radio(
			[
				'name'     => 'oss_sp',
				'options'  => ['腾讯云' => 'Qcloud', '阿里云' => 'Aliyun'],
				'label'    => '对象存储服务商',
				'class'    => 'is-checkradio is-danger',
				'required' => true,
			]
		);

		$form->add_url(
			[
				'name'        => 'oss_endpoint',
				'label'       => '对象存储节点 URL',
				'required'    => true,
				'placeholder' => '对象存储节点完整 URL（不要忘了 https:// ）',
				'help'        => ['text' => '阿里云 OSS 统一区域可填写内网地址'],
			]
		);

		$form->add_text(
			[
				'name'        => 'oss_dir',
				'label'       => '节点目录',
				'required'    => false,
				'placeholder' => '本应用在指定存储节点的子目录（留空为根目录）',
				'help'        => ['text' => '若多个站点共用一个存储节点，强烈建议填写目录以区分'],
			]
		);

		$form->add_url(
			[
				'name'        => 'oss_base_url',
				'label'       => '外网 URL',
				'required'    => true,
				'placeholder' => '外网 URL（不要忘了 https:// ）',
				'help'        => ['text' => '通常为【节点URL + 目录】或 CDN URL'],
			]
		);

		$form->set_submit_button('保存', 'is-danger');
		$form->build();

		return $form->html;
	}
}
