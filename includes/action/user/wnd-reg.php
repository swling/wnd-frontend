<?php
namespace Wnd\Action\User;

use Exception;
use Wnd\Action\Wnd_Action;

/**
 * ajax user POST name规则：
 * user field：_user_{field}
 * user meta：
 * _usermeta_{key} （*自定义数组字段）
 * _wpusermeta_{key} （*WordPress原生字段）
 *
 * @since 初始化 用户注册
 */
class Wnd_Reg extends Wnd_Action {
	// 注册用户需设置防抖，防止用户短期重复提交写入
	public $period      = 5;
	public $max_actions = 1;

	private $user_data;
	private $user_meta_data;
	private $wp_user_meta_data;

	protected function execute(): array {
		// 写入新用户
		$user_id = wp_insert_user($this->user_data);
		if (is_wp_error($user_id)) {
			throw new Exception($user_id->get_error_message());
		}

		/**
		 * 注册完成
		 * - 由于注册采用了 json 数据，故此设置，以传递数据
		 * @since 0.9.37
		 */
		do_action('wnd_user_register', $user_id, $this->data);

		// 写入用户自定义数组meta
		if ($this->user_meta_data) {
			wnd_update_user_meta_array($user_id, $this->user_meta_data);
		}

		// 写入WordPress原生用户字段
		if ($this->wp_user_meta_data) {
			foreach ($this->wp_user_meta_data as $key => $value) {
				update_user_meta($user_id, $key, $value);
			}
			unset($key, $value);
		}

		// 用户注册完成，自动登录
		wp_set_current_user($user_id);
		wp_set_auth_cookie($user_id, true);
		$redirect_to  = $_REQUEST['redirect_to'] ?? wnd_get_reg_redirect_url();
		$return_array = apply_filters(
			'wnd_reg_return',
			['status' => 3, 'msg' => __('注册成功', 'wnd'), 'data' => ['redirect_to' => $redirect_to, 'user_id' => $user_id]],
			$user_id
		);
		return $return_array;
	}

	protected function check() {
		$user_can_reg = apply_filters('wnd_can_reg', ['status' => 1, 'msg' => ''], $this->data);
		if (0 === $user_can_reg['status']) {
			throw new Exception($user_can_reg['msg']);
		}
	}

	protected function parse_data() {
		$this->user_data               = $this->request->get_user_data();
		$this->user_data['user_login'] = $this->user_data['user_login'] ?? wnd_generate_login();
		$this->user_meta_data          = $this->request->get_user_meta_data();
		$this->wp_user_meta_data       = $this->request->get_wp_user_meta_data();

		// 检查表单数据
		static::check_data($this->user_data);
	}

	/**
	 * 检查数据
	 */
	private static function check_data(array $user_data) {
		if (isset($user_data['user_login'])) {
			if (strlen($user_data['user_login']) < 3) {
				throw new Exception(__('用户名不能低于3位', 'wnd'));
			}

			if (is_numeric($user_data['user_login'])) {
				throw new Exception(__('用户名不能是纯数字', 'wnd'));
			}
		}

		if (strlen($user_data['user_pass']) < 6) {
			throw new Exception(__('密码不能低于6位', 'wnd'));
		}

		if (isset($user_data['user_pass_repeat'])) {
			if ($user_data['user_pass_repeat'] !== $user_data['user_pass']) {
				throw new Exception(__('两次输入的密码不匹配', 'wnd'));
			}
		}
	}
}
