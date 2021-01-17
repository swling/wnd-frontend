<?php
namespace Wnd\Controller;

use Exception;
use Wnd\Controller\Wnd_API;
use Wnd\Utility\Wnd_Singleton_Trait;

/**
 *@since 0.9.17
 *自定义伪静态地址，处理外部响应如：支付回调通知、微信公众号通讯等
 *取代原有 do.php 文件的部分功能
 *
 *路径与对应类文件：
 * - /wnd-route/wnd_test  => Wnd\Endpoint\Wnd_Test
 * - /wnd-route/wndt_test => Wndt\Endpoint\Wndt_Test
 *
 *Endpoint 类相关响应应直接输出，而非返回值
 */
class Wnd_Route {

	use Wnd_Singleton_Trait;

	public static $prefix = 'wnd-route';

	private function __construct() {
		add_action('init', [__CLASS__, 'add_rewrite_rule']);
		add_action('wp', [__CLASS__, 'handle_route']);
		add_filter('query_vars', [__CLASS__, 'filter_query_vars']);
	}

	/**
	 *@since 0.9.17
	 *自定义伪静态地址，处理第三方平台交互
	 */
	public static function add_rewrite_rule() {
		add_rewrite_rule(static::$prefix . '/([0-9a-zA-Z_-]*)?$', 'index.php?route=wnd&endpoint=$matches[1]', 'top');
	}

	/**
	 *@since 0.9.17
	 *新增查询参数，与自定义伪静态地址参数对应
	 */
	public static function filter_query_vars($query_vars) {
		$query_vars[] = 'route';
		$query_vars[] = 'endpoint';
		return $query_vars;
	}

	/**
	 *@since 0.9.17
	 *根据查询参数判断是否为自定义伪静态接口，从而实现输出重写
	 */
	public static function handle_route() {
		if ('wnd' != get_query_var('route')) {
			return false;
		}

		$endpoint = get_query_var('endpoint');
		if (!$endpoint) {
			exit(__('未指定 Endpoint', 'wnd'));
		}

		// 解析实际类名称及参数
		$class = Wnd_API::parse_class($endpoint, 'Route');

		// 执行 Endpoint 类
		try {
			new $class();
		} catch (Exception $e) {
			header('Content-Type:text/plain; charset=UTF-8');
			echo $e->getMessage();
		}

		// 结束请求
		exit();
	}
}
