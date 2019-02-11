<?php
/* *
 * 功能：支付宝页面跳转同步通知页面
 * 版本：2.0
 * 修改日期：2017-05-01
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。

 *************************页面功能说明*************************
 * 该页面可在本机电脑测试
 * 可放入HTML等美化页面的代码、商户业务逻辑程序代码
 */
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

require dirname(__FILE__) . '/config.php';
require dirname(__FILE__) . '/pagepay/service/AlipayTradeService.php';

$arr = $_GET;
$alipaySevice = new AlipayTradeService($config);
$result = $alipaySevice->check($arr);

/* 实际验证过程建议商户添加以下校验。
1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
4、验证app_id是否为该商户本身。
 */
if (!$result) {
//验证成功
	echo "验证失败";
	return;
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

//请在这里加上商户的业务逻辑程序代码

//商户订单号
$out_trade_no = $_GET['out_trade_no'];
//交易状态
$trade_status = $_GET['trade_status'];
// 金额
$total_amount = $_GET['total_amount'];
// app_id
$app_id = $_GET['app_id'];

/**
 *@since 2019.02.11 支付宝同步校验
 */
$wnd_verify_recharge = wnd_verify_payment($out_trade_no, $amount, $app_id);

if ($wnd_verify_recharge['status'] > 0) {

	header("Location:" . wnd_get_option('wndwp', 'wnd_pay_return_url'));
	exit;

} else {

	echo "fail";
	$alipaySevice->writeLog($wnd_verify_recharge['msg']);

}

//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
