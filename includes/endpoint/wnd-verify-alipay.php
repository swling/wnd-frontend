<?php
namespace Wnd\Endpoint;

use Wnd\Getway\Payment\Alipay;
use Wnd\Model\Wnd_Transaction;

/**
 * 支付宝校验
 * 注意事项：在异步支付通知中，不得输出任何支付平台规定之外的字符或HTML代码。
 * 			 故此，调用本类时，相关异常应使用 exit 中止并输出
 * @since 0.9.32
 */
class Wnd_Verify_Alipay extends Wnd_Verify_Pay {
	/**
	 * 根据交易订单解析站内交易ID，并查询记录
	 */
	protected function parse_transaction(): Wnd_Transaction {
		return Alipay::parse_transaction();
	}
}
