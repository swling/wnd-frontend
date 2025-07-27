<?php
namespace Wnd\Admin;

use Wnd\WPDB\Wnd_DB;
use Wnd\WPDB\Wnd_User_DB;

/**
 * 升级
 * @since 2020.08.19
 */
class Wnd_Admin_Upgrade {

	public static function upgrade() {
		$db_version = get_option('wnd_ver');
		if (version_compare($db_version, WND_VER, '>=')) {
			return;
		}

		// 提取所有版本升级方法：以"v_"为前缀的方法，并做版本对比确定是否执行
		$reflection      = new \ReflectionClass(__CLASS__);
		$methods         = $reflection->getMethods(\ReflectionMethod::IS_STATIC);
		$upgrade_methods = [];
		foreach ($methods as $method) {
			if (0 !== stripos($method->name, 'v_')) {
				continue;
			}

			// 将升级方法名称，转为与之匹配的版本号（v_0_9_57 =>v.0.9.57）
			$method_version = str_replace('_', '.', $method->name);
			if (version_compare('v.' . $db_version, $method_version, '>=')) {
				continue;
			}

			// 写入数组存储，因为升级方法的执行顺序非常重要，保险起见，存入数组后并排序后，再循环执行
			$upgrade_methods[] = $method->class . '::' . $method->name;
		}

		// 脚本超时
		ini_set('max_execution_time', 0);

		// 方法排序并执行升级
		asort($upgrade_methods);
		foreach ($upgrade_methods as $upgrade_method) {
			$upgrade_method();
		}

		update_option('wnd_ver', WND_VER);
	}

	private static function v_0_9_29() {
		wnd_delete_option('wnd', 'alipay_sandbox');
	}

	private static function v_0_9_30() {
		if ('COS' == wnd_get_option('wnd', 'oss_sp')) {
			wnd_update_option('wnd', 'oss_sp', 'Qcloud');
		} elseif ('OSS' == wnd_get_option('wnd', 'oss_sp')) {
			wnd_update_option('wnd', 'oss_sp', 'Aliyun');
		}

		if ('tencent' == wnd_get_option('wnd', 'captcha_service')) {
			wnd_update_option('wnd', 'captcha_service', 'Qcloud');
		} elseif ('aliyun' == wnd_get_option('wnd', 'captcha_service')) {
			wnd_update_option('wnd', 'captcha_service', 'Aliyun');
		}

		if ('tencent' == wnd_get_option('wnd', 'sms_sp')) {
			wnd_update_option('wnd', 'sms_sp', 'Qcloud');
		} elseif ('aliyun' == wnd_get_option('wnd', 'sms_sp')) {
			wnd_update_option('wnd', 'sms_sp', 'Aliyun');
		}
	}

	private static function v_0_9_38() {
		$enable_cdn = wnd_get_option('wnd', 'cdn_enable');
		wnd_delete_option('wnd', 'cdn_enable');
		wnd_update_option('wnd', 'enable_cdn', $enable_cdn);

		$enable_oss = wnd_get_option('wnd', 'oss_enable');
		wnd_delete_option('wnd', 'oss_enable');
		wnd_update_option('wnd', 'enable_oss', $enable_oss);
	}

	// 新增用户日志表，需要升级数据表
	private static function v_0_9_57() {
		Wnd_DB::create_table();

		// 将旧的用户余额转移至新表（之所有需要将用户余额作为新表存储，是为了定期清理余额为零的睡眠账户）
		global $wpdb;
		$results = $wpdb->get_results("SELECT ID FROM $wpdb->users");
		foreach ($results as $user) {
			$user_id   = $user->ID;
			$old_money = floatval(wnd_get_user_meta($user_id, 'money'));
			if (!$old_money) {
				wnd_delete_user_meta($user_id, 'money');
				continue;
			}

			// 转移
			$action = \Wnd\Model\Wnd_Finance::inc_user_balance($user_id, $old_money, false);
			if ($action) {
				wnd_delete_user_meta($user_id, 'money');
			}
		}
	}

	// 用户日志表新增字段：login_count 统计用户累计登录次数（按天）
	private static function v_0_9_57_5() {
		global $wpdb;
		$exists = $wpdb->query("SHOW COLUMNS FROM $wpdb->wnd_users WHERE field='login_count' ");
		if ($exists) {
			return;
		}

		$wpdb->query("ALTER TABLE $wpdb->wnd_users ADD COLUMN `login_count` BIGINT NOT NULL AFTER `last_login`,  ADD INDEX(login_count)");
	}

