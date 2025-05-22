<?php
namespace Wnd\Action\User;

use Wnd\Action\Wnd_Action_User;
use Wnd\Model\Wnd_Order_Props;

/**
 * 用户保存收货地址
 * @since 0.9.89
 */
class Wnd_Update_Receiver extends Wnd_Action_User {

	protected $verify_sign = false;

	protected function execute(): array {
		$key      = Wnd_Order_Props::$receiver_key;
		$receiver = $this->data['receiver'] ?? '';

		if (wnd_update_user_meta($this->user_id, $key, $receiver)) {
			return ['status' => 1, 'msg' => 'success'];
		} else {
			return ['status' => 0, 'msg' => 'error'];
		}
	}
}
