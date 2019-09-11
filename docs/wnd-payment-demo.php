<?php

###########################################################
/**
 *@since 2019.8.12
 *payment 示例代码
 *若设置object id则为创建在线订单，反之为在线余额充值
 */

// 创建支付
$payment = new Wnd_Payment();
$payment->set_total_amount(10);
// or 设置object id之后，充值金额将设定为对应的产品价格
$payment->set_object_id(616);

$payment->create();
$payment->get_out_trade_no();
$payment->get_subject();
$payment->get_total_amount();

// 获取支付平台返回数据，并完成支付。根据第三方支付订单，获取站内订单，并对比充值金额
$payment = new Wnd_Payment();
$payment->set_total_amount($_POST['total_amount']);
$payment->set_out_trade_no($_POST['out_trade_no']);
$payment->verify();

$payment->get_object_id();

###########################################################
/**
 *@since 2019.8.12
 *order 示例代码
 */
// 创建支付订单
$order = new Wnd_Order();
$order->set_object_id($post_id);
$order->create();

// 订单完成
$order = new Wnd_Order();
$order->set_ID($post_id);
$order->verify();

// 创建并完成订单
$order = new Wnd_Order();
$order->set_object_id($post_id);
$order->create($is_success = true);

// 手动指定价格，并创建支付订单
$order = new Wnd_Order();
$order->set_total_amount($price);
$order->set_object_id($post_id);
$order->create();

// 创建无产品的订单
$order = new Wnd_Order();
$order->set_total_amount($price);
$order->set_subject($subject);
$order->create($is_success = true);

###########################################################
/**
 *@since 2019.8.12
 *recharge 示例代码
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
$recharge->set_ID($post_id);
$recharge->verify();
