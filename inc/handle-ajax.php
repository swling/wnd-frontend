<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *form表单规则：
 *input action : wnd_action
 *input action = wp_nonce_field('action', '_ajax_nonce')  = funcrion action()
 */
add_action('wp_ajax_wnd_action', 'wnd_ajax_action');
add_action('wp_ajax_nopriv_wnd_action', 'wnd_ajax_action');
function wnd_ajax_action() {

	$action = trim($_REQUEST['action']);

	// 请求的函数不存在
	if (!function_exists($action)) {
		$response = array('status' => 0, 'msg' => '未定义的ajax请求！');
		wp_send_json($response, $status_code = null);
	}

	//1、以_wnd_ 开头的函数为无需进行安全校验的函数
	if (strpos($action, '_wnd_') === 0) {

		$response = $action();

		// 2、常规函数操作 需要安全校验
	} else {

		check_ajax_referer($action);
		$response = $action();

	}

	// 发送json数据到前端
	wp_send_json($response, $status_code = null);

}

/**
 *@since 2019.04.07 测试API改造
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

function wnd_api_callback($request) {

	if (empty($_REQUEST) or !isset($_REQUEST['action'])) {
		return array('status' => 0, 'msg' => '未定义的API请求！');
	}

	$action = trim($_REQUEST['action']);

	// 请求的函数不存在
	if (!function_exists($action)) {
		return array('status' => 0, 'msg' => '无效的API请求！');
	}

	//1、以_wnd_ 开头的函数为无需进行安全校验的函数
	if (strpos($action, '_wnd_') === 0) {

		return $action();

		// 2、常规函数操作 需要安全校验
	} else {

		check_ajax_referer($action);
		return $action();

	}

}

/**
 * @since 2019.1.12
 * 依赖于 wnd_ajax_action
 * 响应函数必须以 _wnd_开头，形如：_wnd_template，此类函数应不包含敏感操作，通常仅作为前端响应界面
 * 典型应用：弹出登录框，弹出表单等
 * Ajax 请求：@see /static/js/wndwp.js ： wnd_ajax_modal()、wnd_ajax_embed()
 */
function _wnd_ajax_r() {

	// 匹配目标函数
	$template = $_REQUEST['template'];
	$function_name = '_wnd_' . $template;
	if (!function_exists($function_name)) {
		return array('status' => 0, 'msg' => '未定义的模板请求！');
	}

	// 获取参数并执行相关函数
	return $function_name($_REQUEST['param']);

	// 必须
	wp_die('', '', array('response' => null));
}

/**
 *@since 2019.02.19 在当前位置自动生成一个容器，以供ajax嵌入模板
 *@param $template string  被调用函数去除'_wnd_'前缀后的字符
 *@param $args array or string 传递给被调用模板函数的参数
 */
function _wnd_ajax_embed($template, $args = '') {

	$function_name = '_wnd_' . $template;
	if (!function_exists($function_name)) {
		return;
	}

	$div_id = 'wnd_' . $template;
	$args = wp_parse_args($args);
	$ajax_args = http_build_query($args);

	echo '<div id="' . $div_id . '">';
	echo '<script>wnd_ajax_embed(\'#' . $div_id . '\',\'' . $template . '\',\'' . $ajax_args . '\')</script>';
	echo '</div>';
}
