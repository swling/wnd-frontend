<?php

namespace Wnd\Query;

use Exception;
use Wnd\WPDB\Wnd_Mail_DB;

/**
 * 根据 id 查询邮件
 * 如当前用户为邮件接收用户，则标记邮件为已读
 *
 * @since 0.9.73
 */
class Wnd_Get_Mail extends Wnd_Query {

	protected static function query($args = []): array {
		$id       = (int) ($args['id'] ?? 0);
		$instance = Wnd_Mail_DB::get_instance();
		$mail     = $instance->get($id);
		if (!$mail) {
			throw new Exception('Invalid Mail ID');
		}

		// 标记为已读
		$current_user_id = get_current_user_id();
		if ('unread' == $mail->status and $current_user_id == $mail->to) {
			$instance->update([
				'ID'      => $mail->ID,
				'status'  => 'read',
				'read_at' => time(),
			]);
		}

		// 超级管理员
		if (is_super_admin()) {
			return (array) $mail;
		}

		// 用户未登录，或登陆用户查询他人邮件：抛出异常
		if (!$current_user_id or $mail->to != $current_user_id) {
			throw new Exception('权限错误');
		}

		return (array) $mail;
	}
}
