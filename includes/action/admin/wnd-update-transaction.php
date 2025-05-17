<?php

namespace Wnd\Action\Admin;

use Exception;
use Wnd\Action\Wnd_Action_Admin;
use Wnd\Model\Wnd_Transaction;
use Wnd\WPDB\Wnd_Transaction_DB;

/**
 * 管理员更新订单
 * - 订单状态
 * - 订单 props 属性
 *
 * @since 0.9.87
 */
class Wnd_Update_Transaction extends Wnd_Action_Admin {

	protected $verify_sign = false;

	private $id;
	private $status;
	private $props;
	private $after_transaction;
	private $before_transaction;

	protected function execute(): array {
		$handler = Wnd_Transaction_DB::get_instance();
		$action  = $handler->update(['status' => $this->status, 'props' => $this->props], ['ID' => $this->id]);
		if (!$action) {
			throw new Exception(__('操作失败', 'wnd'));
		}

		// 返回更新后的数据
		$this->after_transaction = $handler->get($this->id);
		return ['status' => 1, 'data' => $this->after_transaction];
	}

	protected function parse_data() {
		$this->id     = (int) $this->data['id'];
		$this->status = $this->data['status'] ?? '';

		$handler     = Wnd_Transaction_DB::get_instance();
		$transaction = $handler->get($this->id);
		if (!$transaction) {
			throw new Exception(__('ID无效', 'wnd'));
		}
		$this->before_transaction = $transaction;

		// 支持更新地址和状态
		$new_props = [];
		if (isset($this->data['receiver'])) {
			$new_props['receiver'] = $this->data['receiver'];
		}
		if (isset($this->data['express_no'])) {
			$new_props['express_no'] = $this->data['express_no'];
		}
		$props       = (array) json_decode($transaction->props, true);
		$props       = array_merge($props, $new_props);
		$this->props = json_encode($props);
	}

	protected function check() {
		if (!$this->id) {
			throw new Exception(__('ID无效', 'wnd'));
		}
	}

	protected function complete() {
		if ($this->before_transaction->status != Wnd_Transaction::$paid_status) {
			return;
		}

		if ($this->after_transaction->status == Wnd_Transaction::$shipped_status) {
			$message = $this->after_transaction->subject;
			wnd_mail($this->after_transaction->user_id, __('商品发货通知', 'wnd'), $message);
			return;
		}
	}
}
