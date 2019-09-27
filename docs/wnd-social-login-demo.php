<?php
/**
 *QQ社交登录
 */

// 创建第三方平台授权登录链接
$return_url = home_url('ucenter?type=qq');

$qq_login = new Wnd_QQ_Login();
$qq_login->set_app_id('qq_appid');
echo $qq_login->build_oauth_url($return_url);

// 在授权回调页面登录
if (isset($_GET['type']) and 'qq' == $_GET['type']) {
	try {
		$qq_login = new Wnd_QQ_Login();
		$qq_login->set_app_id('qq_appid');
		$qq_login->set_app_key('qq_appkey');
		$qq_login->login();
	} catch (Exception $e) {
		wp_die($e->getMessage(), bloginfo('name'));
	}
}
