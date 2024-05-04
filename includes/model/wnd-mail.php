<?php
namespace Wnd\Model;

/**
 * 站内信
 * @since 0.9.32
 *
 * @since 0.9.71 预备独立表重构
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
	public static function mail($to, $subject, $message) {}

	/**
	 * 获取最近的10封未读邮件
	 * @since 2019.04.11
	 *
	 * @return 	int 	用户未读邮件
	 */
	public static function get_mail_count() {}

	/**
	 * 删除未读邮件统计缓存
	 */
	public static function delete_mail_count_cache(int $user_id) {
		wp_cache_delete($user_id, static::$mail_count_cache_group);
	}
}
