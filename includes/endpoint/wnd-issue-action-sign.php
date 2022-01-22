<?php
namespace Wnd\Endpoint;

use Exception;
use Wnd\Controller\Wnd_Request;
use Wnd\Endpoint\Wnd_Endpoint;

/**
 * ## 签发 Action Sign
 * - 请求方式：POST
 * - 本节点用于外部应用，如APP、小程序等请求 action 之前获取签名
 * - 本插件的大部分 Action 均需要对请求数据 key 序列事先签名，并随请求一起发送。在插件内部，表单构造时统一完成了签名 @see View\Wnd_Form_WP;
 * - 签名基于请求的数据键名，因此本节点事先约定了常见的签名允许的字段数组，超出字段约定范围的签名不会签发
 * - 用户可基于 filter 自行拓展签名字段范围
 * - GET 请求示范：_sign_type=payment&keys=["total_amount","type","payment_gateway"]
 *
 * @since 0.9.56
 */
class Wnd_Issue_Action_Sign extends Wnd_Endpoint {

	protected $content_type = 'json';

	/**
	 * Action 标识
	 */
	private $sign_type;

	/**
	 * 待签名的请求字段名
	 */
	private $sign_keys;

	/**
	 * 签发 Sign
	 */
	protected function do() {
		echo json_encode([Wnd_Request::$sign_name => Wnd_Request::sign($this->sign_keys)]);
	}

	/**
	 * 权限检测
	 */
	protected function check() {
		$this->sign_type = $this->data['_sign_type'] ?? '';
		$this->sign_keys = (array) ($this->data['keys'] ?? []);

		if (!$this->sign_type) {
			throw new Exception('Missing parameter [_sign_type]');
		}

		if (!$this->sign_keys) {
			throw new Exception('Missing signed field');
		}

		/**
		 * 根据类型设定默认允许的签名字段
		 * - 默认字段应充分考虑安全性，以保守为原则
		 */
		switch ($this->sign_type) {
			case 'payment':
				$allowed_keys = ['post_id', 'type', 'subject', 'total_amount', 'custom_amount', 'payment_gateway', 'app_id'];
				break;
			case 'profile':
				$allowed_keys = ['_usermeta_avatar', '_usermeta_avatar_url', '_user_display_name', '__wpusermeta_description', '_user_user_url'];
				break;

			default:
				$allowed_keys = [];
				break;
		}

		/**
		 * - 设置过滤器，允许在实际应用中拓展
		 * - 核查当前签名请求，是否在允许范围内
		 */
		$allowed_keys = apply_filters('wnd_allowed_sign_keys', $allowed_keys, $this->sign_type, $this->sign_keys);
		if (!$allowed_keys or array_intersect($this->sign_keys, $allowed_keys) != $this->sign_keys) {
			throw new Exception('The signature field name exceeds the allowable.');
		}
	}
}
