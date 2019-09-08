<?php

/**
 *@since 2019.03.02 支付宝支付同步跳转
 *同步回调一般不处理业务逻辑，显示一个付款成功的页面，或者跳转到用户的财务记录页面即可。
 */
header('Content-type:text/html; Charset=utf-8');
require dirname(__FILE__) . '/config.php';
require dirname(__FILE__) . '/class/AlipayService.php';

//支付宝公钥，账户中心->密钥管理->开放平台密钥，找到添加了支付功能的应用，根据你的加密类型，查看支付宝公钥
$aliPay = new AlipayService($config['alipay_public_key']);

//验证签名
$result = $aliPay->rsaCheck($_GET);

//校验失败
if ($result !== true) {
	exit('校验失败');
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

//请在这里加上商户的业务逻辑程序代码

//商户订单号
$out_trade_no = $_GET['out_trade_no'];
//交易状态
// $trade_status = $_GET['trade_status'];
// 金额
$total_amount = $_GET['total_amount'];
// app_id
$app_id = $_GET['app_id'];

/**
 *@since 2019.02.11 支付宝同步校验
 */
try {
	$payment = new Wnd_Payment();
	$payment->set_total_amount($total_amount);
	$payment->set_out_trade_no($out_trade_no);
	$payment->verify();

	$object_id = $payment->get_object_id();
} catch (Exception $e) {
	exit($e->getMessage());
}

// 校验通过，跳转
if ($object_id) {
	$link = get_permalink($object_id) ?: wnd_get_option('wnd', 'wnd_pay_return_url') ?: home_url();
	header('Location:' . $link . '?from=payment_successful');
	exit;

// 充值
} else {
	$link = wnd_get_option('wnd', 'wnd_pay_return_url') ?: home_url();
	header('Location:' . $link . '?from=payment_successful');
	exit;
}
