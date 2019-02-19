<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/*
/ #########################################################ajax

form表单规则：

input action : wnd_action
input action_name = wp_nonce_field('action_name', '_ajax_nonce')  = funcrion action_name()

 */
add_action('wp_ajax_wnd_action', 'wnd_ajax_action');
add_action('wp_ajax_nopriv_wnd_action', 'wnd_ajax_action');
function wnd_ajax_action() {

	$action_name = trim($_REQUEST['action_name']);

	// 请求的函数不存在
	if (!function_exists($action_name)) {
		$response = array('status' => 0, 'msg' => '未定义的ajax请求！');
		wp_send_json($response, $status_code = null);
	}

	//1、以_wnd_ 开头的函数为无需进行安全校验的函数
	if (strpos($action_name, '_wnd_') === 0) {

		$response = $action_name();

		// 2、常规函数操作 需要安全校验
	} elseif (function_exists($action_name)) {

		check_ajax_referer($action_name);
		$response = $action_name();

	}

	// 发送json数据到前端
	wp_send_json($response, $status_code = null);

}

/**
 * @since 2019.1.12
 * 依赖于 wnd_ajax_action
 * 响应函数必须以 _wnd_开头，形如：_wnd_handle，此类函数应不包含敏感操作，通常仅作为前端响应界面
 * 典型应用：弹出登录框，弹出表单等
 * Ajax 请求：@see /static/js/wndwp.js ： wnd_ajax_modal()、wnd_ajax_embed()
 */
function _wnd_ajax_r() {

	// 匹配目标函数
	$handle = $_REQUEST['handle'];
	$function_name = '_wnd_' . $handle;
	if (!function_exists($function_name)) {
		return array('status' => 0, 'msg' => '未定义的模板请求！');
	}

	// 获取参数并执行相关函数
	$param = $_REQUEST['param'];
	$function_name($param);

	// 必须
	wp_die('', '', array('response' => null));
}
