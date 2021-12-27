<?php
namespace Wnd\Action\User;

use Exception;
use Wnd\Action\Wnd_Action_User;

/**
 * 解除账户绑定
 * - QQ 等第三方社交登录账户
 * - 不含手机及邮箱
 *
 * @since 0.9.4
 */
class Wnd_Unbind_Openid extends Wnd_Action_User {

	private $type;

	protected function execute(): array{
		// 解除绑定
		if (!wnd_delete_user_openid($this->user_id, $this->type)) {
			throw new Exception(__('解绑失败，请稍后重试', 'wnd'));
		}

		return ['status' => 8, 'msg' => __('已解除绑定', 'wnd') . ':' . strtoupper($this->type)];
	}

	protected function check() {
		$this->type = $this->data['type'] ?? '';
		$user_pass  = $this->data['_user_user_pass'];
		$wnd_user   = (array) wnd_get_wnd_user($this->user->ID);

		/**
		 * 如果当前账户未绑定邮箱、手机、或其他第三方账户，则不允许解绑最后一个绑定
		 * $wnd_user 包含属性 $wnd_user->user_id 故判断条件为: <= 2
		 */
		if (count($wnd_user) <= 2) {
			throw new Exception(__('当前账户不可解绑', 'wnd'));
		}

		if (!$this->type) {
			throw new Exception(__('未指定解绑类型', 'wnd'));
		}

		// 密码校验
		if (!wp_check_password($user_pass, $this->user->data->user_pass, $this->user->ID)) {
			throw new Exception(__('密码错误', 'wnd'));
		}
	}
}
