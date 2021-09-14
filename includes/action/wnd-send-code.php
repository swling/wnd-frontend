<?php
namespace Wnd\Action;

use Exception;
use Wnd\Model\Wnd_Auth;

/**
 * 发送手机或邮箱验证码
 * @since 2019.01.28
 */
class Wnd_Send_Code extends Wnd_Action {

	public function execute(): array{
		$type        = $this->data['type'] ?? '';
		$device      = $this->data['device'] ?? '';
		$device_type = $this->data['device_type'] ?? '';
		$device_name = $this->data['device_name'] ?? '';
		$template    = $this->data['template'] ?: wnd_get_config('sms_template_v');

		// 防止前端篡改表单：校验验证码类型及接受设备
		if (!wp_verify_nonce($this->data['type_nonce'], $device_type . $type)) {
			throw new Exception(__('Nonce校验失败', 'wnd'));
		}

		/**
		 * 已登录用户，且账户已绑定邮箱/手机，且验证类型不为bind（切换绑定邮箱）
		 * 核查当前表单字段与用户已有数据是否一致（验证码核验需要指定手机或邮箱，故此不可省略手机或邮箱表单字段）
		 */
		if ($this->user->ID and $type != 'bind') {
			$user_device = ('email' == $device_type) ? $this->user->user_email : wnd_get_user_phone($this->user->ID);
			if (!$user_device) {
				throw new Exception(__('当前账户未绑定', 'wnd') . $device_name);
			}

			if ($device != $user_device) {
				throw new Exception($device_name . __('与当前账户不匹配', 'wnd'));
			}
		}

		// 检测对应手机或邮箱格式：防止在邮箱绑定中输入手机号，反之亦然
		if (('email' == $device_type) and !is_email($device)) {
			throw new Exception(__('邮箱地址无效', 'wnd'));
		}
		if (('phone' == $device_type) and !wnd_is_mobile($device)) {
			throw new Exception(__('手机号码无效', 'wnd'));
		}

		// 发送权限过滤
		$can_send_code = apply_filters('wnd_can_send_code', ['status' => 1, 'msg' => '']);
		if (0 === $can_send_code['status']) {
			return $can_send_code;
		}

		$auth = Wnd_Auth::get_instance($device);
		$auth->set_type($type);
		$auth->set_template($template);
		$auth->send();
		return ['status' => 1, 'msg' => __('发送成功，请注意查收', 'wnd')];
	}
}
