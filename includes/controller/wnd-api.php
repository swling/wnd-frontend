<?php
namespace Wnd\Controller;

use Exception;
use Wnd\Utility\Wnd_Singleton_Trait;
use Wnd\View\Wnd_Filter;
use Wnd\View\Wnd_Filter_User;
use WP_REST_Request;

/**
 *@since 2019.04.07 API改造
 * # 主题或插件可拓展 Action、Module、Jsonget 详情参见：
 * - @see docs/api.md
 * - @see docs/autoloader.md
 *
 * 注意：
 *	通过回调函数传参的数据是未经处理的原始数据（不会添加反斜线）。
 *	而 WordPress 环境中，默认的超全局变量 $_POST / $_GET / $_REQUEST 则是经过转义的数据（无论 PHP 配置）
 *	@link https://make.wordpress.org/core/2016/04/06/rest-api-slashed-data-in-wordpress-4-4-and-4-5/
 */
class Wnd_API {

	use Wnd_Singleton_Trait;

	private function __construct() {
		add_action('rest_api_init', [__CLASS__, 'register_route']);
	}

	/**
	 *注册API
	 */
	public static function register_route() {
		// 数据处理
		register_rest_route(
			'wnd',
			'action',
			[
				'methods'             => 'POST',
				'callback'            => __CLASS__ . '::handle_action',
				'permission_callback' => '__return_true',
			]
		);

		// Post 多重筛选
		register_rest_route(
			'wnd',
			'posts',
			[
				'methods'             => 'GET',
				'callback'            => __CLASS__ . '::handle_posts',
				'permission_callback' => '__return_true',
			]
		);

		// User 筛选
		register_rest_route(
			'wnd',
			'users',
			[
				'methods'             => 'GET',
				'callback'            => __CLASS__ . '::handle_users',
				'permission_callback' => '__return_true',
			]
		);

		// UI响应
		register_rest_route(
			'wnd',
			'interface',
			[
				'methods'             => 'GET',
				'callback'            => __CLASS__ . '::handle_interface',
				'permission_callback' => '__return_true',
			]
		);

		// Json数据输出
		register_rest_route(
			'wnd',
			'jsonget',
			[
				'methods'             => 'GET',
				'callback'            => __CLASS__ . '::handle_jsonget',
				'permission_callback' => '__return_true',
			]
		);

		// 自定义 Endpoint 响应第三方平台请求：响应数据格式不限于 json 格式
		register_rest_route(
			'wnd',
			'route/(?P<route>[a-zA-Z0-9-_]+)',
			[
				'methods'             => ['GET', 'POST'],
				'callback'            => __CLASS__ . '::handle_route',
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 *解析前端发送的类标识，返回包含完整命名空间的真实类名
	 *
	 *因拓展插件不具唯一性，因此加载插件中的拓展类需要添加插件名称
	 *parse_class('Wndt_File_Import\\Wndt_Demo', 'Module') 	=> Wnd_Plugin\Wndt_File_Import\Module\Wndt_Demo;
	 *parse_class('Wnd_Demo', 'Module') 					=> Wnd\Module\Wnd_Demo;
	 *parse_class('Wndt_Demo', 'Module') 					=> Wndt\Module\Wndt_Demo;
	 *
	 *其他 api 请求以此类推
	 *
	 *@see 自动加载机制 wnd-load.php
	 *
	 *@return string 包含完整命名空间的类名称
	 */
	public static function parse_class(string $class, string $api): string{
		/**
		 *拓展插件类请求格式：Wndt_File_Import\Wndt_Demo
		 *判断是否为拓展插件类，若是，则提取插件名称
		 */
		$class_info = explode('\\', $class, 2);
		if (isset($class_info[1])) {
			$plugin     = $class_info[0];
			$class_name = $class_info[1];
		} else {
			$plugin     = '';
			$class_name = $class_info[0];
		}

		/**
		 *解析类名称
		 *
		 * 插件：
		 * - 添加插件固定命名空间前缀：Wnd_Plugin
		 * - 添加插件名称
		 *
		 *本插件及主题：
		 * - 提取类名称前缀作为命名空间前缀
		 *
		 *拼接完整类名称：
		 * - 添加API接口
		 * - 添加类名称
		 *最终拼接成具有完整命名空间的实际类名称
		 */
		if ($plugin) {
			$real_class = 'Wnd_Plugin' . '\\' . $plugin . '\\' . $api . '\\' . $class_name;
		} else {
			$prefix     = explode('_', $class, 2)[0];
			$real_class = $prefix . '\\' . $api . '\\' . $class_name;
		}

		return $real_class;
	}

	/**
	 *@since 2019.04.07
	 *UI 响应
	 *
	 *@param $request  WP_REST_Request Object
	 */
	public static function handle_interface(WP_REST_Request $request): array{
		if (!isset($request['module'])) {
			return ['status' => 0, 'msg' => __('未指定UI', 'wnd')];
		}

		// 解析实际类名称及参数
		$class = static::parse_class($request['module'], 'Module');

		/**
		 *@since 2019.10.01
		 *为实现惰性加载，废弃函数支持，改用类
		 */
		if (!is_callable([$class, 'render'])) {
			return ['status' => 0, 'msg' => __('无效的UI', 'wnd') . ':' . $class];
		}

		try {
			return ['status' => 1, 'data' => $class::render()];
		} catch (Exception $e) {
			return ['status' => 0, 'msg' => $e->getMessage()];
		}
	}

	/**
	 *@since 2020.04.24
	 *获取 json data
	 *
	 *@param $request  WP_REST_Request Object
	 */
	public static function handle_jsonget(WP_REST_Request $request): array{
		if (!isset($request['data'])) {
			return ['status' => 0, 'msg' => __('未指定Data', 'wnd')];
		}

		// 解析实际类名称及参数
		$class = static::parse_class($request['data'], 'JsonGet');

		if (!is_callable([$class, 'get'])) {
			return ['status' => 0, 'msg' => __('无效的Json Data', 'wnd') . ':' . $class];
		}

		try {
			return ['status' => 1, 'msg' => '', 'data' => $class::get()];
		} catch (Exception $e) {
			return ['status' => 0, 'msg' => $e->getMessage()];
		}
	}

	/**
	 *@since 2019.04.07
	 *数据处理
	 *
	 *注意：
	 *	WordPress Rest API 回调函数的传参 $request 数据为原始数据，如直接使用 $request 数据执行数据库操作需要做数据清理。
	 *	因此在本插件，Action 层相关方法中，用户数据采用 Wnd\Utility\Wnd_Request 统一处理
	 * 	@see Wnd\Utility\Wnd_Request; Wnd\Action\Wnd_Action_Ajax
	 *
	 *@param $request  WP_REST_Request Object
	 */
	public static function handle_action(WP_REST_Request $request): array{
		if (!isset($request['action'])) {
			return ['status' => 0, 'msg' => __('未指定Action', 'wnd')];
		}

		// 解析实际类名称
		$class = static::parse_class($request['action'], 'Action');

		// nonce校验：action
		if (!wp_verify_nonce($request['_ajax_nonce'] ?? '', $request['action'])) {
			return ['status' => 0, 'msg' => __('Nonce校验失败', 'wnd')];
		}

		/**
		 *@since 2019.10.01
		 *为实现惰性加载，使用控制类
		 */
		if (!is_callable([$class, 'execute'])) {
			return ['status' => 0, 'msg' => __('无效的Action', 'wnd')];
		}

		try {
			$action = new $class();
			return $action->execute();
		} catch (Exception $e) {
			return ['status' => 0, 'msg' => $e->getMessage()];
		}
	}

	/**
	 *@since 0.9.17
	 *根据查询参数判断是否为自定义伪静态接口，从而实现输出重写
	 */
	public static function handle_route(WP_REST_Request $request) {
		if (!$request['route']) {
			return ['status' => 0, 'msg' => __('未指定 Route', 'wnd')];
		}

		// 解析实际类名称及参数
		$class = Wnd_API::parse_class($request['route'], 'route');

		// 执行 Endpoint 类
		try {
			new $class();
		} catch (Exception $e) {
			header('Content-Type:text/plain; charset=UTF-8');
			echo $e->getMessage();
		}
	}

	/**
	 *@since 2019.07.31
	 *多重筛选 API
	 *
	 *@since 2019.10.07 OOP改造
	 *常规情况下，controller 应将用户请求转为操作命令并调用 model 处理，但 Wnd\View\Wnd_Filter 是一个完全独立的功能类
	 *Wnd\View\Wnd_Filter 既包含了生成筛选链接的视图功能，也包含了根据请求参数执行对应 WP_Query 并返回查询结果的功能，且两者紧密相关不宜分割
	 *可以理解为，Wnd\View\Wnd_Filter 是通过生成一个筛选视图，发送用户请求，最终根据用户请求，生成新的视图的特殊类：
	 *视图<->控制<->视图
	 *
	 * @see Wnd_Filter: parse_url_to_wp_query() 解析规则：
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
	 *@param $request  WP_REST_Request Object
	 **/
	public static function handle_posts(WP_REST_Request $request): array{
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

	/**
	 *@since 2020.05.05
	 *User 筛选 API
	 *
	 *@param $request  WP_REST_Request Object
	 */
	public static function handle_users(WP_REST_Request $request): array{
		try {
			$filter = new Wnd_Filter_User(true);
		} catch (Exception $e) {
			return ['status' => 0, 'msg' => $e->getMessage()];
		}

		// 执行查询
		$filter->query();

		return [
			'status' => 1,
			'data'   => [
				'users'      => $filter->get_users(),
				'pagination' => $filter->get_pagination(),
			],
		];
	}
}
