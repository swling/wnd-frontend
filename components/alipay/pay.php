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
	$money = isset($_REQUEST['money']) && is_numeric($_REQUEST['money']) ? $_REQUEST['money'] : 0;
}
if (!$money) {
	wp_die('获取金额错误！', get_bloginfo('name'));
}

// 判断支付类型：充值或下单
$subject = $post_id ? get_bloginfo('name') . '订单 [' . get_the_title($post_id) . ']' : get_bloginfo('name') . '充值订单[' . get_userdata($user_id)->user_login . ']';

// 创建支付数据
$out_trade_no = wnd_insert_payment($user_id, $money, $post_id);
if (!$out_trade_no) {
	wp_die('订单创建错误！', get_bloginfo('name'));
}

/*** ########################################################## 配置结束 构建支付数据 通常以下信息无需修改 ***/

// 引入支付基础配置信息
require dirname(__FILE__) . '/config.php';
require dirname(__FILE__) . '/class/AlipayBuilder.php';
$aliPay = new AlipayPagePayBuilder();

// 订单属性
$aliPay->total_amount = $money;
$aliPay->out_trade_no = $out_trade_no;
$aliPay->subject = $subject;

/**
 *@since 2019.03.03
 * 配置支付宝API
 * PC支付和wap支付中：product_code 、method 参数有所不同，详情查阅如下
 *@link https://docs.open.alipay.com/270/alipay.trade.page.pay
 *@link https://docs.open.alipay.com/203/107090/
 */
$aliPay->product_code = wp_is_mobile() ? 'QUICK_WAP_WAY' : 'FAST_INSTANT_TRADE_PAY';
$aliPay->method = wp_is_mobile() ? 'alipay.trade.wap.pay' : 'alipay.trade.page.pay';

$aliPay->gateway_url = $config['gateway_url'];
$aliPay->app_id = $config['app_id'];
$aliPay->return_url = $config['return_url'];
$aliPay->notify_url = $config['notify_url'];
$aliPay->private_key = $config['merchant_private_key'];

// 生成数据表单并提交
echo $aliPay->doPay();
