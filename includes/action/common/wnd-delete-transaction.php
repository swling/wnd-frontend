<?php

namespace Wnd\Action\Common;

use Exception;
use Wnd\Action\Wnd_Action_User;
use Wnd\WPDB\Wnd_Transaction_DB;

/**
 * 删除订单
 * @since 2019.01.23
 */
class Wnd_Delete_Transaction extends Wnd_Action_User {

	protected $verify_sign = false;
	private $transaction_id;
	private $transaction;

	protected function execute(): array {
		$handler = Wnd_Transaction_DB::get_instance();
		if ($handler->delete($this->transaction_id)) {
			return ['status' => 1, 'msg' => __('删除成功', 'wnd'), 'data' => $this->transaction_id];
		}

		throw new Exception(__('删除失败', 'wnd'));
	}

	protected function parse_data() {
		$this->transaction_id = (int) $this->data['id'];
		$handler              = Wnd_Transaction_DB::get_instance();
		$this->transaction    = $handler->get($this->transaction_id);
	}

	protected function check() {
		if (!$this->transaction) {
			throw new Exception(__('ID无效', 'wnd'));
		}

		// 禁止删除七天内的订单
		if ($this->transaction->time > time() - 3600 * 24 * 7) {
			throw new Exception(__('七天内的订单不能删除', 'wnd'));
		}

		if (wnd_is_manager()) {
			return;
		}

		if (!$this->user_id or $this->user_id != $this->transaction->user_id) {
			throw new Exception(__('权限错误', 'wnd'));
		}
	}
}
