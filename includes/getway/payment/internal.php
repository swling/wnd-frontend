<?php
namespace Wnd\Getway\Payment;

use Wnd\Model\Wnd_Payment;

/**
 *@since 2020.06.20
 *站内支付伪类
 * - 站内交易无需执行相关支付操作
 * - 因 Wnd_Payment 为抽象类，无法直接实例化调用内部非静态方法，故设置此类，仅作为读取站内交易支付记录信息所用
 *
 *@since 0.9.17
 * - 相关回调校验必须返回 False 否则将产生支付漏洞
 */
class Internal extends Wnd_Payment {

	/**
	 *构造支付界面
	 */
	public function build_interface(): string {
		return __('在线支付禁止调用 Internal 网关', 'wnd');
	}

	/**
	 *同步回调通知
	 */
	protected function check_return(): bool {
		return false;
	}

	/**
	 *异步回调通知
	 */
	protected function check_notify(): bool {
		return false;
	}
}
