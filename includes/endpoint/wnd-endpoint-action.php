<?php
namespace Wnd\Endpoint;

use Exception;
use ReflectionClass;

/**
 *@since 0.9.18
 *需要进行 Nonce 校验的 Endpoint
 */
abstract class Wnd_Endpoint_Action extends Wnd_Endpoint {
	/**
	 *权限检测 WP Nonce 校验
	 */
	protected function check() {
		$nonce  = $this->data['_wpnonce'] ?? '';
		$action = (new ReflectionClass(get_called_class()))->getShortName();
		$action = strtolower($action);

		if (!$nonce or !wp_verify_nonce($nonce, $action)) {
			throw new Exception(__('Nonce 校验失败', 'wnd'));
		}
	}
}
