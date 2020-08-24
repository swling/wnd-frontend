<?php
namespace Wnd\Model;

use Wnd\Admin\Wnd_Admin_Menus;
use Wnd\Controller\Wnd_API;
use Wnd\Hook\Wnd_Hook;
use Wnd\Model\Wnd_DB;
use Wnd\Utility\Wnd_language;
use Wnd\Utility\Wnd_Optimization;
use Wnd\Utility\Wnd_Singleton_Trait;
use Wnd\Utility\Wnd_Upgrader_Plugin_This;

/**
 *初始化 单例模式
 */
class Wnd_Init {

	use Wnd_Singleton_Trait;

	private function __construct() {
		// Init
		static::init();

		// 默认Hook
		Wnd_Hook::instance();

		// 数据库
		Wnd_DB::instance();

		// API
		Wnd_API::instance();

		// 优化
		Wnd_Optimization::instance();

		// 语言
		Wnd_language::instance();

		// function
		require WND_PATH . '/includes/function/inc-meta.php'; //数组形式储存 meta、option
		require WND_PATH . '/includes/function/inc-general.php'; //通用函数定义
		require WND_PATH . '/includes/function/inc-post.php'; //post相关自定义函数
		require WND_PATH . '/includes/function/inc-user.php'; //user相关自定义函数
		require WND_PATH . '/includes/function/inc-media.php'; //媒体文件处理函数
		require WND_PATH . '/includes/function/inc-finance.php'; //财务

		require WND_PATH . '/includes/function/tpl-general.php'; //通用模板

		// 管理后台
		if (is_admin()) {
			// 配置菜单
			new Wnd_Admin_Menus;

			// 检查更新
			new Wnd_Upgrader_Plugin_This;
		}
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
		 *前端移除WordPress默认的API
		 *如果网站设置了自定义的内容管理权限，必须禁止WordPress默认的管理接口
		 *
		 *在本插件环境中，仅超级管理员可能访问后台，故后台不做处理。以此保持WP相关功能正常，如古腾堡编辑器等
		 *
		 *@link https://stackoverflow.com/questions/42757726/disable-default-routes-of-wp-rest-api
		 *@link https://wpreset.com/remove-default-wordpress-rest-api-routes-endpoints/
		 */
		if (!is_admin()) {
			add_filter('rest_endpoints', function ($endpoints) {
				foreach ($endpoints as $route => $endpoint) {
					if (0 === stripos($route, '/wp/')) {
						unset($endpoints[$route]);
					}
				}
				unset($route, $endpoint);

				return $endpoints;
			});
		}
	}

	/**
	 *@since 2019.02.28 如不注册类型，直接创建pending状态post时，会有notice级别的错误
	 *@see wp-includes/post.php @3509
	 */
	public static function register_post_type() {

		/*充值记录*/
		$labels = [
			'name' => __('充值记录', 'wnd'),
		];
		$args = [
			'labels'      => $labels,
			'description' => __('充值', 'wnd'),
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
			'name' => __('订单记录', 'wnd'),
		];
		$args = [
			'labels'      => $labels,
			'description' => __('订单', 'wnd'),
			'public'      => false,
			'has_archive' => false,
			'query_var'   => false, //order 为wp_query的排序参数，如果查询参数中包含order排序，会导致冲突，此处需要注销
			'supports'    => ['title', 'author', 'editor'],
		];
		register_post_type('order', $args);

		/**
		 *站内信
		 *
		 *@date 2020.03.20
		 *参数解释：
		 *'public'              => true, 需要设置为公开，站内信才能使用固定连接打开
		 *'exclude_from_search' => true, 从搜索中排除，防止当指定查询post_type为any时，查询出站内信
		 */
		$labels = [
			'name' => __('站内信', 'wnd'),
		];
		$args = [
			'labels'              => $labels,
			'description'         => __('站内信', 'wnd'),
			'public'              => true,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'show_ui'             => false,
			'supports'            => ['title', 'author', 'editor', 'thumbnail', 'excerpt', 'comments', 'custom-fields'],
			'rewrite'             => ['slug' => 'mail', 'with_front' => false],
		];
		register_post_type('mail', $args);

		/*整站充值统计*/
		$labels = [
			'name' => __('充值统计', 'wnd'),
		];
		$args = [
			'labels'      => $labels,
			'description' => __('充值统计', 'wnd'),
			'public'      => false,
			'has_archive' => false,
			'supports'    => ['title', 'author', 'editor'],
		];
		register_post_type('stats-re', $args);

		/*整站消费统计*/
		$labels = [
			'name' => __('消费统计', 'wnd'),
		];
		$args = [
			'labels'      => $labels,
			'description' => __('消费统计', 'wnd'),
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
		// 订单功能类 Post Status公共属性
		$transaction_common_args = [
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => false,
		];

		$post_statuses = [
			/**
			 *@since 2019.03.01 注册自定义状态，用于功能型post
			 *wp_insert_post可直接写入未经注册的post_status
			 *未经注册的post_status无法通过wp_query进行筛选，故此注册
			 **/
			'wnd-processing' => $transaction_common_args,
			'wnd-pending'    => $transaction_common_args,
			'wnd-completed'  => $transaction_common_args,
			'wnd-refunded'   => $transaction_common_args,
			'wnd-cancelled'  => $transaction_common_args,

			/**
			 *@since 2019.05.31 注册自定义状态：closed 用于关闭文章条目，但前端可以正常浏览
			 *wp_insert_post可直接写入未经注册的post_status
			 *未经注册的post_status无法通过wp_query进行筛选，故此注册
			 **/
			'wnd-closed'     => [
				'label'                     => __('关闭', 'wnd'),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => false,
			],
		];

		foreach ($post_statuses as $post_status => $values) {
			register_post_status($post_status, $values);
		}
	}
}
