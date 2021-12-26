<?php
namespace Wnd\Action\Common;

use Exception;
use Wnd\Action\Wnd_Action;
use Wnd\Model\Wnd_Auth;

/**
 * 发送手机或邮箱验证码
 * @since 2019.01.28
 */
class Wnd_Send_Auth_Code extends Wnd_Action {

	private $device;
	private $type;
	private $template;

	protected function execute(): array{
		$auth = Wnd_Auth::get_instance($this->device);
		$auth->set_type($this->type);
		$auth->set_template($this->template);
		$auth->send();
		return ['status' => 1, 'msg' => __('发送成功，请注意查收', 'wnd')];
	}

	protected function check() {
		$this->type     = $this->data['type'] ?? '';
		$this->device   = $this->data['device'] ?? '';
		$this->template = $this->data['template'] ?: wnd_get_config('sms_template_v');
		$device_type    = $this->data['device_type'] ?? '';

		// 检测对应手机或邮箱格式：防止在邮箱绑定中输入手机号，反之亦然
		if ('email' == $device_type and !is_email($this->device)) {
			throw new Exception(__('邮箱地址无效', 'wnd'));
		}
		if ('phone' == $device_type and !wnd_is_mobile($this->device)) {
			throw new Exception(__('手机号码无效', 'wnd'));
		}

		// 发送权限过滤
		$can_send_code = apply_filters('wnd_can_send_auth_code', ['status' => 1, 'msg' => ''], $this->data);
		if (0 === $can_send_code['status']) {
			throw new Exception($can_send_code['msg']);
		}
	}
}
