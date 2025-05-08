<?php

namespace Wnd\Module\User;

use Exception;
use Wnd\Module\Wnd_Module_Html;

/**
 * @since 0.9.86 独立附件数据表
 */
class Wnd_Attachments extends Wnd_Module_Html {

	protected static function build(): string {
		$tabs = [
			[
				'label'   => __('状态', 'wnd'),
				'key'     => 'status',
				'options' => [
					__('全部', 'wnd')  => 'any',
				],
			],

		];
		$param = ['user_id' => 'any'];
		$html  = '<script>var vue_tabs = ' . json_encode($tabs, JSON_UNESCAPED_UNICODE) . '; var vue_param = ' . json_encode($param) . '</script>';

		/**
		 * 采用 vue 文件编写代码，并通过 php 读取文件文本作为字符串使用
		 * 主要目的是便于编辑，避免在 php 文件中混入大量 HTML 源码，难以维护
		 * 虽然的确基于 vue 构建，然而在这里，它并不是标准的 vue 文件，而是 HTML 文件
		 * 之所以使用 .vue 后缀是因为 .HTML 文件在文件夹中将以浏览器图标展示，非常丑陋，毫无科技感
		 * 仅此而已
		 */
		$html .= file_get_contents(WND_PATH . '/includes/module-vue/user/attachments.vue');
		return $html;
	}

	protected static function check($args) {
		if (!is_user_logged_in()) {
			throw new Exception(__('权限不足', 'wnd'));
		}
	}
}
