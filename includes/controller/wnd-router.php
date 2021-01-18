<?php
namespace Wnd\Controller;

use Exception;
use Wnd\Controller\Wnd_API;
use Wnd\Utility\Wnd_Singleton_Trait;

/**
 *@since 0.9.17
 *自定义伪静态路由地址，处理与外部的交互响应如：支付回调通知、微信公众号通讯等
 *
 *路径与对应类文件：
 * - /wnd-route/wnd_test  => Wnd\Endpoint\Wnd_Test
 * - /wnd-route/wndt_test => Wndt\Endpoint\Wndt_Test
 *（Endpoint 类相关响应应直接输出，而非返回值）
 */
class Wnd_Router {

	use Wnd_Singleton_Trait;

	public static $rule = [
		'prefix' => 'wnd-route',
		'rule'   => 'wnd-route/([0-9a-zA-Z_-]*)?$',
		'query'  => 'index.php?router=wnd&endpoint=$matches[1]',
	];

	private function __construct() {
		add_action('init', [__CLASS__, 'add_rewrite_rule']);
		add_action('parse_request', [__CLASS__, 'handle_request']);
	}

	/**
	 *@since 0.9.17
	 *自定义伪静态地址，处理第三方平台交互、执行 Action、渲染 Module
	 *
	 *@link https://wordpress.stackexchange.com/questions/334641/add-rewrite-rule-to-point-to-a-file-on-the-server
	 */
	public static function add_rewrite_rule() {
		add_rewrite_rule(static::$rule['rule'], static::$rule['query'], 'top');
	}

	/**
	 *获取指定 Endpoint 绝对路由 URL
	 */
	public static function get_route_url(string $endpoint): string {
		return home_url(static::$rule['prefix'] . '/' . $endpoint);
	}

	/**
	 *判断当前请求是否匹配本路由
	 */
	public static function handle_request(\WP $wp) {
		if ($wp->matched_rule != static::$rule['rule']) {
			return false;
		}

		$endpoint = explode(static::$rule['prefix'] . '/', $wp->request)[1] ?? '';
		static::handle_endpoint($endpoint);
		exit();
	}

	/**
	 *@since 0.9.17
	 *根据查询参数判断是否为自定义伪静态接口，从而实现输出重写
	 */
	protected static function handle_endpoint(string $endpoint) {
		// 解析实际类名称及参数
		$class = Wnd_API::parse_class($endpoint, 'Endpoint');

		// 执行 Endpoint 类
		try {
			new $class();
		} catch (Exception $e) {
			header('Content-Type:text/plain; charset=UTF-8');
			echo $e->getMessage();
		}
	}
}
