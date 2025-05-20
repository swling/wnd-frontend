<?php

namespace Wnd\Action\Common;

use Exception;
use Wnd\Action\Wnd_Action_User;
use Wnd\Model\Wnd_Transaction;
use Wnd\WPDB\Wnd_Transaction_DB;

/**
 * 用户确认订单
 * - 订单状态
 * - 订单 props 属性
 *
 * @since 0.9.87
 */
class Wnd_confirm_receipt extends Wnd_Action_User {

	protected $verify_sign = false;

	private $id;
	private $props;
	private $after_transaction;
	private $before_transaction;

	protected function execute(): array {
		$status  = Wnd_Transaction::$completed_status;
		$handler = Wnd_Transaction_DB::get_instance();
		$action  = $handler->update(['status' => $status, 'props' => $this->props], ['ID' => $this->id]);
		if (!$action) {
			throw new Exception(__('操作失败', 'wnd'));
		}

		// 返回更新后的数据
		$this->after_transaction = $handler->get($this->id);
		return ['status' => 1, 'data' => $this->after_transaction];
	}

	protected function parse_data() {
		$this->id    = (int) $this->data['id'];
		$handler     = Wnd_Transaction_DB::get_instance();
		$transaction = $handler->get($this->id);
		if (!$transaction) {
			throw new Exception(__('ID无效', 'wnd'));
		}
		$this->before_transaction = $transaction;

		// 支持更新地址
		$new_props = [];
		if (isset($this->data['receiver'])) {
			$new_props['receiver'] = $this->data['receiver'];
		}
		$props       = (array) json_decode($transaction->props, true);
		$props       = array_merge($props, $new_props);
		$this->props = json_encode($props);
	}

	protected function check() {
		if ($this->user_id != $this->before_transaction->user_id) {
			throw new Exception(__('权限错误', 'wnd'));
		}

		// 非常重要：必须检测订单是否为待收货状态
		if ($this->before_transaction->status != Wnd_Transaction::$shipped_status) {
			throw new Exception(__('订单状态异常', 'wnd'));
		}
	}

	protected function complete() {
		// if ($this->before_transaction->status != Wnd_Transaction::$paid_status) {
		// 	return;
		// }

		// if ($this->after_transaction->status == Wnd_Transaction::$shipped_status) {
		// 	return;
		// }
	}
}
