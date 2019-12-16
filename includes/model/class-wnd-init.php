<?php
namespace Wnd\Model;

use Wnd\Controller\Wnd_API;
use Wnd\Hook\Wnd_Hook;
use Wnd\Model\Wnd_DB;
use Wnd\Model\Wnd_Optimization;

/**
 *初始化 单例模式
 */
class Wnd_Init {

	private static $instance;

	private function __construct() {
		// Init
		self::init();

		// 默认Hook
		Wnd_Hook::instance();

		// 数据库
		Wnd_DB::instance();

		// API
		Wnd_API::instance();

		// 优化
		Wnd_Optimization::instance();

		// function
		require WND_PATH . '/includes/function/inc-meta.php'; //数组形式储存 meta、option
		require WND_PATH . '/includes/function/inc-general.php'; //通用函数定义
		require WND_PATH . '/includes/function/inc-post.php'; //post相关自定义函数
		require WND_PATH . '/includes/function/inc-user.php'; //user相关自定义函数
		require WND_PATH . '/includes/function/inc-media.php'; //媒体文件处理函数
		require WND_PATH . '/includes/function/inc-finance.php'; //财务

		require WND_PATH . '/includes/function/tpl-general.php'; //通用模板
		require WND_PATH . '/includes/function/tpl-list.php'; //post list模板
		require WND_PATH . '/includes/function/tpl-term.php'; //term模板

		// 管理后台配置选项
		if (is_admin()) {
			require WND_PATH . '/wnd-options.php';
		}
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

	// Init
	private static function init() {
		// 自定义文章类型及状态
		add_action('init', [__CLASS__, 'register_post_type']);
		add_action('init', [__CLASS__, 'register_post_status']);

		/**
		 *@since 2019.10.08
		 *禁用xmlrpc
		 *如果网站设置了自定义的内容管理权限，必须禁止WordPress默认的管理接口
		 */
		add_filter('xmlrpc_enabled', '__return_false');

		/**
		 *移除WordPress默认的API
		 *如果网站设置了自定义的内容管理权限，必须禁止WordPress默认的管理接口
		 *
		 *@link https://stackoverflow.com/questions/42757726/disable-default-routes-of-wp-rest-api
		 *@link https://wpreset.com/remove-default-wordpress-rest-api-routes-endpoints/
		 */
		add_filter('rest_endpoints', function ($endpoints) {
			foreach ($endpoints as $route => $endpoint) {
				if (0 === stripos($route, '/wp/') or 0 === stripos($route, '/oembed/')) {
					unset($endpoints[$route]);
				}
			}

			return $endpoints;
		});
	}

	/**
	 *@since 2019.02.28 如不注册类型，直接创建pending状态post时，会有notice级别的错误
	 *@see wp-includes/post.php @3509
	 */
	public static function register_post_type() {

		/*充值记录*/
		$labels = [
			'name' => '充值记录',
		];
		$args = [
			'labels'      => $labels,
			'description' => '充值',
			'public'      => false,
			'has_archive' => false,
			'query_var'   => false,
			/**
			 *支持author的post type 删除用户时才能自动删除对应的自定义post
			 *@see wp-admin/includes/user.php @370
			 *@since 2019.05.05
			 */
			'supports'    => ['title', 'author', 'editor'],
		];
		register_post_type('recharge', $args);

		/*订单记录*/
		$labels = [
			'name' => '订单记录',
		];
		$args = [
			'labels'      => $labels,
			'description' => '订单',
			'public'      => false,
			'has_archive' => false,
			'query_var'   => false, //order 为wp_query的排序参数，如果查询参数中包含order排序，会导致冲突，此处需要注销
			'supports'    => ['title', 'author', 'editor'],
		];
		register_post_type('order', $args);

		/*站内信*/
		$labels = [
			'name' => '站内信',
		];
		$args = [
			'labels'      => $labels,
			'description' => '站内信',
			'public'      => true,
			'has_archive' => false,
			'show_ui'     => false,
			'supports'    => ['title', 'author', 'editor', 'thumbnail', 'excerpt', 'comments', 'custom-fields'],
			'rewrite'     => ['slug' => 'mail', 'with_front' => false],
		];
		register_post_type('mail', $args);

		/*整站充值统计*/
		$labels = [
			'name' => '充值统计',
		];
		$args = [
			'labels'      => $labels,
			'description' => '充值统计',
			'public'      => false,
			'has_archive' => false,
			'supports'    => ['title', 'author', 'editor'],
		];
		register_post_type('stats-re', $args);

		/*整站消费统计*/
		$labels = [
			'name' => '消费统计',
		];
		$args = [
			'labels'      => $labels,
			'description' => '消费统计',
			'public'      => false,
			'has_archive' => false,
			'supports'    => ['title', 'author', 'editor'],
		];
		register_post_type('stats-ex', $args);

	}

	/**
	 *注册自定义post status
	 **/
	public static function register_post_status() {

		/**
		 *@since 2019.03.01 注册自定义状态：success 用于功能型post
		 *wp_insert_post可直接写入未经注册的post_status
		 *未经注册的post_status无法通过wp_query进行筛选，故此注册
		 **/
		register_post_status('success', [
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => false,
		]);

		/**
		 *@since 2019.05.31 注册自定义状态：close 用于关闭文章条目，但前端可以正常浏览
		 *wp_insert_post可直接写入未经注册的post_status
		 *未经注册的post_status无法通过wp_query进行筛选，故此注册
		 **/
		register_post_status('close', [
			'label'                     => '关闭',
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => false,
		]);
	}
}
