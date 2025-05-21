<?php

namespace Wnd\Query;

use Exception;
use Wnd\WPDB\Wnd_Mail_DB;

/**
 * Mails 查询接口
 * @since 0.9.73
 */
class Wnd_Mails extends Wnd_Query {

	protected static function check() {
		if (!is_user_logged_in()) {
			throw new Exception(__('请登录', 'wnd'));
		}
	}

	protected static function query($args = []): array {
		$status  = $args['status'] ?? 'any';
		$paged   = $args['paged'] ?? 1;
		$number  = $args['number'] ?? get_option('posts_per_page');
		$user_id = !is_super_admin() ? get_current_user_id() : ($args['user_id'] ?? 'any');

		$where = [
			'status'   => (string) $status,
			'receiver' => (int) $user_id,
		];

		$handler = Wnd_Mail_DB::get_instance();
		$results = $handler->get_results($where, ['limit' => $number, 'offset' => ($paged - 1) * $number]);

		return ['results' => $results, 'number' => count($results)];
	}

}
