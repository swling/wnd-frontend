<?php
/**
 *@since 2019.02.11 创建支付宝网页充值订单
 */
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// 引入必须的文件
require dirname(dirname(__FILE__)) . '/config.php';
require dirname(dirname(__FILE__)) . '/aop/request/AlipayTradePagePayRequest.php';
require dirname(__FILE__) . '/service/AlipayTradeService.php';
require dirname(__FILE__) . '/buildermodel/AlipayTradePagePayContentBuilder.php';

$user_id = get_current_user_id();

$money = isset($_POST['money']) && is_numeric($_POST['money']) ? $_POST['money'] : 0;
if (!$money) {
	wp_die('获取金额错误！', get_bloginfo('name'));
}

$subject = get_bloginfo('name') . '充值订单[' . get_the_author_meta('user_login', $user_id) . ']';

$out_trade_no = wnd_insert_recharge($user_id, $money);
if (!$out_trade_no) {
	wp_die('订单创建错误！', get_bloginfo('name'));
}

/**
 *@since 2019.02.11 构造订单，发起付款请求
 */
//商户订单号，商户网站订单系统中唯一订单号，必填
$out_trade_no = $out_trade_no;
//订单名称，必填
$subject = $subject;
//付款金额，必填
$total_amount = trim($money);
//商品描述，可空
$body = '';

//构造参数
$payRequestBuilder = new AlipayTradePagePayContentBuilder();
$payRequestBuilder->setBody($body);
$payRequestBuilder->setSubject($subject);
$payRequestBuilder->setTotalAmount($total_amount);
$payRequestBuilder->setOutTradeNo($out_trade_no);
$aop = new AlipayTradeService($config);
/**
 * pagePay 电脑网站支付请求
 *
@param $builder 业务参数，使用buildmodel中的对象生成。
 *
@param $return_url 同步跳转地址，公网可以访问
 *
@param $notify_url 异步通知地址，公网可以访问
 *
@return $response 支付宝返回的信息
 */
$response = $aop->pagePay($payRequestBuilder, $config['return_url'], $config['notify_url']);
