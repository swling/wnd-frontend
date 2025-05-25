<?php
namespace Wnd\Endpoint;

use Wnd\Endpoint\Wnd_Endpoint;
use Wnd\Utility\Wnd_Wechat;

/**
 * 微信小程序带参二维码
 * @link https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/qrcode-link/qr-code/getUnlimitedQRCode.html
 */
class Wnd_MP_QRCode extends Wnd_Endpoint {

	private $app_id;

	private $secret;

	private $page;

	private $scene;

	protected function set_content_type() {
		header('Content-Type: image/jpeg; charset=' . get_option('blog_charset'));
	}

	protected function do() {
		$access_token = Wnd_Wechat::get_access_token($this->app_id, $this->secret);
		$url          = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$access_token}";
		$request      = wnd_remote_post($url, [
			'body' => json_encode(
				[
					'page'  => "pages/$this->page/$this->page",
					'scene' => $this->scene,
					'width' => 280,
				]
			),
		]);

		echo($request['body']);
	}

	protected function check() {
		$config       = json_decode(wnd_get_config('wechat_app'), true);
		$this->app_id = array_key_first($config);
		$this->secret = $config[$this->app_id] ?? '';

		$this->page  = $this->data['page'] ?? 'index';
		$this->scene = $this->data['scene'] ?? '';

		if (!$this->scene) {
			exit('Scene 无效');
		}
	}

}
