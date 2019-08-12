<?php
// header('Content-type:text/html; Charset=utf-8');
require dirname(__FILE__) . '/config.php';
require dirname(__FILE__) . '/class/AlipayService.php';

//支付宝公钥，账户中心->密钥管理->开放平台密钥，找到添加了支付功能的应用，根据你的加密类型，查看支付宝公钥
$aliPay = new AlipayService($config['alipay_public_key']);

//验证签名
$result = $aliPay->rsaCheck($_POST);

// 验签失败，抛出错误，中止操作
if ($result !== true) {
	exit('error');
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 *请在这里加上商户的业务逻辑程序代
 *获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
 *
 *如果校验成功必须输出 'success'，页面源码不得包含其他及HTML字符
 *
 */

//商户订单号
$out_trade_no = $_POST['out_trade_no'];
//交易状态
$trade_status = $_POST['trade_status'];
// 金额
$total_amount = $_POST['total_amount'];
// app_id
$app_id = $_POST['app_id'];

if ($trade_status == 'TRADE_FINISHED') {

	//判断该笔订单是否在商户网站中已经做过处理
	//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
	//请务必判断请求时的total_amount与通知时获取的total_fee为一致的
	//如果有做过处理，不执行商户的业务程序

	//注意：
	//退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知

	exit('success'); //由于是即时到账不可退款服务，因此直接返回成功
}

if ($trade_status == 'TRADE_SUCCESS') {
	//判断该笔订单是否在商户网站中已经做过处理
	//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
	//请务必判断请求时的total_amount与通知时获取的total_fee为一致的
	//如果有做过处理，不执行商户的业务程序
	//注意：
	//付款完成后，支付宝系统发送该交易状态通知

	/**
	 *@since 2019.08.12 异步校验
	 */
	try {
		$payment = new Wnd_Payment();
		$payment->set_total_amount($total_amount);
		$payment->set_out_trade_no($out_trade_no);
		$payment->verify();
	} catch (Exception $e) {
		exit($e->getMessage());
	}

	// 校验通过
	exit('success');
}
