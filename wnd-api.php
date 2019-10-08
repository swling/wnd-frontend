<?php
/**
 *@since 2019.04.07 API改造
 */
add_action('rest_api_init', 'wnd_action_rest_register_route');
function wnd_action_rest_register_route() {
	// 数据处理
	register_rest_route(
		'wnd',
		'rest-api',
		array(
			'methods'  => WP_REST_Server::ALLMETHODS,
			'callback' => 'wnd_rest_api_callback',
		)
	);

	// 多重筛选
	register_rest_route(
		'wnd',
		'filter',
		array(
			'methods'  => 'GET',
			'callback' => 'Wnd\\Controller\\Wnd_Ajax_Filter::filter',
		)
	);

	// UI响应
	register_rest_route(
		'wnd',
		'interface',
		array(
			'methods'  => 'GET',
			'callback' => 'wnd_interface_api_callback',
		)
	);
}

/**
 *@since 2019.04.07
 *UI响应
 *@param $_GET['action'] 	string	后端响应UI类名称
 *@param $_GET['param']		string	模板类传参
 *
 *@since 2019.10.04
 *如需在第三方插件或主题拓展UI响应请定义类并遵循以下规则：
 *1、类名称必须以wndt为前缀
 *2、命名空间必须为：Wndt\Module
 */
function wnd_interface_api_callback($request) {
	if (!isset($_GET['action'])) {
		return array('status' => 0, 'msg' => '未定义UI响应！');
	}

	$class_name = stripslashes_deep($_GET['action']);
	$namespace  = (stripos($class_name, 'Wndt') === 0) ? 'Wndt\\Module' : 'Wnd\\Module';
	$class      = $namespace . '\\' . $class_name;
	$param      = $_GET['param'] ?? '';

	/**
	 *@since 2019.10.01
	 *为实现惰性加载，废弃函数支持，改用类
	 */
	if (class_exists($class) and is_callable(array($class, 'build'))) {
		try {
			return $class::build($param);
		} catch (Exception $e) {
			return array('status' => 0, 'msg' => $e->getMessage());
		}
	} else {
		return array('status' => 0, 'msg' => '无效的UI请求！');
	}
}

/**
 *@since 2019.04.07
 *数据处理
 *@param $_REQUEST['_ajax_nonce'] 	string 	wp nonce校验
 *@param $_REQUEST['action']	 	string 	后端响应类
 *
 *@since 2019.10.04
 *如需在第三方插件或主题拓展控制器处理请定义类并遵循以下规则：
 *1、类名称必须以wndt为前缀
 *2、命名空间必须为：Wndt\Controller
 */
function wnd_rest_api_callback($request) {
	if (!isset($_REQUEST['action'])) {
		return array('status' => 0, 'msg' => '未指定API响应！');
	}

	$class_name = stripslashes_deep($_REQUEST['action']);
	$namespace  = (stripos($class_name, 'Wndt') === 0) ? 'Wndt\\Controller' : 'Wnd\\Controller';
	$class      = $namespace . '\\' . $class_name;

	// nonce校验：action
	if (!wnd_verify_nonce($_REQUEST['_ajax_nonce'] ?? '', $_REQUEST['action'])) {
		return array('status' => 0, 'msg' => '安全校验失败！');
	}

	/**
	 *@since 2019.10.01
	 *为实现惰性加载，使用控制类
	 */
	if (class_exists($class) and is_callable(array($class, 'execute'))) {
		try {
			return $class::execute();
		} catch (Exception $e) {
			return array('status' => 0, 'msg' => $e->getMessage());
		}
	} else {
		return array('status' => 0, 'msg' => 'API请求不合规！');
	}
}
