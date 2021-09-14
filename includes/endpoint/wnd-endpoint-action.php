<?php
namespace Wnd\Endpoint;

use Exception;
use ReflectionClass;

/**
 * 需要进行 Nonce 校验的 Endpoint
 * @since 0.9.18
 */
abstract class Wnd_Endpoint_Action extends Wnd_Endpoint {

	protected static $nonce_name = '_wndnonce';

	/**
	 * 权限检测 WP Nonce 校验
	 */
	protected function check() {
		$nonce  = $this->data[static::$nonce_name] ?? '';
		$action = (new ReflectionClass(get_called_class()))->getShortName();
		$action = strtolower($action);

		if (!$nonce or !wp_verify_nonce($nonce, $action)) {
			throw new Exception('Endpoint' . __('Nonce 校验失败', 'wnd'));
		}
	}

	/**
	 * 组合生成需要 nonce 校验的 Endpoint 请求链接
	 */
	public static function build_request_url(string $endpoint, array $args): string{
		$args[static::$nonce_name] = wp_create_nonce($endpoint);
		$args['_wpnonce']          = wp_create_nonce('wp_rest');

		return add_query_arg($args, wnd_get_endpoint_url($endpoint));
	}
}
