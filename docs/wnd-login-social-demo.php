<?php
use Wnd\Model\Wnd_Login_QQ;

/**
 *QQ社交登录
 */

// 统一的回调网址
$redirect_url = home_url('ucenter?type=qq');

// 创建第三方平台授权登录链接
$qq_login = new Wnd_Login_QQ();
$qq_login->set_app_id('qq_appid');
$qq_login->set_redirect_url($redirect_url);
echo $qq_login->build_oauth_url();

// 在授权回调页面登录
if (isset($_GET['type']) and 'qq' == $_GET['type']) {
	try {
		$qq_login = new Wnd_Login_QQ();
		$qq_login->set_app_id('qq_appid');
		$qq_login->set_app_key('qq_appkey');
		$qq_login->set_redirect_url($redirect_url);
		$qq_login->login();
	} catch (Exception $e) {
		wp_die($e->getMessage(), bloginfo('name'));
	}
}
