<?php
use Wnd\View\Wnd_Filter;

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
			'callback' => 'wnd_filter_api_callback',
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
 *@param $_GET['action'] 	string	后端响应函数
 *@param $_GET['param']		string	模板响应函数传参
 *
 *在存在自动加载的环境中 function_exists() 效率远高于 class_exists()
 *因此优先检测 '_wnd'前缀的模板函数，其次查询模板类
 *
 */
function wnd_interface_api_callback($request) {
	if (!isset($_GET['action'])) {
		return array('status' => 0, 'msg' => '未定义UI响应！');
	}
	$action = trim($_GET['action']);
	$param  = $_GET['param'] ?? '';

	/**
	 *以_wnd 开头的函数为无需进行安全校验的模板函数，
	 *为统一ajax请求规则，ajax类模板函数统一使用唯一的超全局变量$_GET['param']传参
	 *若模板函数需要传递多个参数，请整合为数组形式纳入$_GET['param']实现
	 *不在ajax请求中使用的模板函数则不受此规则约束
	 */
	if (strpos($action, '_wnd') === 0 and function_exists($action)) {
		return $action($param);
	}

	/**
	 *@since 2019.10.01
	 *为实现惰性加载，本插件使用模板类
	 */
	$class = 'Wnd\\Template\\' . $action;
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
 *@param $_REQUEST['action']	 	string 	后端响应函数
 *
 *在存在自动加载的环境中 function_exists() 效率远高于 class_exists()
 *因此优先检测 'wnd'前缀的函数，其次查询控制类
 */
function wnd_rest_api_callback($request) {
	if (!isset($_REQUEST['action'])) {
		return array('status' => 0, 'msg' => '未指定API响应！');
	}
	$action = trim($_REQUEST['action']);

	// nonce校验
	if (!wnd_verify_nonce($_POST['_ajax_nonce'] ?? '', $action)) {
		return array('status' => 0, 'msg' => '安全校验失败！');
	}

	/**
	 *常规wnd前缀函数操作 需要安全校验
	 *函数可能同时接收超全局变量和指定参数变量
	 *为避免混乱在ajax请求中，不接受指定传参，统一使用超全局变量传参
	 */
	if (strpos($action, 'wnd') === 0 and function_exists($action)) {
		return $action();
	}

	/**
	 *@since 2019.10.01
	 *为实现惰性加载，本插件使用控制类
	 */
	$class = 'Wnd\\Controller\\' . $action;
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

/**
 *@since 2019.07.31
 *多重筛选API
 *
 *
 * @see Wnd_Filter: parse_url_to_wp_query() 解析$_GET规则：
 * type={post_type}
 * status={post_status}
 *
 * post字段
 * _post_{post_field}={value}
 *
 *meta查询
 * _meta_{key}={$meta_value}
 * _meta_{key}=exists
 *
 *分类查询
 * _term_{$taxonomy}={term_id}
 *
 * 其他查询（具体参考 wp_query）
 * $wp_query_args[$key] = $value;
 *
 **/
function wnd_filter_api_callback() {

	// 根据请求GET参数，获取wp_query查询参数
	try {
		$filter = new Wnd_Filter($is_ajax = true);
	} catch (Exception $e) {
		return array('status' => 0, 'msg' => $e->getMessage());
	}

	// 执行查询
	$filter->query();

	return array(
		'status' => 1,
		'data'   => array(
			'posts'             => $filter->get_posts(),

			/**
			 *@since 2019.08.10
			 *当前post type的主分类筛选项 约定：post(category) / 自定义类型 （$post_type . '_cat'）
			 *
			 *动态插入主分类的情况，通常用在用于一些封装的用户面板：如果用户内容管理面板
			 *常规筛选页面中，应通过add_taxonomy_filter方法添加
			 */
			'category_tabs'     => $filter->get_category_tabs(),
			'sub_taxonomy_tabs' => $filter->get_sub_taxonomy_tabs(),
			'related_tags_tabs' => $filter->get_related_tags_tabs(),
			'pagination'        => $filter->get_pagination(),
			'post_count'        => $filter->wp_query->post_count,

			/**
			 *当前post type支持的taxonomy
			 *前端可据此修改页面行为
			 */
			'taxonomies'        => get_object_taxonomies($filter->wp_query->query_vars['post_type'], 'names'),

			/**
			 *@since 2019.08.10
			 *当前post type的主分类taxonomy
			 *约定：post(category) / 自定义类型 （$post_type . '_cat'）
			 */
			'category_taxonomy' => $filter->category_taxonomy,

			/**
			 *在debug模式下，返回当前WP_Query查询参数
			 **/
			'query_vars'        => WP_DEBUG ? $filter->wp_query->query_vars : '请开启Debug',
		),
	);
}
