<?php
namespace Wnd\Model;

use Exception;

/**
 *@since 2020.06.11
 *站内退款
 */
class Wnd_Refunder_Internal extends Wnd_Refunder {

	/**
	 *站内订单退款：退款至用户余额 (站外则直接退款至用户相关支付账户)
	 *
	 *站内充值退款：无需额外操作。 @see Wnd_Refunder->deduction();
	 */
	protected function do_refund() {
		if ('order' != $this->transaction_type) {
			return;
		}

		if (!wnd_inc_user_money($this->user_id, $this->refund_amount)) {
			throw new Exception(__('退款失败', 'wnd') . ':' . static::$payment_gateway);
		}
	}
}
