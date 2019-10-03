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
 *@param $_GET['action'] 	string	后端响应UI类名称
 *@param $_GET['namespace']	string 	类的命名空间，默认为：'Wnd\\Template'
 *@param $_GET['param']		string	模板类传参
 *
 */
function wnd_interface_api_callback($request) {
	if (!isset($_GET['action'])) {
		return array('status' => 0, 'msg' => '未定义UI响应！');
	}
	$action    = trim($_GET['action']);
	$namespace = $_GET['namespace'] ? stripslashes_deep($_GET['namespace']) : 'Wnd\\Template';
	$param     = $_GET['param'] ?? '';

	// 允许的命名空间
	$namespaces = apply_filters('wnd_interface_namespaces', array('Wnd\\Template'));
	if (!in_array($namespace, $namespaces)) {
		return array('status' => 0, 'msg' => '未经允许的命名空间！');
	}

	/**
	 *@since 2019.10.01
	 *为实现惰性加载，本插件使用模板类
	 */
	$class = $namespace . '\\' . $action;
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
 *@param $_REQUEST['namespace']	 	string 	类的命名空间，默认为：'Wnd\\Controller'
 *
 */
function wnd_rest_api_callback($request) {
	if (!isset($_REQUEST['action'])) {
		return array('status' => 0, 'msg' => '未指定API响应！');
	}
	$action    = trim($_REQUEST['action']);
	$namespace = $_REQUEST['namespace'] ? stripslashes_deep($_REQUEST['namespace']) : 'Wnd\\Controller';

	// 允许的命名空间
	$namespaces = apply_filters('wnd_rest_namespaces', array('Wnd\\Controller'));
	if (!in_array($namespace, $namespaces)) {
		return array('status' => 0, 'msg' => '未经允许的命名空间！');
	}

	// nonce校验：action与namespace
	if (!wnd_verify_nonce($_REQUEST['_ajax_nonce'] ?? '', $action . $namespace)) {
		return array('status' => 0, 'msg' => '安全校验失败！');
	}

	/**
	 *@since 2019.10.01
	 *为实现惰性加载，本插件使用控制类
	 */
	$class = $namespace . '\\' . $action;
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
