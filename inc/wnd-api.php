<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.04.07 API改造
 */
add_action('rest_api_init', 'wnd_action_rest_register_route');
function wnd_action_rest_register_route() {
	register_rest_route(
		'wnd',
		'rest-api',
		array(
			'methods' => WP_REST_Server::ALLMETHODS,
			'callback' => 'wnd_api_callback',
		)
	);
}

/**
 *@since 2019.04.07
 *@param $_REQUEST['_ajax_nonce'] 	string 		wp nonce校验
 *@param $_REQUEST['action']	 	string 		后端响应函数
 *@param $_REQUEST['param']		 	string 		模板响应函数传参
 */
function wnd_api_callback($request) {

	if (empty($_REQUEST) or !isset($_REQUEST['action'])) {
		return array('status' => 0, 'msg' => '未定义的API请求！');
	}

	$action = trim($_REQUEST['action']);

	// 请求的函数不存在
	if (!function_exists($action)) {
		return array('status' => 0, 'msg' => '无效的API请求！');
	}

	//1、以_wnd 开头的函数为无需进行安全校验的模板函数，使用$_REQUEST['param']传参
	if (strpos($action, '_wnd') === 0) {

		return $action($_REQUEST['param']);

		// 2、常规函数操作 需要安全校验，常规ajax函数，请直接使用超全局变量传参
	} else {

		if (!wnd_verify_nonce($_REQUEST['_ajax_nonce'] ?? '', $action)) {
			return array('status' => 0, 'msg' => '安全校验失败！');
		}

		return $action();

	}

}
