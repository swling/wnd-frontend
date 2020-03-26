<?php
namespace Wnd\Hook;

use Exception;
use Wnd\Model\Wnd_Auth;

/**
 *Wnd Filter
 */
class Wnd_Add_Filter {

	private static $instance;

	private function __construct() {
		add_filter('wnd_can_reg', [__CLASS__, 'filter_can_reg'], 10, 1);
		add_filter('wnd_can_update_profile', [__CLASS__, 'filter_can_update_profile'], 10, 1);
		add_filter('wnd_insert_post_status', [__CLASS__, 'filter_post_status'], 10, 3);
	}

	/**
	 *单例模式
	 */
	public static function instance() {
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 *@since 2019.01.22
	 *检测当前信息是否可以注册新用户
	 */
	public static function filter_can_reg($can_array) {
		if (!get_option('users_can_register')) {
			return ['status' => 0, 'msg' => __('站点已关闭注册', 'wnd')];
		}

		// 验证:手机或邮箱 验证码
		$auth_code      = $_POST['auth_code'];
		$email_or_phone = $_POST['phone'] ?? $_POST['_user_user_email'] ?? '';
		try {
			$auth = Wnd_Auth::get_instance($email_or_phone);
			$auth->set_type('register');
			$auth->set_auth_code($auth_code);
			$auth->verify();
			return $can_array;
		} catch (Exception $e) {
			return ['status' => 0, 'msg' => $e->getMessage()];
		}
	}

	/**
	 *@since  2020.03.26
	 *
	 *用户显示昵称不得与登录名重复
	 */
	public static function filter_can_update_profile($can_array) {
		$display_name = $_POST['_user_display_name'] ?? '';
		$user_login   = wp_get_current_user()->data->user_login ?? '';
		if ($display_name == $user_login) {
			$can_array = ['status' => 0, 'msg' => __('名称不得与登录名一致', 'wnd')];
		}

		return $can_array;
	}

	/**
	 *@since 2019.02.13
	 *文章状态过滤，允许前端表单设置为草稿状态（执行顺序10，因而会被其他顺序晚于10的filter覆盖）
	 *如果 $update_id 为0 表示为新发布文章，否则为更新文章
	 */
	public static function filter_post_status($post_status, $post_type, $update_id) {
		// 允许用户设置为草稿
		if (isset($_POST['_post_post_status']) and $_POST['_post_post_status'] == 'draft') {
			return 'draft';
		}

		// 管理员通过
		if (wnd_is_manager()) {
			return 'publish';
		}

		// 已公开发布过的内容，再次编辑无需审核
		return (get_post_status($update_id) == 'publish') ? 'publish' : $post_status;
	}
}
