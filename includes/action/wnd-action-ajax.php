<?php
namespace Wnd\Action;

use Wnd\Utility\Wnd_Form_Data;

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
	 *是否自动解析表单数据
	 */
	protected $parse_data = true;

	/**
	 *解析表单数据时，是否验证表单签名
	 */
	protected $verify_sign = true;

	/**
	 *构造
	 *@since 0.8.66
	 *
	 * - 校验请求数据
	 * - 核查权限许可
	 *
	 *@param bool $verify_sign 是否验证表单签名
	 */
	public function __construct(bool $verify_sign = true) {
		$this->verify_sign = $verify_sign;
		$this->data        = $this->parse_data ? Wnd_Form_Data::get_form_data($this->verify_sign) : $this->data;
		$this->user        = wp_get_current_user();
		$this->user_id     = $this->user->ID ?? 0;

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
