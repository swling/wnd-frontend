<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

$config = array(
	//应用ID,您的APPID。
	'app_id' => wnd_get_option('wndwp', 'wnd_alipay_appid'),

	/**
	 *@link https://docs.open.alipay.com/58/103242
	 *商户私钥 用工具生成的应用私钥
	 **/
	'merchant_private_key' => wnd_get_option('wndwp', 'wnd_alipay_private_key'),

	/**
	 *支付宝公钥 @link https://openhome.alipay.com/platform/keyManage.htm
	 *对应APPID下的支付宝公钥。用工具生成应用公钥后，上传到支付宝，再由支付宝生成
	 */
	'alipay_public_key' => wnd_get_option('wndwp', 'wnd_alipay_public_key'),

	//异步通知地址 *不能带参数否则校验不过 （插件执行页面地址）
	'notify_url' => wnd_get_do_url(),

	//同步跳转 *不能带参数否则校验不过 （插件执行页面地址）
	'return_url' => wnd_get_do_url(),

	//编码格式
	'charset' => "UTF-8",

	//签名方式
	'sign_type' => "RSA2",

	//支付宝网关
	'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
);