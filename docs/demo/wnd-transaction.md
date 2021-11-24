```php
use Wnd\Getway\Wnd_Payment;
use Wnd\Model\Wnd_Order;
use Wnd\Model\Wnd_Recharge;
use Wnd\Model\Wnd_Transaction;

###########################################################
/**
 * payment 示例代码
 * 若设置object id则为创建在线订单，反之为在线余额充值
 * $payment_gateway 	对应第三方支付平台标识典型值如：Alipay、Wepay……
 * 					    需完善对应支付接口后方可顺利使用
 * 					    @see Wnd\Getway\Wnd_Payment::get_instance($payment_gateway);
 * @since 2019.8.12
 */

// 创建站内记录：$type 通常为 order 或 recharge 亦可自行拓展
$transaction = Wnd_Transaction::get_instance($type);
$transaction->set_payment_gateway($payment_gateway);
$transaction->set_object_id($post_id);
$transaction->set_quantity($quantity);
$transaction->set_total_amount($total_amount);
$transaction->set_props($this->data);
$transaction->set_subject($subject);
$transaction->create(false);

// 构造第三方支付接口：表单提交或二维码等
$payment = Wnd_Payment::get_instance($transaction);
echo $payment->build_interface();

/**
 * 获取支付平台返回数据，并完成支付。根据第三方支付订单，获取站内订单，并对比充值金额
 * - 根据交易订单解析站内交易ID，并查询记录
 * - 校验订单
 *
 * 此处以支付宝为例
 */
$transaction = \Wnd\Getway\Payment\Alipay::parse_transaction();
try {
	$payment = Wnd_Payment::get_instance($transaction);
	$payment->verify_payment();
	$payment->update_transaction();
	$payment->return();
} catch (Exception $e) {
	exit($e->getMessage());
}

###########################################################
/**
 * order 示例代码
 * @since 2019.8.12
 */
// 创建支付订单
$order = new Wnd_Order();
$order->set_object_id($post_id);
$order->create();

// 订单完成
$order = new Wnd_Order();
$order->set_transaction_id($post_id);
$order->verify();

// 创建并完成订单
$order = new Wnd_Order();
$order->set_object_id($post_id);
$order->create($is_completed = true);

// 手动指定价格，并创建支付订单
$order = new Wnd_Order();
$order->set_total_amount($price);
$order->set_object_id($post_id);
$order->create();

// 创建无产品的订单
$order = new Wnd_Order();
$order->set_total_amount($price);
$order->set_subject($subject);
$order->create($is_completed = true);

###########################################################
/**
 * recharge 示例代码
 * @since 2019.8.12
 */
// 创建常规充值
$recharge = new Wnd_Recharge();
$recharge->set_total_amount($total_amount);
$recharge->create();

// 创建站内分成
$recharge = new Wnd_Recharge();
$recharge->set_object_id($post_id); // 设置充值来源
$recharge->set_total_amount($total_amount);
$recharge->create(true); // 直接写入余额

// 完成充值
$recharge = new Wnd_Recharge();
$recharge->set_transaction_id($post_id);
$recharge->verify();
```