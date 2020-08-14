<?php
namespace Wnd\Action;

use Exception;
use Wnd\Model\Wnd_Auth;

/**
 *@since 2019.01.28
 *发送手机或邮箱验证码
 */
class Wnd_Send_Code extends Wnd_Action_Ajax {

	public static function execute(): array{
		$type         = $_POST['type'] ?? '';
		$device       = $_POST['device'] ?? '';
		$device_type  = $_POST['device_type'] ?? '';
		$device_name  = $_POST['device_name'] ?? '';
		$template     = $_POST['template'] ?: wnd_get_config('sms_template_v');
		$captcha      = $_POST['captcha'] ?? '';
		$current_user = wp_get_current_user();

		// 防止前端篡改表单：校验验证码类型及接受设备
		if (!wp_verify_nonce($_POST['type_nonce'], $device_type . $type)) {
			return ['status' => 0, 'msg' => __('Nonce校验失败', 'wnd')];
		}

		/**
		 *已登录用户，且账户已绑定邮箱/手机，且验证类型不为bind（切换绑定邮箱）
		 *发送验证码给当前账户
		 */
		if ($current_user->ID and $type != 'bind') {
			$device = ('email' == $device_type) ? $current_user->user_email : wnd_get_user_phone($current_user->ID);
			if (!$device) {
				return ['status' => 0, 'msg' => __('当前账户未绑定', 'wnd') . $device_name];
			}
		}

		// 检测对应手机或邮箱格式：防止在邮箱绑定中输入手机号，反之亦然
		if (('email' == $device_type) and !is_email($device)) {
			return ['status' => 0, 'msg' => __('邮箱地址无效', 'wnd')];
		}
		if (('phone' == $device_type) and !wnd_is_mobile($device)) {
			return ['status' => 0, 'msg' => __('手机号码无效', 'wnd')];
		}

		// 发送权限过滤
		$can_send_code = apply_filters('wnd_can_send_code', ['status' => 1, 'msg' => ''], $device, $captcha);
		if (0 === $can_send_code['status']) {
			return $can_send_code;
		}

		try {
			$auth = Wnd_Auth::get_instance($device);
			$auth->set_type($type);
			$auth->set_template($template);
			$auth->send();
			return ['status' => 1, 'msg' => __('发送成功，请注意查收', 'wnd')];
		} catch (Exception $e) {
			return ['status' => 0, 'msg' => $e->getMessage()];
		}
	}
}
