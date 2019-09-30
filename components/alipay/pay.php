<?php
use Wnd\Model\Wnd_Payment;

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}
/**
 *@since 2019.03.02 轻量化改造，新增wap支付
 **/

/*** 请填写以下配置信息
$out_trade_no  	//你自己的商品订单号，不能重复
$total_amount	//付款金额，单位:元
$subject    	//订单标题
 */
$post_id      = $_REQUEST['post_id'] ?? 0;
$total_amount = $_REQUEST['total_amount'] ?? 0;

/**
 *@since 2019.08.12
 *面向对象重构
 **/
try {
	$payment = new Wnd_Payment();
	$payment->set_object_id($post_id);
	$payment->set_total_amount($total_amount);
	$payment->create();

	$out_trade_no = $payment->get_out_trade_no();
	$subject      = $payment->get_subject();
	$total_amount = $payment->get_total_amount();
} catch (Exception $e) {
	exit($e->getMessage());
}

/*** ########################################################## 配置结束 构建支付数据 通常以下信息无需修改 ***/

// 引入支付基础配置信息
require dirname(__FILE__) . '/config.php';
require dirname(__FILE__) . '/class/AlipayBuilder.php';
$aliPay = new AlipayPagePayBuilder();

// 订单属性
$aliPay->total_amount = $total_amount;
$aliPay->out_trade_no = $out_trade_no;
$aliPay->subject      = $subject;

/**
 *@since 2019.03.03
 * 配置支付宝API
 * PC支付和wap支付中：product_code 、method 参数有所不同，详情查阅如下
 *@link https://docs.open.alipay.com/270/alipay.trade.page.pay
 *@link https://docs.open.alipay.com/203/107090/
 */
$aliPay->product_code = wp_is_mobile() ? 'QUICK_WAP_WAY' : 'FAST_INSTANT_TRADE_PAY';
$aliPay->method       = wp_is_mobile() ? 'alipay.trade.wap.pay' : 'alipay.trade.page.pay';
$aliPay->gateway_url  = $config['gateway_url'];
$aliPay->app_id       = $config['app_id'];
$aliPay->return_url   = $config['return_url'];
$aliPay->notify_url   = $config['notify_url'];
$aliPay->private_key  = $config['merchant_private_key'];

// 生成数据表单并提交
echo $aliPay->doPay();