	// 删除自定义用户表 role 及 attribute 字段
	private static function v_0_9_57_7() {
		global $wpdb;
		$wpdb->query("ALTER TABLE $wpdb->wnd_users DROP COLUMN role, DROP COLUMN attribute");
	}

	// 用户日志表新增字段：last_recall 记录最后一次召回睡眠用户的时间
	private static function v_0_9_59_9() {
		global $wpdb;
		$exists = $wpdb->query("SHOW COLUMNS FROM $wpdb->wnd_users WHERE field='last_recall' ");
		if ($exists) {
			return;
		}

		$wpdb->query("ALTER TABLE $wpdb->wnd_users ADD COLUMN `last_recall` BIGINT NOT NULL AFTER `login_count`,  ADD INDEX(last_recall)");
	}

	// 交易数据采用独立数据：转移历史订单至独立数据表
	private static function v_0_9_67() {
		// 脚本超时
		ini_set('max_execution_time', 0);

		Wnd_DB::create_table();

		global $wpdb;
		$old_posts = $wpdb->get_results(
			"SELECT * FROM $wpdb->posts WHERE post_type IN ('order','recharge')"
		);

		$handler = \Wnd\WPDB\Wnd_Transaction_DB::get_instance();
		foreach ($old_posts as $post) {
			$post_arr = [
				// 'ID'              => $post->ID,
				'type'            => $post->post_type,
				'user_id'         => $post->post_author ?: 0,
				'object_id'       => $post->post_parent,
				'total_amount'    => $post->post_content ?: 0,
				'payment_gateway' => $post->post_excerpt ?: 'internal',
				'status'          => str_replace('wnd-', '', $post->post_status),
				'subject'         => $post->post_title,
				'slug'            => $post->post_name,
				'time'            => strtotime($post->post_date_gmt),
				'props'           => json_encode(get_post_meta($post->ID, 'wnd_meta', true), JSON_UNESCAPED_UNICODE),
			];

			$ID = $handler->insert($post_arr);
			if ($ID) {
				wp_delete_post($post->ID, true);
			}
		}
	}

	private static function v_0_9_71() {
		global $wpdb;

		// 废弃的站内信和赞赏
		$old_posts = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'mail' OR post_type = 'reward' ");
		foreach ((array) $old_posts as $delete) {
			// Force delete.
			wp_delete_post($delete, true);
		}

		// 新增用户开销字段 expense
		$exists = $wpdb->query("SHOW COLUMNS FROM $wpdb->wnd_users WHERE field='expense' ");
		if (!$exists) {
			$wpdb->query("ALTER TABLE $wpdb->wnd_users ADD COLUMN `expense` decimal(10, 2) NOT NULL AFTER `balance` ");
		}

		// 转移原有 expense 字段
		$user_ids = $wpdb->get_col("SELECT ID FROM $wpdb->users");
		foreach ($user_ids as $user_id) {
			$expense = floatval(wnd_get_user_meta($user_id, 'expense'));
			if ($expense <= 0) {
				continue;
			}

			$action = Wnd_User_DB::update_wnd_user($user_id, ['expense' => $expense]);
			if ($action) {
				wnd_delete_user_meta($user_id, 'expense');
			} else {
				exit('出现错误');
			}
		}
	}

	private static function v_0_9_73() {
		wnd_delete_option('wnd', 'pay_return_url');
	}

	private static function v_0_9_74() {
		Wnd_DB::create_table();
	}

	private static function v_0_9_75() {
		// 创建 analyses 数据表
		Wnd_DB::create_table();

		/**
		 * 一、删除孤立的 post meta。原因如下：
		 * 1、 analyses 表 post_id 启用了外键约束，需确保 meta 均对应有 post，否则无法写入。
		 * 2、移除冗余数据。
		 *
		 * 二、历史数据迁移：将 views 字段转入独立表
		 */
		global $wpdb;
		// $meta = $wpdb->get_results("SELECT pm.* FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE p.ID IS NULL;");
		$meta = $wpdb->query("DELETE pm FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE p.ID IS NULL;");

		$wpdb->query("
			INSERT INTO {$wpdb->wnd_analyses} (post_id, total_views) SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'views';
		");

		// 清理 user meta
		// $meta = $wpdb->query("SELECT um.* FROM {$wpdb->usermeta} um LEFT JOIN {$wpdb->users} u ON um.user_id = u.ID WHERE u.ID IS NULL;");
		// $meta = $wpdb->query("DELETE um FROM {$wpdb->usermeta} um LEFT JOIN {$wpdb->users} u ON um.user_id = u.ID WHERE u.ID IS NULL;");
	}

	// 删除原有浏览量统计字段
	private static function v_0_9_76() {
		delete_post_meta_by_key('views');
	}

	// 附件数据采用独立数据：转移附件posts至独立数据表
	private static function v_0_9_86() {
		// 脚本超时
		ini_set('max_execution_time', 0);

		Wnd_DB::create_table();

		global $wpdb;
		$handler = \Wnd\WPDB\Wnd_Attachment_DB::get_instance();

		// 迁移数据
		$batch_size = 1000;
		$offset     = 0;
		do {
			$attachments = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->posts}  WHERE post_type = 'attachment' ORDER BY ID LIMIT %d OFFSET %d",
					$batch_size,
					$offset
				)
			);

