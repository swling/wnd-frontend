<?php

namespace Wnd\Query;

use Exception;
use Wnd\WPDB\Wnd_Transaction_DB;

/**
 * posts 查询接口
 * @since 0.9.59.1 从独立 rest api 接口移植入 Wnd_Query
 *
 * 多重筛选 API
 * 常规情况下，controller 应将用户请求转为操作命令并调用 model 处理，但 Wnd\View\Wnd_Filter 是一个完全独立的功能类
 * Wnd\View\Wnd_Filter 既包含了生成筛选链接的视图功能，也包含了根据请求参数执行对应 WP_Query 并返回查询结果的功能，且两者紧密相关不宜分割
 * 可以理解为，Wnd\View\Wnd_Filter 是通过生成一个筛选视图，发送用户请求，最终根据用户请求，生成新的视图的特殊类：视图<->控制<->视图
 * @since 2019.07.31
 * @since 2019.10.07 OOP改造
 *
 * @param $request
 */
class Wnd_Transactions extends Wnd_Query {

	protected static function check() {
		if (!is_user_logged_in()) {
			throw new Exception(__('请登录', 'wndt'), 1);
		}
	}

	protected static function query($args = []): array {
		$type    = $args['type'] ?? 'any';
		$status  = $args['status'] ?? 'any';
		$paged   = $args['paged'] ?? 1;
		$number  = $args['number'] ?? get_option('posts_per_page');
		$user_id = !is_super_admin() ? get_current_user_id() : ($args['user_id'] ?? 'any');

		$where = [
			'type'    => $type,
			'status'  => $status,
			'user_id' => $user_id,
		];

		$handler = Wnd_Transaction_DB::get_instance();
		$results = $handler->get_results($where, $number, ($paged - 1) * $number);

		return ['results' => $results, 'number' => count($results)];
	}

}
