<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}
/**
 *@since 2019.03.02 轻量化改造，新增wap支付
 **/

/*** 请填写以下配置信息
$out_trade_no = uniqid();  	//你自己的商品订单号，不能重复
$money = 0.01; 				//付款金额，单位:元
$order_name = '支付测试';   //订单标题
 */

$user_id = get_current_user_id();
$post_id = $_REQUEST['post_id'] ?? 0;

// 获取金额
if ($post_id) {
	$money = wnd_get_post_price($post_id);
} else {
	$money = isset($_POST['money']) && is_numeric($_POST['money']) ? number_format($_POST['money'], 2) : 0;
}
if (!$money) {
	wp_die('获取金额错误！', get_bloginfo('name'));
}

// 判断支付类型：充值或下单
$order_name = $post_id ? '订单 [' . get_the_title($post_id) . ']' : get_bloginfo('name') . '充值订单[' . get_userdata($user_id)->user_login . ']';

// 创建支付数据
$out_trade_no = wnd_insert_payment($user_id, $money, $post_id);
if (!$out_trade_no) {
	wp_die('订单创建错误！', get_bloginfo('name'));
}

/*** ########################################################## 配置结束 构建支付数据 通常以下信息无需修改 ***/

// 引入支付基础配置信息
require dirname(__FILE__) . '/config.php';

// PC网页支付
if (!wp_is_mobile()) {

	require dirname(__FILE__) . '/class/AlipayPagePayBuilder.php';
	$aliPay = new AlipayPagePayBuilder();
	$aliPay->setAppid($config['app_id']);
	$aliPay->setReturnUrl($config['return_url']);
	$aliPay->setNotifyUrl($config['notify_url']);
	$aliPay->setRsaPrivateKey($config['merchant_private_key']);

	$aliPay->setTotalAmount($money);
	$aliPay->setOutTradeNo($out_trade_no);
	$aliPay->setOrderName($order_name);
	$sHtml = $aliPay->doPay();
	echo $sHtml;
}

/**
 *@since 2019.03.02 支付宝wap支付
 */
else {

	require dirname(__FILE__) . '/class/AlipayWapPayBuilder.php';
	// 构造wap移动支付
	$aliPay = new AlipayWapPayBuilder();
	$aliPay->setAppid($config['app_id']);
	$aliPay->setReturnUrl($config['return_url']);
	$aliPay->setNotifyUrl($config['notify_url']);
	$aliPay->setRsaPrivateKey($config['merchant_private_key']);

	$aliPay->setTotalAmount($money);
	$aliPay->setOutTradeNo($out_trade_no);
	$aliPay->setOrderName($order_name);
	$sHtml = $aliPay->doPay();
	echo $sHtml;

}