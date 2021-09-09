<?php
namespace Wnd\Hook;

use Exception;
use Wnd\Permission\Wnd_FPC;
use Wnd\Permission\Wnd_PPC;
use Wnd\Utility\Wnd_Defender_User;
use Wnd\Utility\Wnd_Singleton_Trait;
use Wnd\Utility\Wnd_Validator;

/**
 * Wnd Filter
 */
class Wnd_Add_Filter {

	use Wnd_Singleton_Trait;

	private function __construct() {
		add_filter('wnd_can_reg', [__CLASS__, 'filter_can_reg'], 10, 1);
		add_filter('wnd_can_login', [__CLASS__, 'filter_can_login'], 10, 2);
		add_filter('wnd_can_update_profile', [__CLASS__, 'filter_can_update_profile'], 10, 1);
		add_filter('wnd_can_delete_user', [__CLASS__, 'filter_can_delete_user'], 10, 2);
		add_filter('wnd_insert_post_status', [__CLASS__, 'filter_post_status'], 10, 3);

		/**
		 * Post 权限
		 * @since 0.9.36
		 */
		add_filter('wnd_can_insert_post', [__CLASS__, 'filter_can_insert_post'], 11, 3);
		add_filter('wnd_can_update_post_status', [__CLASS__, 'filter_can_update_post_status'], 11, 3);

		/**
		 * 文件管理权限
		 * @since 0.9.6
		 */
		add_filter('wnd_can_upload_file', [__CLASS__, 'filter_upload_file'], 11, 3);
	}

	/**
	 * 检测当前信息是否可以注册新用户
	 * @since 2019.01.22
	 */
	public static function filter_can_reg($can_array) {
		if (!get_option('users_can_register')) {
			return ['status' => 0, 'msg' => __('站点已关闭注册', 'wnd')];
		}

		// 验证:手机或邮箱 验证码
		try {
			Wnd_Validator::validate_auth_code('register');
			return $can_array;
		} catch (Exception $e) {
			return ['status' => 0, 'msg' => $e->getMessage()];
		}
	}

	/**
	 * 登录权限
	 * @since 0.8.61
	 */
	public static function filter_can_login($can_array, $user) {
		// 账户已封禁
		if (wnd_has_been_banned($user->ID)) {
			return ['status' => 0, 'msg' => __('账户已被封禁', 'wnd')];
		}

		// Defender
		try {
			$defender = new Wnd_Defender_User($user->ID);
			$defender->check_login();

			return $can_array;
		} catch (Exception $e) {
			return ['status' => 0, 'msg' => $e->getMessage()];
		}
	}

	/**
	 * 用户显示昵称不得与登录名重复
	 * @since  2020.03.26
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
	 * 删除用户权限检测
	 * @since 0.8.64
	 */
	public static function filter_can_delete_user($can_array, $user_id) {
		$money = wnd_get_user_money($user_id);
		if (0 != $money) {
			$can_array = ['status' => 0, 'msg' => __('当前账户余额不为零：¥' . $money, 'wnd')];
		}

		return $can_array;
	}

	/**
	 * 文章状态过滤，允许前端表单设置为草稿状态（执行顺序10，因而会被其他顺序晚于10的filter覆盖）
	 * 如果 $update_id 为0 表示为新发布文章，否则为更新文章
	 * @since 2019.02.13
	 */
	public static function filter_post_status($post_status, $post_type, $update_id) {
		// 允许用户设置为草稿
		if ('draft' == ($_POST['_post_post_status'] ?? false)) {
			return 'draft';
		}

		// 管理员通过
		if (wnd_is_manager()) {
			return 'publish';
		}

		return $post_status;
	}

	/**
	 * 写入文章权限检测
	 * @since 0.9.36
	 */
	public static function filter_can_insert_post(array $can_array, string $post_type, $update_id): array{
		try {
			$ppc = Wnd_PPC::get_instance($post_type);
			$ppc->set_post_title($_POST['_post_post_title'] ?? ''); // 可能会校验标题是否重复

			// 定义更新：已指定post id，且排除自动草稿
			if ($update_id and get_post_status($update_id) != 'auto-draft') {
				$ppc->set_post_id($update_id);
				$ppc->check_update();
			} else {
				$ppc->check_insert();
			}
		} catch (Exception $e) {
			return ['status' => 0, 'msg' => $e->getMessage()];
		}

		return $can_array;
	}

	/**
	 * 是否可以更新状态
	 * @since 0.9.36
	 */
	public static function filter_can_update_post_status(array $can_array, object $before_post, string $after_status): array{
		try {
			$ppc = Wnd_PPC::get_instance($before_post->post_type);
			$ppc->set_post_id($before_post->ID);
			$ppc->set_post_status($after_status);
			$ppc->check_status_update();
		} catch (Exception $e) {
			return ['status' => 0, 'msg' => $e->getMessage()];
		}

		// 返回默认值
		return $can_array;
	}

	public static function filter_upload_file(array $can_array, int $post_parent, string $meta_key) {
		try {
			$upc = new Wnd_FPC();
			$upc->check_file_upload($post_parent, $meta_key);
		} catch (Exception $e) {
			return ['status' => 0, 'msg' => $e->getMessage()];
		}
		// 返回未经修改的默认值
		return $can_array;
	}
}
