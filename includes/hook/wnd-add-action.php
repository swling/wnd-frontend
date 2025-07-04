<?php
namespace Wnd\Hook;

use Wnd\Model\Wnd_Auth_Code;
use Wnd\Model\Wnd_Order_Props;
use Wnd\Utility\Wnd_Affiliate;
use Wnd\Utility\Wnd_Defender_User;
use Wnd\Utility\Wnd_Singleton_Trait;
use Wnd\View\Wnd_Form_Option;
use Wnd\WPDB\Wnd_User_DB;

/**
 * Wnd Action
 */
class Wnd_Add_Action {

	use Wnd_Singleton_Trait;

	private function __construct() {
		add_action('wnd_user_register', [__CLASS__, 'action_on_user_register'], 10, 2);
		add_action('wnd_login', [__CLASS__, 'action_on_login'], 10);
		add_action('wnd_login_failed', [__CLASS__, 'action_on_login_failed'], 10);
		add_action('wnd_upload_file', [__CLASS__, 'action_on_upload_file'], 10, 3);
		add_action('wnd_delete_file', [__CLASS__, 'action_on_delete_file'], 10, 3);
		add_action('before_delete_wnd_transaction', [__CLASS__, 'action_on_wnd_transaction_deleted'], 10);
		add_action('before_delete_wnd_attachment', [__CLASS__, 'action_on_delete_attachment']);

		/**
		 * 获取产品属性时，释放指定时间内未完成支付的库存
		 * - 之所以通过钩子挂载，因为某些情况下需要移除这个操作，避免死循环
		 * - 如在订单删除还原库存时，如果当前产品包含可释放的订单，将陷入死循环：还原库存=>触发订单释放=>还原库存
		 * @since 0.9.38
		 */
		add_action('wnd_pre_get_product_props', 'Wnd\Model\Wnd_Order_Props::release_pending_orders', 10, 1);
	}

	/**
	 * @since 初始化 用户注册后
	 */
	public static function action_on_user_register($user_id, $data) {
		// 创建 Wnd_User 记录（必须在注册时即创建表记录，否则无记录可能导致在首次登陆写入前的操作无效）
		Wnd_User_DB::update_wnd_user($user_id, []);

		// 注册成功
		Wnd_Affiliate::reg_success($user_id);

		// 注册类，将注册用户id写入对应数据表
		$email_or_phone = $data['phone'] ?? $data['_user_user_email'] ?? '';
		if (!$email_or_phone) {
			return;
		}

		// 绑定邮箱或手机
		$auth = Wnd_Auth_Code::get_instance($email_or_phone);
		$auth->bind_user($user_id);
	}

	/**
	 * 登录成功
	 * @since 0.8.61
	 */
	public static function action_on_login($user) {
		// Defender：清空登录失败日志
		$defender = new Wnd_Defender_User($user->ID);
		$defender->reset_log();
	}

	/**
	 * 登录失败
	 * @since 0.8.61
	 */
	public static function action_on_login_failed($user) {
		// Defender：写入登录失败日志
		$defender = new Wnd_Defender_User($user->ID);
		$defender->write_failure_log();
	}

	/**
	 * ajax上传文件时，根据 meta_key 做后续处理
	 * @since 2018
	 */
	public static function action_on_upload_file($attachment_id, $post_parent, $meta_key) {
		if (!$meta_key) {
			return;
		}

		// 存储在 Wnd option中 : _option_{$option_name}_{$option_key}
		if (0 === stripos($meta_key, '_option_')) {
			$old_option = Wnd_Form_Option::get_option_value_by_input_name($meta_key);
			if ($old_option) {
				wnd_delete_attachment($old_option, true);
			}

			Wnd_Form_Option::update_option_by_input_name($meta_key, $attachment_id);
			return;
		}

		// WordPress原生缩略图
		if ('_wpthumbnail_id' == $meta_key) {
			$old_meta = get_post_meta($post_parent, '_thumbnail_id', true);
			if ($old_meta) {
				wnd_delete_attachment($old_meta, true);
			}

			set_post_thumbnail($post_parent, $attachment_id);
			return;
		}

		// 储存在文章字段
		if ($post_parent) {
			$old_meta = wnd_get_post_meta($post_parent, $meta_key);
			if ($old_meta) {
				wnd_delete_attachment($old_meta, true);
			}
			wnd_update_post_meta($post_parent, $meta_key, $attachment_id);

			//储存在用户字段
		} else {
			$user_id       = get_current_user_id();
			$old_user_meta = wnd_get_user_meta($user_id, $meta_key);
			if ($old_user_meta) {
				wnd_delete_attachment($old_user_meta, true);
			}
			wnd_update_user_meta($user_id, $meta_key, $attachment_id);
		}
	}

	/**
	 * ajax删除附件时
	 * @since 2018
	 */
	public static function action_on_delete_file($attachment_id, $post_parent, $meta_key) {
		if (!$meta_key) {
			return;
		}

		// 删除在 option
		if (0 === stripos($meta_key, '_option_')) {
			Wnd_Form_Option::delete_option_by_input_name($meta_key);
		}

		// 删除文章字段
		if ($post_parent) {
			wnd_delete_post_meta($post_parent, $meta_key);
			//删除用户字段
		} else {
			wnd_delete_user_meta(get_current_user_id(), $meta_key);
		}
	}

	public static function action_on_wnd_transaction_deleted(object $transaction) {
		/**
		 * @since 2019.06.04 删除订单时，扣除订单统计字段
		 * @since 2019.07.03 删除订单时，删除user_has_paid缓存
		 */
		if ('order' == $transaction->type) {
			Wnd_Order_Props::cancel_order($transaction);
			return;
		}

	}

	/**
	 * 删除附件数据时，同步删除服务器文件
	 */
	public static function action_on_delete_attachment(object $attachment): bool {
		$uploadpath = wp_get_upload_dir();
		$path       = $attachment->file_path;

		$file = $uploadpath['basedir'] . "/$path";
		return wp_delete_file_from_directory($file, $uploadpath['basedir']);
	}
}
