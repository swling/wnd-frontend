<?php

namespace Wnd\Action\Admin;

use Exception;
use Wnd\Action\Wnd_Action_Root;

/**
 * 设置服务器系统信息
 * @since 0.9.87
 */
class Wnd_Update_System_Settings extends Wnd_Action_Root {

	protected $verify_sign = false;

	private $type;

	protected function execute(): array {
		switch ($this->type) {
			case 'clear_opcache':
				if (function_exists('opcache_reset')) {
					opcache_reset();
					break;
				} else {
					throw new Exception('OPcache 扩展未启用');
				}
				break;

			case 'clear_object_cache':
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
