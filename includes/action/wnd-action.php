<?php
namespace Wnd\Action;

use Wnd\Utility\Wnd_Request;
use WP_REST_Request;

/**
 * Ajax 操作基类
 * @since 2019.10.02
 */
abstract class Wnd_Action {

	/**
	 * Post Data Array
	 * @since 0.8.66
	 */
	protected $data = [];

	/**
	 * 当前用户 Object
	 */
	protected $user;

	/**
	 * 当前用户 ID Int
	 */
	protected $user_id;

	/**
	 * 解析表单数据时，是否验证表单签名
	 */
	protected $verify_sign = true;

	/**
	 * 解析表单数据时，是否进行人机验证（如果存在）
	 */
	protected $validate_captcha = true;

	/**
	 * Instance of Wnd_Request
	 */
	protected $request;

	/**
	 * 构造
	 * - 校验请求数据
	 * - 核查权限许可
	 * @since 0.8.66
	 */
	public function __construct(WP_REST_Request $wp_rest_request) {
		$this->request = new Wnd_Request($wp_rest_request, $this->verify_sign, $this->validate_captcha);
		$this->data    = $this->request->get_request();
		$this->user    = wp_get_current_user();
		$this->user_id = $this->user->ID ?? 0;

		$this->check();
	}

	/**
	 * 权限检测
	 * @since 0.8.66
	 */
	protected function check() {
		return true;
	}

	/**
	 * 执行
	 */
	abstract public function execute(): array;
}
