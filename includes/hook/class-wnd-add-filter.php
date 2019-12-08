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
		add_filter('wnd_can_reg', array(__CLASS__, 'filter_can_reg'), 10, 1);
		add_filter('wnd_insert_post_status', array(__CLASS__, 'filter_post_status'), 10, 3);
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
			return array('status' => 0, 'msg' => '站点已关闭注册');
		}

		// 验证:手机或邮箱 验证码
		$auth_code      = $_POST['auth_code'];
		$email_or_phone = $_POST['phone'] ?? $_POST['_user_user_email'] ?? '';
		try {
			$auth = new Wnd_Auth;
			$auth->set_type('register');
			$auth->set_auth_code($auth_code);
			$auth->set_email_or_phone($email_or_phone);
			$auth->verify();
			return $can_array;
		} catch (Exception $e) {
			return array('status' => 0, 'msg' => $e->getMessage());
		}
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
