<?php
namespace Wnd\Hook;

use Exception;
use Wnd\Getway\Wnd_Captcha;
use Wnd\Model\Wnd_Transaction_Anonymous;
use Wnd\Utility\Wnd_Affiliate;
use Wnd\Utility\Wnd_Defender;
use Wnd\Utility\Wnd_Defender_User;
use Wnd\Utility\Wnd_Singleton_Trait;
use Wnd\Utility\Wnd_Validator;
use Wnd\WPDB\Wnd_Auth_DB;
use Wnd\WPDB\Wnd_Mail_DB;
use Wnd\WPDB\Wnd_Tag_Under_Category;
use Wnd\WPDB\Wnd_Transaction_DB;
use Wnd\WPDB\Wnd_User_DB;

/**
 * WP Action
 * @link https://developer.wordpress.org/apis/hooks/action-reference/
 */
class Wnd_Add_Action_WP {

	use Wnd_Singleton_Trait;

	private function __construct() {
		add_action('init', [__CLASS__, 'action_on_init'], 10);
		add_action('wp_loaded', [__CLASS__, 'action_on_wp_loaded'], 10);
		add_action('after_password_reset', [__CLASS__, 'action_on_password_reset'], 10, 1);
		add_action('deleted_user', [__CLASS__, 'action_on_delete_user'], 10, 1);
		add_action('before_delete_post', [__CLASS__, 'action_on_before_delete_post'], 10, 1);
		add_action('post_updated', [__CLASS__, 'action_on_post_updated'], 10, 3);
		add_action('add_attachment', [__CLASS__, 'action_on_add_attachment'], 10, 1);
		add_action('pre_get_posts', ['Wnd\View\Wnd_Filter_Query', 'action_on_pre_get_posts'], 10, 1);

		/**
		 * 匿名用户评论验证码，基于 WordPress 原生评论表单及 wp_handle_comment_submission 评论提交
		 * - 前端表单：@see comment_form(
		 * - 提交验证：@see wp_handle_comment_submission
		 * @since 0.8.73
		 */
		add_action('comment_form', [__CLASS__, 'action_on_comment_form'], 10, 1);
		add_action('pre_comment_on_post', [__CLASS__, 'action_on_pre_comment_on_post'], 10, 1);

		/**
		 * 分类关联标签
		 */
		Wnd_Tag_Under_Category::add_hook();

		/**
		 * 登录成功设置cookie之前，删除匿名订单的cookie
		 * do_action( 'set_auth_cookie', $auth_cookie, $expire, $expiration, $user_id, $scheme, $token );
		 * @since 0.9.37
		 */
		add_action('set_auth_cookie', [__CLASS__, 'action_before_login_success'], 10);

		/**
		 * WP 最后一个 Action
		 * - 记录WordPress非致命性错误日志
		 *
		 * @link https://developer.wordpress.org/reference/hooks/shutdown/
		 * @since 0.9.57.8
		 */
		add_action('shutdown', [__CLASS__, 'action_before_shutdown']);
	}

	/**
	 * init
	 * @link https://developer.wordpress.org/reference/hooks/init/
	 * @since 0.9.70
	 */
	public static function action_on_init() {
		// 登录：由推广注册而来
		if (is_user_logged_in()) {
			if (wnd_get_aff_cookie()) {
				Wnd_Affiliate::handle_aff_reg();
				wnd_delete_aff_cookie();
			}
			return;
		}

		// 匿名：存在推广参数
		if (isset($_GET[WND_AFF_KEY])) {
			wnd_set_aff_cookie();
		}
	}

	/**
	 * This action hook is fired once WordPress, all plugins, and the theme are fully loaded and instantiated.
	 * @link https://codex.wordpress.org/Plugin_API/Action_Reference/wp_loaded
	 * @since 2020.04.30
	 */
	public static function action_on_wp_loaded() {
		// 记录登录日志
		Wnd_User_DB::write_login_log();

		// 拦截封禁账户登录
		if (wnd_has_been_banned()) {
			wp_logout();
			wp_die('账户已被封禁', get_option('blogname'));
		}
	}

	/**
	 * 重设密码后
	 * @since 0.8.62
	 */
	public static function action_on_password_reset($user) {
		// Defender：重设密码后清空登录失败日志
		$defender = new Wnd_Defender_User($user->ID);
		$defender->reset_log();
	}

	/**
	 * 删除用户的附加操作
	 * @since 2018
	 */
	public static function action_on_delete_user($user_id) {
		// 删除自定义 wnd_users 表记录
		Wnd_User_DB::delete_wnd_user($user_id);

		// 删除自定义用户 wnd_auths 表记录
		Wnd_Auth_DB::delete_user_auths($user_id);

		// 删除用户交易表记录
		$handler = Wnd_Transaction_DB::get_instance();
		$handler->delete_by('user_id', $user_id);

		// 删除用户站内信表记录
		$handler = Wnd_Mail_DB::get_instance();
		$handler->delete_by('receiver', $user_id);

		// 删除用户评论（若不删除，对应评论列表会产生无法缓存的重复sql查询）
		$comments = get_comments(['user_id' => $user_id]);
		foreach ($comments as $comment) {
			wp_delete_comment($comment->comment_ID, true);
		}
	}

