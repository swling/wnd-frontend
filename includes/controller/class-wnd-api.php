<?php
namespace Wnd\Controller;

use Exception;
use Wnd\View\Wnd_Filter;
use WP_REST_Server;

/**
 *@since 2019.04.07 API改造
 */
class Wnd_API {

	private static $instance;

	private function __construct() {
		add_action('rest_api_init', [__CLASS__, 'register_route']);
	}

	/**
	 *单例模式
	 */
	public static function instance() {
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 *注册API
	 */
	public static function register_route() {
		// 数据处理
		register_rest_route(
			'wnd',
			'rest-api',
			[
				'methods'  => WP_REST_Server::ALLMETHODS,
				'callback' => __CLASS__ . '::handle_rest_api',
			]
		);

		// 多重筛选
		register_rest_route(
			'wnd',
			'filter',
			[
				'methods'  => 'GET',
				'callback' => __CLASS__ . '::handle_filter',
			]
		);

		// UI响应
		register_rest_route(
			'wnd',
			'interface',
			[
				'methods'  => 'GET',
				'callback' => __CLASS__ . '::handle_interface',
			]
		);
	}

	/**
	 *@since 2019.04.07
	 *UI响应
	 *@param $_GET['module'] 	string	后端响应模块类
	 *@param $_GET['param']		string	模板类传参
	 *
	 *@since 2019.10.04
	 *如需在第三方插件或主题拓展UI响应请定义类并遵循以下规则：
	 *1、类名称必须以wndt为前缀
	 *2、命名空间必须为：Wndt\Module
	 */
	public static function handle_interface() {
		if (!isset($_GET['module'])) {
			return ['status' => 0, 'msg' => '未定义UI响应'];
		}

		$class_name = stripslashes_deep($_GET['module']);
		$namespace  = (stripos($class_name, 'Wndt') === 0) ? 'Wndt\Module' : 'Wnd\Module';
		$class      = $namespace . '\\' . $class_name;
		$param      = $_GET['param'] ?? '';

		/**
		 *@since 2019.10.01
		 *为实现惰性加载，废弃函数支持，改用类
		 */
		if (is_callable([$class, 'build'])) {
			try {
				return $param ? $class::build($param) : $class::build();
			} catch (Exception $e) {
				return ['status' => 0, 'msg' => $e->getMessage()];
			}
		} else {
			return ['status' => 0, 'msg' => '无效的UI请求'];
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
	 *2、命名空间必须为：Wndt\Action
	 */
	public static function handle_rest_api(): array{
		if (!isset($_REQUEST['action'])) {
			return ['status' => 0, 'msg' => '未指定API响应'];
		}

		$class_name = stripslashes_deep($_REQUEST['action']);
		$namespace  = (stripos($class_name, 'Wndt') === 0) ? 'Wndt\Action' : 'Wnd\Action';
		$class      = $namespace . '\\' . $class_name;

		// nonce校验：action
		if (!wnd_verify_nonce($_REQUEST['_ajax_nonce'] ?? '', $_REQUEST['action'])) {
			return ['status' => 0, 'msg' => '安全校验失败'];
		}

		/**
		 *@since 2019.10.01
		 *为实现惰性加载，使用控制类
		 */
		if (is_callable([$class, 'execute'])) {
			try {
				return $class::execute();
			} catch (Exception $e) {
				return ['status' => 0, 'msg' => $e->getMessage()];
			}
		} else {
			return ['status' => 0, 'msg' => 'API请求不合规'];
		}
	}

	/**
	 *@since 2019.07.31
	 *多重筛选API
	 *
	 *@since 2019.10.07 OOP改造
	 *常规情况下，controller应将用户请求转为操作命令并调用model处理，但Wnd\View\Wnd_Filter是一个完全独立的功能类
	 *Wnd\View\Wnd_Filter既包含了生成筛选链接的视图功能，也包含了根据请求参数执行对应WP_Query并返回查询结果的功能，且两者紧密相关不宜分割
	 *可以理解为，Wnd\View\Wnd_Filter是通过生成一个筛选视图，发送用户请求，最终根据用户请求，生成新的视图的特殊类：
	 *视图<->控制<->视图
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
	public static function handle_filter(): array{

		// 根据请求GET参数，获取wp_query查询参数
		try {
			$filter = new Wnd_Filter(true);
		} catch (Exception $e) {
			return ['status' => 0, 'msg' => $e->getMessage()];
		}

		// 执行查询
		$filter->query();

		return [
			'status' => 1,
			'data'   => [
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
			],
		];
	}
}
