<?php

namespace Wnd\Action\Admin;

use Exception;
use Wnd\Action\Wnd_Action_Root;
use Wnd\Utility\Wnd_Defender;

/**
 * 设置服务器系统信息
 * @since 0.9.87
 */
class Wnd_Update_System_Settings extends Wnd_Action_Root {

	private string $type;

	protected function execute(): array {
		switch ($this->type) {
			case 'opcache_reset':
				if (function_exists('opcache_reset')) {
					opcache_reset();
					break;
				} else {
					throw new Exception('OPcache 扩展未启用');
				}
				break;

			case 'wp_cache_flush':
				wp_cache_flush();
				break;

			case 'clear_redis':
				try {
					$redis = new \Redis();
					$redis->connect('127.0.0.1', 6379);
					$redis->flushAll();
				} catch (Exception $e) {
					throw new Exception('Redis 服务不可用: ' . $e->getMessage());
				}
				break;

			case 'clean_block_logs':
				$defender = Wnd_Defender::get_instance(0, 0, 0);
				$defender->clean_block_logs();
				break;

			default:
				throw new Exception('未指定的操作: ' . $this->type);
				break;
		}

		return ['status' => 1, 'msg' => __('操作成功', 'wnd') . ' action:' . $this->type];
	}

	protected function parse_data() {
		$this->type = $this->data['type'] ?? '';
	}
}
