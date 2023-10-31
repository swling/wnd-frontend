<?php
namespace Wnd\Query;

use Exception;
use Wnd\Model\Wnd_Transaction;

/**
 * 根据交易 id 查询交易记录
 * 若当前用户并非交易发起者，且非超级管理员，则仅返回状态值
 * 
 * @since 2023.10.30
 */
class Wnd_Get_Transaction extends Wnd_Query {

	protected static function query($args = []): array {
		$id          = (int) ($args['id'] ?? 0);
		$transaction = Wnd_Transaction::query_db(['ID' => $id]);
		if (!$transaction) {
			throw new Exception('Invalid Transaction ID');
		}

		/**
		 * 非公开post仅返回基本状态
		 */
		if ($transaction->user_id != get_current_user_id() and !is_super_admin()) {
			return [
				'status' => $transaction->status,
			];
		}

		return (array) $transaction;
	}

}
