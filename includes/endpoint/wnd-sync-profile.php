<?php
namespace Wnd\Endpoint;

use Exception;
use Wnd\Endpoint\Wnd_Endpoint;

/**
 * 接收远程应用通过api同步的用户信息
 * - 适用于各类第三方应用，如小程序等，快速同步用户基本信息至本应用
 * - 内置包含头像，昵称，用户简介三个 profile 字段
 * - 可通过钩子自行拓展其他 profile 字段
 *
 * @since 0.9.50
 */
class Wnd_Sync_Profile extends Wnd_Endpoint {

	protected $content_type = 'json';

	protected $user_id;
	protected $avatar_url;
	protected $display_name;
	protected $description;

	protected function do() {
		if ($this->display_name) {
			$sync_user = wp_update_user(['ID' => $this->user_id, 'display_name' => $this->display_name]);
			if (is_wp_error($sync_user)) {
				throw new Exception('Failed to sync user');
			}
		}

		if ($this->avatar_url) {
			wnd_update_user_meta($this->user_id, 'avatar_url', $this->avatar_url);
		}

		if ($this->description) {
			wnd_update_user_meta($this->user_id, 'description', $this->description);
		}

		// 添加钩子以便于主题或其他插件拓展功能
		do_action('wnd_sync_profile', $this->user_id, $this->data);

		// Endpoint 节点之间输出响应字符
		echo json_encode(['status' => 1, 'msg' => 'Sync profile success']);
	}

	protected function check() {
		$this->user_id      = get_current_user_id();
		$this->avatar_url   = $this->data['avatar_url'] ?? '';
		$this->display_name = $this->data['display_name'] ?? '';
		$this->description  = $this->data['description'] ?? '';

		if (!$this->user_id) {
			throw new Exception('Invalid user id ');
		}
	}
}
