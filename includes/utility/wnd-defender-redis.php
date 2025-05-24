<?php
namespace Wnd\Utility;

use Exception;
use Redis;
use Wnd\Utility\Wnd_Defender;

/**
 * 加载本文件时，尚未加载类文件自动加载规则，因此需要手动引入文件
 *
 */
if (!class_exists('\Wnd\Utility\Wnd_Defender')) {
	require dirname(__FILE__) . '/wnd-defender.php';
}

/**
 * 安全防护 Redis 子类
 *
 * ## 使用方法：
 * - 启用拦截应该在加载WP之前，如在 wp-config.php 中手动引入本文件
 *   require dirname(__FILE__) . '/wp-content/plugins/wnd-frontend/includes/utility/wnd-defender.php';
 *   Wnd\Utility\Wnd_Defender::get_instance(60, 5, 1800);
 *
 * 备注：本类仅依赖 PHP 及 Redis，不依赖 WP 环境
 * @link https://github.com/phpredis/phpredis
 *
 * @since 0.8.61
 */
class Wnd_Defender_Redis Extends Wnd_Defender {

	/**
	 * 封装实例化内存缓存初始化，以便重写以适配其他内存缓存如 redis
	 */
	protected function cache_init() {
		if (!class_exists('Redis')) {
			throw new Exception('Redis is not installed yet');
		}

		try {
			$this->cache = new Redis();
			$this->cache->connect('127.0.0.1', 6379);
		} catch (Exception $e) {
			$this->cache = null;
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * 封装内存缓存读取方法，以便重写以适配其他内存缓存如 redis
	 * 获取
	 */
	protected function cache_get($key) {
		return $this->cache->get($key);
	}

	/**
	 * 封装内存缓存设置方法，以便重写以适配其他内存缓存如 redis
	 * 设置
	 */
	protected function cache_set($key, $value, $expiration) {
		$expiration = $expiration < 1 ? 1 : $expiration;
		return $this->cache->set($key, $value, $expiration);
	}

	/**
	 * 封装内存缓存增加方法，以便重写以适配其他内存缓存如 redis
	 * 新增
	 */
	protected function cache_inc($key, $offset) {
		return $this->cache->incrBy($key, $offset);
	}

	/**
	 * 封装内存缓存设置方法，以便重写以适配其他内存缓存如 redis
	 * 删除
	 */
	protected function cache_delete($key) {
		return $this->cache->del($key);
	}

}
