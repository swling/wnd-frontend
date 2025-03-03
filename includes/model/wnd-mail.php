<?php
namespace Wnd\Model;

use Wnd\WPDB\Wnd_Mail_DB;

/**
 * 站内信
 * @since 0.9.32
 *
 * @since 0.9.73 独立表重构
 */
abstract class Wnd_Mail {

	/**
	 * 发送站内信
	 *
	 * @param  	int    	$to      	收件人ID
	 * @param  	string 	$subject 	邮件主题
	 * @param  	string 	$message 	邮件内容
	 * @return 	int   	ID/0
	 */
	public static function mail(int $to, string $subject, string $message): int {
		if (!$to or !get_user_by('id', $to)) {
			return 0;
		}

		$hander = Wnd_Mail_DB::get_instance();
		return $hander->insert([
			'sender'   => get_current_user_id(),
			'receiver' => $to,
			'subject'  => $subject,
			'content'  => $message,
			'sent_at'  => time(),
		]);
	}

	/**
	 * 获取最近的10封未读邮件
	 * @return 	int 用户未读邮件
	 */
	public static function get_mail_count(): int {
		$user_id = get_current_user_id();
		$handler = Wnd_Mail_DB::get_instance();
		return count($handler->get_results(['receiver' => $user_id, 'status' => 'unread'], ['limit' => 10]));
	}
}
