<?php
namespace Wnd\Action;

use Wnd\Utility\Wnd_Request;

/**
 *@since 2019.10.02
 *Ajax 操作基类
 */
abstract class Wnd_Action_Ajax {

	/**
	 *Post Data Array
	 *@since 0.8.66
	 */
	protected $data = [];

	/**
	 *当前用户 Object
	 */
	protected $user;

	/**
	 *当前用户 ID Int
	 */
	protected $user_id;

	/**
	 *解析表单数据时，是否验证表单签名
	 */
	protected $verify_sign = true;

	/**
	 *解析表单数据时，是否进行人机验证（如果存在）
	 */
	protected $validate_captcha = true;

	/**
	 * Instance of Wnd_Request
	 */
	protected $form_data;

	/**
	 *构造
	 *@since 0.8.66
	 *
	 * - 校验请求数据
	 * - 核查权限许可
	 *
	 */
	public function __construct() {
		$this->form_data = new Wnd_Request($this->verify_sign, $this->validate_captcha);
		$this->data      = $this->form_data->get_data();
		$this->user      = wp_get_current_user();
		$this->user_id   = $this->user->ID ?? 0;

		$this->check();
	}

	/**
	 *权限检测
	 *@since 0.8.66
	 */
	protected function check() {
		return true;
	}

	/**
	 *执行
	 */
	abstract public function execute(): array;
}
