<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/*
/ #########################################################ajax

form表单规则：

input action : wnd_action
input handler = wp_nonce_field('handler', '_ajax_nonce')  = funcrion handler()

 */
add_action('wp_ajax_wnd_action', 'wnd_ajax_action');
add_action('wp_ajax_nopriv_wnd_action', 'wnd_ajax_action');
function wnd_ajax_action() {

	$handler = trim($_REQUEST['handler']);

	// 请求的函数不存在
	if (!function_exists($handler)) {
		$response = array('status' => 0, 'msg' => '未定义的ajax请求！');
		wp_send_json($response, $status_code = null);
	}

	//1、以_wnd_ 开头的函数为无需进行安全校验的函数
	if (strpos($handler, '_wnd_') === 0) {

		$response = $handler();

		// 2、常规函数操作 需要安全校验
	} else {

		check_ajax_referer($handler);
		$response = $handler();

	}

	// 发送json数据到前端
	wp_send_json($response, $status_code = null);

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
	$param = $_REQUEST['param'];
	$function_name($param);

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