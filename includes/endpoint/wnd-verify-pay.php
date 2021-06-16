<?php
namespace Wnd\Endpoint;

use Exception;
use Wnd\Getway\Wnd_Payment;
use Wnd\Model\Wnd_Transaction;

/**
 * 支付校验基类
 * 注意事项：通常在异步支付通知中，不得输出任何支付平台规定之外的字符或HTML代码。
 * 			 故此，调用本类时，相关异常应使用 exit 中止并输出
 * @since 0.9.32
 */
abstract class Wnd_Verify_Pay extends Wnd_Endpoint {
	// 响应类型
	protected $content_type = 'txt';

	// 站内交易记录实例化对象
	protected $transaction;

	/**
	 * 响应操作
	 */
	protected function do() {
		/**
		 * 验签并处理相关站内业务
		 */
		try {
			$payment = Wnd_Payment::get_instance($this->parse_transaction());
			$payment->verify_payment();
			$payment->update_transaction();
			$payment->return();
		} catch (Exception $e) {
			exit($e->getMessage());
		}
	}

	/**
	 * 根据交易订单解析站内交易ID，并查询记录
	 */
	abstract protected function parse_transaction(): Wnd_Transaction;
}
