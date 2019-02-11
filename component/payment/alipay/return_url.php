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
if ( !defined( 'ABSPATH' ) ) exit;

require dirname(__FILE__).'/config.php';
require dirname(__FILE__).'/pagepay/service/AlipayTradeService.php';

$arr=$_GET;
$alipaySevice = new AlipayTradeService($config); 
$result = $alipaySevice->check($arr);

/* 实际验证过程建议商户添加以下校验。
1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
4、验证app_id是否为该商户本身。
*/
if($result) {//验证成功
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	//请在这里加上商户的业务逻辑程序代码
	
    $recharge_post = get_post( esc_sql($_GET['out_trade_no']) );
    
        //如果订单金额匹配 	
	    if( $recharge_post->post_title == htmlspecialchars($_GET['total_amount']) ){
	       
	       // 订单支付状态检查
	    	if($recharge_post->post_status =='pending'){
	    	    
	    	    //  站点充值积分 = 充值人民币*插件设置比值*优惠函数
	    	    $money = $recharge_post->post_title;
	    	    
	    	    //  写入用户账户信息
	    		if( wnd_update_recharge($recharge_post->ID,'private','支付宝跳转充值') ){
	    		    
	    		    wnd_inc_user_money($recharge_post->post_author, $money);
	    		    header("Location:" . wnd_get_option('wndwp','wnd_pay_return_url') );
	    		    exit;
	    		    
	    		}else $alipaySevice->writeLog('跳转写入数据数据失败');
	    	
	        //订单已处理过 跳转		
	    	}elseif($recharge_post->post_status =='private') {
	    	    
                header("Location:" . wnd_get_option('wndwp','wnd_pay_return_url') );
                exit;
                
	    	}
	    	
	    } else {
	        
	        echo "fail"; //订单不匹配		
	        $alipaySevice->writeLog('触发成功，订单金额不匹配');
	        
	    }    
    
	//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
else {
    //验证失败
    echo "验证失败";
}