			foreach ($attachments as $post) {
				$file_path = get_post_meta($post->ID, '_wp_attached_file', true);
				if (empty($file_path)) {
					continue;
				}

				$exists = $handler->get_by('attachment_id', $post->ID);
				if ($exists) {
					continue;
				}

				$post_arr = [
					'user_id'       => $post->post_author,
					'post_id'       => $post->post_parent,
					'mime_type'     => $post->post_mime_type,
					'file_path'     => $file_path,
					'attachment_id' => $post->ID,
					'meta_key'      => $post->post_content_filtered,
					'created_at'    => strtotime($post->post_date_gmt),
				];

				$ID = $handler->insert($post_arr);
				// if ($ID) {
				// 	wp_delete_post($post->ID, true);
				// }
			}

			$offset += $batch_size;
		} while (count($attachments) === $batch_size);

		// 迁移用户头像
		$batch_size = 1000;
		$offset     = 0;
		do {
			$users = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->users} ORDER BY ID LIMIT %d OFFSET %d",
					$batch_size,
					$offset
				)
			);

			foreach ($users as $user) {
				$avatar = wnd_get_user_meta($user->ID, 'avatar');
				if (!$avatar) {
					continue;
				}

				$new_db = $handler->get_by('attachment_id', $avatar);
				if ($new_db and $new_db->ID != $avatar) {
					wnd_update_user_meta($user->ID, 'avatar', $new_db->ID);
				}
			}

			$offset += $batch_size;
		} while (count($users) === $batch_size);

		// 迁移文章缩略图和付费文件
		$batch_size = 1000;
		$offset     = 0;
		do {
			$posts = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} ORDER BY ID LIMIT %d OFFSET %d",
					$batch_size,
					$offset
				)
			);

			foreach ($posts as $post) {
				$thumb = wnd_get_post_meta($post->ID, '_thumbnail_id');
				$file  = wnd_get_post_meta($post->ID, 'file');
				if (!$thumb and !$file) {
					continue;
				}

				// 缩略图
				$new_db = $handler->get_by('attachment_id', $thumb);
				if ($new_db and $new_db->ID != $thumb) {
					wnd_update_post_meta($post->ID, '_thumbnail_id', $new_db->ID);
				}

				// 文件
				$new_db = $handler->get_by('attachment_id', $file);
				if ($new_db and $new_db->ID != $file) {
					wnd_update_post_meta($post->ID, 'file', $new_db->ID);
				}
			}

			$offset += $batch_size;
		} while (count($posts) === $batch_size);
	}

	private static function v_0_9_87() {
		global $wpdb;
		$batch_size = 1000;

		do {
			$attachment_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' LIMIT %d",
					$batch_size
				)
			);

			if (empty($attachment_ids)) {
				break;
			}

			// 删除 postmeta
			$placeholders = implode(',', array_fill(0, count($attachment_ids), '%d'));
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($placeholders)",
					...$attachment_ids
				)
			);

			// 删除 attachment posts
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->posts} WHERE ID IN ($placeholders)",
					...$attachment_ids
				)
			);

			// 清理缓存
			foreach ($attachment_ids as $post_id) {
				wp_cache_delete($post_id, 'posts');
				wp_cache_delete($post_id, 'post_meta');
			}

			// 可选：强制刷新整个对象缓存（不推荐频繁使用）
			// wp_cache_flush();

			usleep(50000); // 50ms

		} while (!empty($attachment_ids));
	}

	// 删除：已经废弃的静态资源配置
	private static function v_0_9_90() {
		wnd_delete_option('wnd', 'static_host');
		wnd_delete_option('wnd', 'agreement_url');
		wnd_delete_option('wnd', 'reg_redirect_url');
	}

}