	/**
	 * 删除文章时附件操作
	 * 需要删除文章对应的子文章，需要定义在：before_delete_post，仅此时尚保留对应关系
	 * @since 2019.03.28
	 * @since 2019.10.20
	 */
	public static function action_on_before_delete_post($post_id) {
		$delete_post = get_post($post_id);

		/**
		 * 删除附属文件
		 * 此处需要删除所有子文章，如果设置为 any，自定义类型中设置public为false的仍然无法包含，故获取全部注册类型
		 */
		$args = [
			'posts_per_page' => -1,
			'post_type'      => get_post_types(),
			'post_status'    => 'any',
			'post_parent'    => $post_id,
		];

		// 获取并删除
		foreach (get_posts($args) as $child) {
			wp_delete_post($child->ID, true);
		}
		unset($child);

		// 删除自定义附件
		wnd_delete_attachment_by_post($post_id);
	}

	/**
	 * 文章更新
	 * @since 2019.06.05
	 */
	public static function action_on_post_updated($post_ID, $post_after, $post_before) {}

	/**
	 * do_action( 'add_attachment', $post_ID );
	 * 新增上传附件时
	 * @since 2019.07.18
	 */
	public static function action_on_add_attachment($post_ID) {
		$post = get_post($post_ID);

		/**
		 * 记录附件children_max_menu_order、删除附件时不做修改
		 * 记录值用途：读取后，自动给上传附件依次设置menu_order，以便按menu_order排序
		 * 典型场景：
		 * 删除某个特定附件后，需要新上传附件，并恢复原有排序。此时要求新附件menu_order与删除的附件一致
		 * 通过wnd_attachment_form()上传文件，并编辑menu_order即可达到上述要求
		 * @see wnd_filter_wp_insert_attachment_data
		 */
		if ($post->post_parent) {
			wnd_inc_wnd_post_meta($post->post_parent, 'attachment_records');
		}
	}

	/**
	 * 匿名用户提交评论验证码前端交互
	 * @since 0.8.73
	 */
	public static function action_on_comment_form($post_ID) {
		if (is_user_logged_in() or !wnd_get_config('captcha_service')) {
			return;
		}

		try {
			$captcha = Wnd_Captcha::get_instance();
			echo '<input type="hidden" name="' . Wnd_Captcha::$captcha_name . '">' . PHP_EOL;
			echo '<input type="hidden" name="' . Wnd_Captcha::$captcha_nonce_name . '">' . PHP_EOL;
			echo $captcha->render_submit_form_script();
		} catch (Exception $e) {
			echo '<!-- ' . $e->getMessage() . ' -->';
		}
	}

	/**
	 * 匿名用户提交评论验证码后端校验
	 * @since 0.8.73
	 */
	public static function action_on_pre_comment_on_post($post_ID) {
		if (is_user_logged_in() or !wnd_get_config('captcha_service')) {
			return;
		}

		/**
		 * 评论验证
		 * - rest api 提交 @see Controller\Wnd_Controller::add_comment();
		 * - 常规提交：@see /wp-comments-post.php
		 *
		 * 常规提交中缺失异常捕获，故此添加异常捕获
		 * @since 0.9.56
		 */
		try {
			Wnd_Validator::validate_captcha($_POST);
		} catch (Exception $e) {
			if (wnd_is_rest_request()) {
				throw new Exception($e->getMessage());
			} else {
				exit($e->getMessage());
			}
		}
	}

	/**
	 * 登录成功设置cookie之前，删除匿名订单的cookie
	 * @since 0.9.37
	 */
	public static function action_before_login_success() {
		if (!wnd_get_config('enable_anon_order')) {
			return;
		}

		Wnd_Transaction_Anonymous::delete_anon_cookie();
	}

	/**
	 * WP 生命周期结束前
	 * - 记录WordPress非致命性错误日志
	 * - 对已登录/含有匿名支付信息的用户移除防护统计
	 * @since 0.9.57.8
	 */
	public static function action_before_shutdown() {
		global $wpdb;
		if ($wpdb->last_error) {
			$error = $wpdb->last_error . '@ Request from ' . wnd_get_user_ip() . '. @' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			wnd_error_log($error, 'wnd_wpdb_error');
		}

		if (is_user_logged_in() or Wnd_Transaction_Anonymous::get_anon_cookies()) {
			$defender = Wnd_Defender::get_instance(0, 0, 0);
			if ($defender) {
				$defender->reset();
			}
		}
	}

}
