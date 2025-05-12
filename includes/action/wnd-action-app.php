<?php
namespace Wnd\Action;

use Exception;
use ReflectionClass;
use Wnd\Action\Wnd_Action;
use Wnd\Application\Wnd_App_abstract;

/**
 * Application 操作基类
 * - 无需验证签名
 * - 核查是否为付费应用
 * - 针对付费应用，操作完成后扣除费用
 */
abstract class Wnd_Action_App extends Wnd_Action {

	/**
	 * 本操作非标准表单请求，无需验证签名
	 */
	protected $verify_sign = false;

	/**
	 * App Post ID
	 */
	protected $app_id = 0;

	/**
	 * App 实例
	 *
	 */
	protected $app_instance;

	/**
	 * SKU ID
	 */
	protected $sku_id = '';

	/**
	 * 付费价格
	 */
	protected $action_price = 0;

	/**
	 * 执行
	 */
	abstract protected function execute(): array;

	/**
	 * 检测
	 * - 校验 App ID
	 * - 校验用户余额
	 */
	protected function check() {
		$this->parse_app_data();

		// 验证 App ID 与当前 Action 是否匹配，以确保 App ID 准确有效，从而确保付费价格的准确性
		$current_action = (new ReflectionClass(get_called_class()))->getShortName();
		if (strcasecmp($current_action, $this->app_instance->action)) {
			throw new Exception('当前请求Action 与 App ID 不匹配');
		}

		$this->check_payment();
	}

	/**
	 * 解析 Application 数据
	 * @since 2023.05.25
	 */
	private function parse_app_data() {
		$this->sku_id = $this->data['sku_id'] ?? '';
		$this->app_id = $this->data['app_id'] ?? 0;
		if (!$this->app_id) {
			throw new Exception('App ID 无效');
		}

		// 获取当前 Action 对应的 Application
		$app_name = Wnd_App_abstract::get_app_name($this->app_id);
		if (!$app_name) {
			throw new Exception('App ID 无效');
		}

		$this->app_instance = $app_name::get_instance();
		$this->action_price = $this->calculate_price();
	}

	/**
	 * 当前操作需要支付的费用
	 * - 之所以设置此方法，是因为不同的操作可能有不同的扣费模式，方便子类复写此方法
	 *
	 */
	protected function calculate_price(): float {
		return wnd_get_post_price($this->app_id, $this->sku_id, true);
	}

	/**
	 * 付费应用检测
	 */
	protected function check_payment() {
		if ($this->action_price <= 0) {
			return;
		}

		// 余额充足
		if ($this->check_balance()) {
			return;
		}

		// 匿名支付：此处 Exception Code 为 -1，旨在配合前端将信息弹窗显示给用户
		if (!$this->user_id) {
			$msg = '<p>请注意：匿名支付订单仅限当前设备 24 小时内使用<br/>如需多次使用，建议' .
			wnd_modal_link('<b>注册账户</b>', 'user/wnd_user_center') . '后充值使用！<p>';
		} else {
			$msg = '<p>余额不足，需扣费：¥' . $this->action_price . '/次</p>';
		}

		$payment_button = wnd_modal_button('充值：¥' . $this->action_price, 'common/wnd_recharge_form', ['amount' => $this->action_price], 'is-danger');
		throw new Exception('<div class="has-text-centered">' . $msg . $payment_button . '</div>', -1);
	}

	/**
	 * 检查余额
	 *
	 */
	private function check_balance(): bool {
		if ($this->user_id and wnd_get_user_balance($this->user_id) >= $this->action_price) {
			return true;
		}

		if (!$this->user_id and wnd_get_anon_user_balance() >= $this->action_price) {
			return true;
		}

		return false;
	}

	/**
	 * 完成
	 * - 单个购买：关闭订单
	 * - 充值应用：扣除消费额
	 */
	protected function complete() {
		// 免费应用
		if ($this->action_price <= 0) {
			return;
		}

		/**
		 * 余额支付
		 * - 登录用户：扣除余额/新增消费
		 * - 匿名用户：更新匿名充值订单消费额
		 */
		if ($this->user_id) {
			wnd_inc_user_balance($this->user_id, $this->action_price * -1, false);
		} else {
			wnd_inc_anon_user_expense($this->action_price);
		}
	}

}
