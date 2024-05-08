<?php
namespace Wnd\Action\User;

use Exception;
use Wnd\Action\Wnd_Action_User;
use WP_Application_Passwords;

/**
 * 用户创建 Application password
 * @since 0.9.72
 */
class Wnd_Create_Application_Password extends Wnd_Action_User {

	protected $verify_sign = false;

	private $name;
	private $app_id;

	protected function execute(): array {
		$created = WP_Application_Passwords::create_new_application_password(
			$this->user_id,
			[
				'name'   => $this->name,
				'app_id' => $this->app_id,
			]
		);

		if (is_wp_error($created)) {
			throw new Exception(__('创建失败', 'wnd'));
		}

		return ['status' => 1, 'data' => $created, 'msg' => __('创建成功', 'wnd')];
	}

	protected function parse_data() {
		$this->name = $this->data['name'] ?? '';
	}

	protected function check() {
		if (!$this->name) {
			throw new Exception('name is empty');
		}
	}
}
