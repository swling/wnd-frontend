<?php
namespace Wnd\Controller;

use Exception;
use Wnd\Utility\Wnd_Singleton_Trait;
use Wnd\View\Wnd_Filter;
use Wnd\View\Wnd_Filter_User;

/**
 *@since 2019.04.07 API改造
 *
 * # 主题或插件可拓展 Action、Module、Jsonget 详情参见：
 * - @see docs/api.md
 * - @see docs/autoloader.md
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
			'handler',
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
		// 移除WP自动转义(必须，WordPress不管$ _magic_quotes_gpc（）返回什么，都会在$ _POST / $ _ GET / $ _ REQUEST / $ _ COOKIE中添加斜杠)
		$class = stripslashes_deep($class);

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
	 *@param $_GET['module'] 	string	后端响应模块类
	 *
	 */
	public static function handle_interface(): array{
		if (!isset($_GET['module'])) {
			return ['status' => 0, 'msg' => __('未指定UI', 'wnd')];
		}

		// 解析实际类名称及参数
		$class = static::parse_class($_GET['module'], 'Module');

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
	 *@param $_GET['data'] 	string	后端响应
	 *
	 */
	public static function handle_jsonget(): array{
		if (!isset($_GET['data'])) {
			return ['status' => 0, 'msg' => __('未指定Data', 'wnd')];
		}

		// 解析实际类名称及参数
		$class = static::parse_class($_GET['data'], 'JsonGet');

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
	 *@param $_POST['_ajax_nonce'] 	string 	wp nonce校验
	 *@param $_POST['action']	 	string 	后端响应类
	 *
	 */
	public static function handle_action(): array{
		if (!isset($_POST['action'])) {
			return ['status' => 0, 'msg' => __('未指定Action', 'wnd')];
		}

		// 解析实际类名称
		$class = static::parse_class($_POST['action'], 'Action');

		// nonce校验：action
		if (!wp_verify_nonce($_POST['_ajax_nonce'] ?? '', $_POST['action'])) {
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
	 *@since 2019.07.31
	 *多重筛选 API
	 *
	 *@since 2019.10.07 OOP改造
	 *常规情况下，controller 应将用户请求转为操作命令并调用 model 处理，但 Wnd\View\Wnd_Filter 是一个完全独立的功能类
	 *Wnd\View\Wnd_Filter 既包含了生成筛选链接的视图功能，也包含了根据请求参数执行对应 WP_Query 并返回查询结果的功能，且两者紧密相关不宜分割
	 *可以理解为，Wnd\View\Wnd_Filter 是通过生成一个筛选视图，发送用户请求，最终根据用户请求，生成新的视图的特殊类：
	 *视图<->控制<->视图
	 *
	 * @see Wnd_Filter: parse_url_to_wp_query() 解析 $_GET 规则：
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
	public static function handle_posts(): array{
		/**
		 *Post模板函数可能包含反斜杠（如命名空间）故需移除WP自带的转义
		 *@since 2019.12.18
		 *
		 */
		$_GET = stripslashes_deep($_GET);

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

	/**
	 *@since 2020.05.05
	 *User 筛选 API
	 *
	 */
	public static function handle_users(): array{
		/**
		 *模板函数可能包含反斜杠（如命名空间）故需移除WP自带的转义
		 *@since 2019.12.18
		 *
		 */
		$_GET = stripslashes_deep($_GET);

		// 根据请求GET参数，获取wp_query查询参数
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
