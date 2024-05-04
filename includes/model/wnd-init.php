<?php
namespace Wnd\Model;

use Wnd\Admin\Wnd_Admin_Optimization;
use Wnd\Admin\Wnd_Admin_Upgrade;
use Wnd\Admin\Wnd_Menus;
use Wnd\Controller\Wnd_Controller;
use Wnd\Hook\Wnd_Hook;
use Wnd\Model\Wnd_DB;
use Wnd\Utility\Wnd_CDN;
use Wnd\Utility\Wnd_Error_Handler;
use Wnd\Utility\Wnd_JWT_Handler;
use Wnd\Utility\Wnd_language;
use Wnd\Utility\Wnd_Optimization;
use Wnd\Utility\Wnd_OSS_Handler;
use Wnd\Utility\Wnd_Singleton_Trait;
use Wnd\Utility\Wnd_Upgrader_Plugin_This;

/**
 * 初始化 单例模式
 */
class Wnd_Init {

	use Wnd_Singleton_Trait;

	private function __construct() {
		// Error Handler
		Wnd_Error_Handler::get_instance();

		// function
		static::load_function_file();

		// Init
		static::init();

		// 默认Hook
		Wnd_Hook::get_instance();

		// 数据库
		Wnd_DB::get_instance();

		// API
		Wnd_Controller::get_instance();

		// JWT
		Wnd_JWT_Handler::get_instance();

		// 优化
		Wnd_Optimization::get_instance();

		// 语言切换
		if (wnd_get_config('enable_multi_language')) {
			Wnd_language::get_instance();
		}

		// OSS @since 0.9.29 需要用到自定义函数，故此必须在进入文件之后
		if (wnd_get_config('enable_oss')) {
			Wnd_OSS_Handler::get_instance();
		}

		// CDN @since 0.9.29 需要用到自定义函数，故此必须在进入文件之后
		if (wnd_get_config('enable_cdn')) {
			Wnd_CDN::get_instance();
		}

		// 管理后台
		if (is_admin()) {
			// 配置菜单
			new Wnd_Menus();

			// 检查更新
			new Wnd_Upgrader_Plugin_This();

			// 优化大数据库站点的 WP 后台
			if (wnd_get_config('enable_admin_optimization')) {
				Wnd_Admin_Optimization::get_instance();
			}
		}
	}

	// 加载函数封装文件
	private static function load_function_file() {
		require WND_PATH . '/includes/function/inc-general.php'; //通用函数定义
		require WND_PATH . '/includes/function/inc-meta.php'; //数组形式储存 meta、option
		require WND_PATH . '/includes/function/inc-post.php'; //post相关自定义函数
		require WND_PATH . '/includes/function/inc-user.php'; //user相关自定义函数
		require WND_PATH . '/includes/function/inc-media.php'; //媒体文件处理函数
		require WND_PATH . '/includes/function/inc-finance.php'; //财务
		require WND_PATH . '/includes/function/tpl-general.php'; //通用模板
	}

	// Init
	private static function init() {
		/**
		 * 插件安装卸载选项
		 * @since 初始化
		 */
		register_activation_hook(WND_PLUGIN_FILE, 'Wnd\Admin\Wnd_Admin_Install::install');
		register_deactivation_hook(WND_PLUGIN_FILE, 'Wnd\Admin\Wnd_Admin_Install::uninstall');

		/**
		 * 插件更新触发升级操作
		 * @since 0.9.2
		 */
		add_action('upgrader_process_complete', function ($upgrader_object, $options) {
			if ($options['action'] != 'update') {
				return false;
			}

			if ($options['type'] != 'plugin') {
				return false;
			}

			$current_plugin_path_name = plugin_basename(WND_PLUGIN_FILE);
			foreach ($options['plugins'] as $each_plugin) {
				if ($each_plugin == $current_plugin_path_name) {
					Wnd_Admin_Upgrade::upgrade();
					Wnd_language::transform_mo_to_php();
					break;
				}
			}
		}, 10, 2);

		/**
		 * 访问后台时候，触发执行升级及清理动作
		 * @since 2019.04.16
		 */
		add_action('admin_init', 'Wnd\Admin\Wnd_Admin_Upgrade::upgrade');
		add_action('admin_init', 'Wnd\Admin\Wnd_Admin_Clean_UP::clean_up');

		// 自定义文章类型及状态
		add_action('init', [__CLASS__, 'register_post_type']);
		add_action('init', [__CLASS__, 'register_post_status']);
	}

	/**
	 * @see wp-includes/post.php @3509
	 * @since 2019.02.28 如不注册类型，直接创建pending状态post时，会有notice级别的错误
	 */
	public static function register_post_type() {
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
	 * 注册自定义post status
	 *
	 */
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
			 * wp_insert_post可直接写入未经注册的post_status
			 * 未经注册的post_status无法通过wp_query进行筛选，故此注册
			 * @since 2019.05.31 注册自定义状态：closed 用于关闭文章条目，但前端可以正常浏览
			 */
			'wnd-closed' => [
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

	/**
	 * 获取财务类 post types
	 * @since 0.9.39
	 */
	public static function get_fin_types(): array {
		return apply_filters('wnd_fin_types', ['stats-re', 'stats-ex']);
	}

}
