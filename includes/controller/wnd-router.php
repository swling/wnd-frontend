<?php
namespace Wnd\Controller;

use Exception;
use Wnd\Controller\Wnd_Controller;
use Wnd\Utility\Wnd_Singleton_Trait;

/**
 *@since 0.9.17
 *自定义伪静态路由地址
 * - 主要用于处理非 Json 数据交互，如：支付回调通知、微信公众号通讯等
 * - 响应数据格式将在具体 Endpoint 类中定义
 * - 常规 Json 数据交互： @see Wnd\Controller\Wnd_Controller
 *
 *路径与对应类文件：
 * - 当前插件：	/wnd-route/wnd_test  				=> Wnd\Endpoint\Wnd_Test
 * - 当前主题：	/wnd-route/wndt_test 				=> Wndt\Endpoint\Wndt_Test
 * - 拓展插件：	/wnd-route/plugin_name/wndt_test 	=> Plugin_name\Endpoint\Wndt_Test
 *上述类均已实现自动加载 详情 @see wnd-autoloader.php
 */
class Wnd_Router {

	use Wnd_Singleton_Trait;

	public static $rule = [
		'prefix' => 'wnd-route',
		'rule'   => '(.*)?',
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
		add_rewrite_rule(static::$rule['prefix'] . '/' . static::$rule['rule'], static::$rule['query'], 'top');
		add_rewrite_rule(static::$rule['prefix'], 'index.php?router=wnd', 'top');
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
		if ($wp->matched_rule != static::$rule['prefix'] . '/' . static::$rule['rule'] and $wp->matched_rule != static::$rule['prefix']) {
			return false;
		}

		$endpoint = explode(static::$rule['prefix'] . '/', $wp->request)[1] ?? 'Wnd_Default';
		static::handle_endpoint($endpoint);
		exit();
	}

	/**
	 *@since 0.9.17
	 *根据查询参数判断是否为自定义伪静态接口，从而实现输出重写
	 */
	public static function handle_endpoint(string $endpoint) {
		// 解析实际类名称及参数
		$class = Wnd_Controller::parse_class($endpoint, 'Endpoint');
		if (!class_exists($class)) {
			static::send_text_msg(__('Endpoint 无效'));
			return;
		}

		// 执行 Endpoint 类
		try {
			new $class();
		} catch (Exception $e) {
			static::send_text_msg($e->getMessage());
		}
	}

	/**
	 *输出错误信息
	 */
	protected static function send_text_msg(string $msg) {
		header('Content-Type: text/plain; charset=' . get_option('blog_charset'));
		echo $msg;
	}
}
