<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// 创建充值订单
require dirname(dirname(__FILE__)).'/config.php';
require dirname(dirname(__FILE__)).'/aop/request/AlipayTradePagePayRequest.php';

require dirname(__FILE__).'/service/AlipayTradeService.php';
require dirname(__FILE__).'/buildermodel/AlipayTradePagePayContentBuilder.php';

$money = isset($_POST['money']) && is_numeric($_POST['money']) ? $_POST['money'] : 0;
$money = esc_sql ($money);

if ($money){
	$user_id = get_current_user_id ();
	
	//  *订单名称，必填
	$subject = get_bloginfo ('name').'充值订单['.get_the_author_meta ('user_login',$user_id).']';

    // 创建订单类型文章记录 并返回文章ID 作为订单号
	$out_trade_no = wnd_insert_recharge($user_id,$money);
	
	//订单数据库写入成功     
	if ($out_trade_no){
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
		$payRequestBuilder->setBody ($body);
		$payRequestBuilder->setSubject ($subject);
		$payRequestBuilder->setTotalAmount ($total_amount);
		$payRequestBuilder->setOutTradeNo ($out_trade_no);
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
		$response = $aop->pagePay ($payRequestBuilder,$config['return_url'],$config['notify_url']);
		
		//输出表单
// 		var_dump($response);
		
	}else wp_die ( '订单创建错误！',get_bloginfo('name') );
	
}else wp_die ( '获取金额错误！',get_bloginfo('name') );

