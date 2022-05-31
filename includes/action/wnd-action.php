<?php
namespace Wnd\Action;

use Wnd\Controller\Wnd_Defender_Action;
use Wnd\Controller\Wnd_Request;
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
	 * Instance of WP REST Request
	 * @since 0.9.36
	 */
	protected $wp_rest_request;

	/**
	 * Instance of Wnd_Request
	 */
	protected $request;

	/**
	 * 时间范围
	 * 与 $this->max_actions 结合，用于控制操作执行频次
	 */
	public $period;

	/**
	 * 最多执行
	 * 与 $this->period 结合，用于控制操作执行频次
	 */
	public $max_actions;

	/**
	 * 构造
	 * - 校验请求数据
	 * - 解析请求并定义类属性
	 * - 核查权限许可
	 * @since 0.8.66
	 */
	public function __construct(WP_REST_Request $wp_rest_request) {
		$this->wp_rest_request = $wp_rest_request;
		$this->request         = new Wnd_Request($wp_rest_request, $this->verify_sign, $this->validate_captcha);
		$this->data            = $this->request->get_request();
		$this->user            = wp_get_current_user();
		$this->user_id         = $this->user->ID ?? 0;

		$this->parse_data();
		$this->check();
	}

	/**
	 * 解析数据
	 * - 从请求数据中解析并定义类属性
	 * @since 0.9.57.7
	 */
	protected function parse_data() {}

	/**
	 * 权限检测
	 * @since 0.8.66
	 */
	protected function check() {}

	/**
	 * 封装执行
	 * @since 0.9.50
	 */
	final public function do_action(): array{
		// 防护
		$defender = new Wnd_Defender_Action($this);
		$defender->defend_action();

		// 执行
		$execute = $this->execute();
		$this->complete();

		// 执行成功
		$defender->write_log();

		// 响应
		$execute['time'] = timer_stop();
		return $execute;
	}

	/**
	 * 执行
	 */
	abstract protected function execute(): array;

	/**
	 * Action 执行完成后
	 * - 具体子类中执行成功后的相关后续操作，如扣费，扣积分等
	 * @since 0.9.50
	 */
	protected function complete() {}

	/**
	 * 获取当前操作类名称
	 * @since 0.9.50
	 */
	public static function get_class_name(): string{
		$class_name = get_called_class();
		return strtolower($class_name);
	}
}
