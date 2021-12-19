<?php
namespace Wnd\Action;

use Exception;

/**
 * 账户状态
 * 账户状态为本插件自定义功能，故添加两个do_action以便后期拓展功能：
 * do_action('wnd_ban_account', $user_id);
 * do_action('wnd_restore_account', $user_id);
 *
 * @since 2020.04.30
 */
class Wnd_Update_Account_Status extends Wnd_Action_Admin {

	private $target_user_id;
	private $status;
	private $before_status;

	protected function execute(): array{
		// 更新状态
		$action = update_user_meta($this->target_user_id, 'status', $this->status);
		if (!$action) {
			throw new Exception(__('操作失败', 'wnd'));
		}

		// 封禁账户Action
		if ('banned' == $this->status) {
			do_action('wnd_ban_account', $this->target_user_id);
			return ['status' => 1, 'msg' => __('账户已被封禁', 'wnd')];
		}

		// 恢复账户Action
		if ('ok' == $this->status and 'banned' == $this->before_status) {
			do_action('wnd_restore_account', $this->target_user_id);
			return ['status' => 1, 'msg' => __('账户已解封', 'wnd')];
		}
	}

	protected function check() {
		$this->target_user_id = (int) $this->data['user_id'];
		$this->status         = $this->data['status'] ?? '';
		$this->before_status  = get_user_meta($this->target_user_id, 'status', true) ?: 'ok';

		if (!$this->target_user_id) {
			throw new Exception(__('ID无效', 'wnd'));
		}

		// 未发生改变
		if ($this->status == $this->before_status) {
			throw new Exception(__('未发生改变', 'wnd'));
		}
	}
}
