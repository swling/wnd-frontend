<?php
namespace Wnd\Module\User;

use Exception;
use Wnd\Module\Wnd_Module_Vue;
use WP_Application_Passwords;

/**
 * @since 0.9.72
 * 封装用户Application password 管理面板
 */
class Wnd_Application_Password extends Wnd_Module_Vue {

	protected static function parse_data(array $args = []): array {
		$user_id   = get_current_user_id();
		$passwords = array_values(WP_Application_Passwords::get_user_application_passwords($user_id));
		return ['passwords' => $passwords];
	}

	protected static function check($args) {
		if (!is_user_logged_in()) {
			throw new Exception(__('请登录', 'wnd'));
		}
	}
}
