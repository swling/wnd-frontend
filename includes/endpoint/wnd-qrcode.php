<?php
namespace Wnd\Endpoint;

use Exception;

/**
 * 生成二维码图像
 * @since 2019.08.30
 */
class Wnd_Qrcode extends Wnd_Endpoint {

	protected function do() {
		$string = $this->data['string'] ?? '';
		if (!$string) {
			throw new Exception('字符串为空');
		}

		/**
		 * 防止盗链 $_SERVER['HTTP_REFERER'];
		 */

		header('Content-Type: image/png');
		require WND_PATH . '/includes/utility/phpqrcode.php';
		return \QRcode::png($string, false, 'Q', 7, 2);
	}
}
