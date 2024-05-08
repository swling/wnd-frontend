<?php
namespace Wnd\Action\User;

use Exception;
use Wnd\Action\Wnd_Action_User;
use WP_Application_Passwords;

/**
 * 用户删除 Application password
 * @since 0.9.72
 */
class Wnd_Delete_Application_Password extends Wnd_Action_User {

	protected $verify_sign = false;

	private $uuid;

	protected function execute(): array {
		$delete = WP_Application_Passwords::delete_application_password($this->user_id, $this->uuid);

		if (is_wp_error($delete)) {
			throw new Exception(__('删除失败', 'wnd'));
		}

		return ['status' => 1, 'msg' => __('删除成功', 'wnd')];
	}

	protected function parse_data() {
		$this->uuid = $this->data['uuid'] ?? '';
	}

	protected function check() {
		if (!$this->uuid) {
			throw new Exception('uuid is empty');
		}
	}
}
