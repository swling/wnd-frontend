<?php
namespace Wnd\Query;

use Exception;
use Wnd\Model\Wnd_Transaction;

/**
 * 根据交易 id 查询交易记录
 * 若当前用户并非交易发起者，且非超级管理员，则仅返回状态值
 *
 * @since 2023.10.30
 * @since 0.9.89.5 修改为按 slug 查询，为匿名查询订单提供准备
 */
class Wnd_Get_Transaction extends Wnd_Query {

	protected static function query($args = []): array {
		$slug        = $args['slug'] ?? '';
		$transaction = Wnd_Transaction::query_db(['slug' => $slug]);
		if (!$transaction) {
			throw new Exception('Invalid Transaction Slug');
		}

		// 产品 url
		$transaction->object_url = $transaction->object_id ? get_permalink($transaction->object_id) : '';

		// 基本信息
		$basic_info = [
			'status'     => $transaction->status,
			'object_id'  => $transaction->object_id,
			'object_url' => $transaction->object_url,
		];

		// 超级管理员
		if (is_super_admin()) {
			return (array) $transaction;
		}

		// 用户未登录，或登陆用户查询他人订单，仅返回基本信息
		$current_user_id = get_current_user_id();
		if (!$current_user_id or $transaction->user_id != $current_user_id) {
			return $basic_info;
		}

		return (array) $transaction;
	}

}
