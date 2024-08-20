<?php
namespace Wnd\Module\User;

use Exception;
use Wnd\Module\Wnd_Module_Html;
use WP_Application_Passwords;

/**
 * @since 0.9.72
 * 封装用户Application password 管理面包
 */
class Wnd_Application_Password extends Wnd_Module_Html {

	protected static function build($args = []): string {
		$user_id   = get_current_user_id();
		$passwords = array_values(WP_Application_Passwords::get_user_application_passwords($user_id));
		$html      = '';
		$html .= '<script>var passwords = ' . json_encode($passwords) . ';</script>';

		/**
		 * 采用 vue 文件编写代码，并通过 php 读取文件文本作为字符串使用
		 * 主要目的是便于编辑，避免在 php 文件中混入大量 HTML 源码，难以维护
		 * 虽然的确基于 vue 构建，然而在这里，它并不是标准的 vue 文件，而是 HTML 文件
		 * 之所以使用 .vue 后缀是因为 .HTML 文件在文件夹中将以浏览器图标展示，非常丑陋，毫无科技感
		 * 仅此而已
		 */
		$html .= file_get_contents(WND_PATH . '/includes/module-vue/user/application-password.vue');
		return $html;
	}

	protected static function check($args) {
		if (!is_user_logged_in()) {
			throw new Exception(__('请登录', 'wnd'));
		}
	}
}
