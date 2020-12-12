<?php
namespace Wnd\Getway\Payment;

use Wnd\Model\Wnd_Payment;

/**
 *@since 2020.06.20
 *站内支付伪类
 * - 站内交易无需执行相关支付操作
 * - 因 Wnd_Payment 为抽象类，无法直接实例化调用内部非静态方法，故设置此类，仅作为读取站内交易支付记录信息所用
 */
class Internal extends Wnd_Payment {

	/**
	 *发起支付
	 *
	 */
	public function build_interface(): string {
		return __CLASS__;
	}

	/**
	 *同步回调通知
	 *
	 */
	protected function check_return(): bool {
		return true;
	}

	/**
	 *异步回调通知
	 *
	 */
	protected function check_notify(): bool {
		return true;
	}
}
