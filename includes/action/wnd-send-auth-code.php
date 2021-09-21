<?php
namespace Wnd\Action;

use Exception;
use Wnd\Model\Wnd_Auth;

/**
 * 发送手机或邮箱验证码
 * @since 2019.01.28
 */
class Wnd_Send_Auth_Code extends Wnd_Action {

	public function execute(): array{
		$type        = $this->data['type'] ?? '';
		$device      = $this->data['device'] ?? '';
		$device_type = $this->data['device_type'] ?? '';
		$template    = $this->data['template'] ?: wnd_get_config('sms_template_v');

		// 检测对应手机或邮箱格式：防止在邮箱绑定中输入手机号，反之亦然
		if (('email' == $device_type) and !is_email($device)) {
			throw new Exception(__('邮箱地址无效', 'wnd'));
		}
		if (('phone' == $device_type) and !wnd_is_mobile($device)) {
			throw new Exception(__('手机号码无效', 'wnd'));
		}

		// 发送权限过滤
		$can_send_code = apply_filters('wnd_can_send_auth_code', ['status' => 1, 'msg' => '']);
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
