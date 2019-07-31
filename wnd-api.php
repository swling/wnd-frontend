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
			'callback' => 'wnd_rest_api_callback',
		)
	);

	register_rest_route(
		'wnd',
		'filter',
		array(
			'methods' => 'GET',
			'callback' => 'wnd_filter_api_callback',
		)
	);
}

/**
 *@since 2019.04.07
 *@param $_REQUEST['_ajax_nonce'] 	string 		wp nonce校验
 *@param $_REQUEST['action']	 	string 		后端响应函数
 *@param $_REQUEST['param']		 	string 		模板响应函数传参
 */
function wnd_rest_api_callback($request) {

	if (empty($_REQUEST) or !isset($_REQUEST['action'])) {
		return array('status' => 0, 'msg' => '未定义的API请求！');
	}

	$action = trim($_REQUEST['action']);

	// 请求的函数不存在
	if (!function_exists($action)) {
		return array('status' => 0, 'msg' => '无效的API请求！');
	}

	/**
	 *1、以_wnd 开头的函数为无需进行安全校验的模板函数，
	 *为统一ajax请求规则，ajax类模板函数统一使用唯一的超全局变量$_REQUEST['param']传参
	 *若模板函数需要传递多个参数，请整合为数组形式纳入$_REQUEST['param']实现
	 *不在ajax请求中使用的模板函数则不受此规则约束
	 */
	if ('GET' == $_SERVER['REQUEST_METHOD']) {

		return $action($_REQUEST['param']);

		/**
		 * 2、常规函数操作 需要安全校验
		 *函数可能同时接收超全局变量和指定参数变量
		 *为避免混乱在ajax请求中，不接受指定传参，统一使用超全局变量传参
		 */
	} else {

		if (!wnd_verify_nonce($_REQUEST['_ajax_nonce'] ?? '', $action)) {
			return array('status' => 0, 'msg' => '安全校验失败！');
		}

		return $action();

	}

}

/**
 *@since 2019.07.31
 *多重筛选API
 **/
function wnd_filter_api_callback() {

	try {
		$filter = new Wnd_Filter;
	} catch (Exception $e) {
		return array('status' => 0, 'msg' => $e->getMessage());
	}

	$query = new WP_Query($filter->wp_query_args);

	$post_list = '';
	if ($query->have_posts()) {
		while ($query->have_posts()): $query->the_post();
			global $post;
			$post_list .= _wndbiz_post_list_tpl($post);
		endwhile;
		wp_reset_postdata(); //重置查询
	}

	return array(
		'status' => 1,
		'data' => array(
			'post_list' => $post_list,
			'wp_query_args' => $filter->wp_query_args,
			'taxonomies' => get_object_taxonomies($filter->wp_query_args['post_type'], $output = 'names'),
		),
	);
}
