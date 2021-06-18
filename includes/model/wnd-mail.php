<?php
namespace Wnd\Model;

/**
 * 站内信
 * @since 0.9.32
 */
abstract class Wnd_Mail {

	private static $mail_count_cache_group = 'wnd_mail_count';

	/**
	 * 发送站内信
	 * @since 2019.02.25
	 *
	 * @param  	int    	$to      		收件人ID
	 * @param  	string 	$subject 	邮件主题
	 * @param  	string 	$message 	邮件内容
	 * @return 	bool   	true on success
	 */
	public static function mail($to, $subject, $message) {
		if (!get_user_by('id', $to)) {
			return ['status' => 0, 'msg' => __('用户不存在', 'wnd')];
		}

		$postarr = [
			'post_type'    => 'mail',
			'post_author'  => $to,
			'post_title'   => $subject,
			'post_content' => $message,
			'post_status'  => 'wnd-unread',
			'post_name'    => uniqid(),
		];

		$mail_id = wp_insert_post($postarr);

		if (is_wp_error($mail_id)) {
			return false;
		} else {
			wp_cache_delete($to, static::$mail_count_cache_group);
			return true;
		}
	}

	/**
	 * 获取最近的10封未读邮件
	 * @since 2019.04.11
	 *
	 * @return 	int 	用户未读邮件
	 */
	public static function get_mail_count() {
		$user_id = get_current_user_id();
		if (!$user_id) {
			return 0;
		}

		$user_mail_count = wp_cache_get($user_id, static::$mail_count_cache_group);
		if (false === $user_mail_count) {
			$args = [
				'posts_per_page' => 11,
				'author'         => $user_id,
				'post_type'      => 'mail',
				'post_status'    => 'wnd-unread',
			];

			$user_mail_count = count(get_posts($args));
			$user_mail_count = ($user_mail_count > 10) ? '10+' : $user_mail_count;
			wp_cache_set($user_id, $user_mail_count, static::$mail_count_cache_group);
		}

		return $user_mail_count ?: 0;
	}

	/**
	 * 删除未读邮件统计缓存
	 */
	public static function delete_mail_count_cache(int $user_id) {
		wp_cache_delete($user_id, static::$mail_count_cache_group);
	}
}
