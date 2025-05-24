<?php

namespace Wnd\Query\Admin;

use Exception;
use Wnd\Query\Wnd_Query;

/**
 * 系统信息
 * @since 0.9.89
 */
class Wnd_System_Info extends Wnd_Query {

	protected static function check() {
		if (!wnd_is_manager()) {
			throw new Exception(__('权限不足', 'wnd'));
		}
	}

	protected static function query($args = []): array {
		$response['success'] = true;
		$response['opcache'] = static::get_opcache_info();
		$response['redis']   = static::get_redis_info();
		$response['system']  = static::get_system_info();

		return $response;
	}

	private static function get_opcache_info(): array {
		if (function_exists('opcache_get_status')) {
			return opcache_get_status(false) ?: [];
		} else {
			return ['opcache_error' => 'OPcache 扩展未启用'];
		}
	}

	private static function get_redis_info(): array {
		// 获取 Redis 状态
		$redisInfo      = [];
		$redisMaxMemory = 0;
		try {
			$redis = new \Redis();
			$redis->connect('127.0.0.1', 6379);
			$redisInfo      = $redis->info();
			$redisMaxMemory = (int) ($redis->config('GET', 'maxmemory')['maxmemory'] ?? 0);
			$redis->close();

			return [
				'info'      => $redisInfo,
				'maxmemory' => $redisMaxMemory,
			];
		} catch (Exception $e) {
			return ['redis_error' => 'Redis 服务不可用: ' . $e->getMessage()];
		}
	}

	private static function get_system_info(): array {
		$diskTotal = round(@disk_total_space('.') / (1024 * 1024 * 1024), 2); //总
		$diskFree  = round(@disk_free_space('.') / (1024 * 1024 * 1024), 2); //可用
		$diskUsed  = $diskTotal - $diskFree;
		$hdPercent = (floatval($diskTotal) != 0) ? round($diskUsed / $diskTotal * 100, 2) : 0;

		// 获取负载信息（load average）
		$loadavg = sys_getloadavg(); // 返回 [1分, 5分, 15分]

		// 组装响应
		return [
			'disk' => [
				'total' => $diskTotal,
				'used'  => $diskUsed,
				'free'  => $diskFree,
			],
			// 获取 CPU 信息
			'cpu'  => static::get_cpu_info(),
			'load' => [
				'1min'  => $loadavg[0],
				'5min'  => $loadavg[1],
				'15min' => $loadavg[2],
			],
			'mem'  => static::getMemoryInfo(),
		];
	}

	private static function get_cpu_info(): array {
		// 获取 CPU 信息
		$cpuInfo = [
			'cores' => 0,
			'model' => '',
		];
		$cpuUsage = null;

		// 获取核心数与型号
		if (is_readable('/proc/cpuinfo')) {
			$cpuinfoRaw = file_get_contents('/proc/cpuinfo');
			preg_match_all('/^processor/m', $cpuinfoRaw, $matches);
			$cpuInfo['cores'] = count($matches[0]);

			if (preg_match('/model name\s+:\s+(.+)/', $cpuinfoRaw, $matches)) {
				$cpuInfo['model'] = $matches[1];
			}
		}

		$start = static::getCpuTimes();
		usleep(100000); // 小睡 100ms
		$end = static::getCpuTimes();

		if (!empty($start) && !empty($end)) {
			$startTotal = array_sum($start);
			$endTotal   = array_sum($end);
			$startIdle  = $start['idle'];
			$endIdle    = $end['idle'];

			$deltaTotal = $endTotal - $startTotal;
			$deltaIdle  = $endIdle - $startIdle;

			if ($deltaTotal > 0) {
				$cpuUsage = round(100 * (1 - ($deltaIdle / $deltaTotal)), 2);
			}
		}

		return [
			'cores'         => $cpuInfo['cores'],
			'model'         => $cpuInfo['model'],
			'usage_percent' => $cpuUsage,
		];
	}

	// 获取 CPU 使用率
	private static function getCpuTimes(): array {
		if (!is_readable('/proc/cpuinfo')) {
			return [];
		}

		$line = file_get_contents('/proc/stat');
		if (preg_match('/^cpu\s+(.+)/', $line, $matches)) {
			$parts = preg_split('/\s+/', trim($matches[1]));
			return [
				'user'    => (int) $parts[0],
				'nice'    => (int) $parts[1],
				'system'  => (int) $parts[2],
				'idle'    => (int) $parts[3],
				'iowait'  => (int) $parts[4],
				'irq'     => (int) $parts[5],
				'softirq' => (int) $parts[6],
			];
		}
		return [];
	}

	private static function getMemoryInfo(): array {
		if (!is_readable('/proc/meminfo')) {
			return [];
		}
		$meminfo = file_get_contents('/proc/meminfo');
		$data    = [];
		foreach (explode("\n", $meminfo) as $line) {
			if (preg_match('/^(\w+):\s+(\d+)\s+kB$/', $line, $matches)) {
				$data[$matches[1]] = (int) $matches[2]; // 单位为 KB
			}
		}

		$total     = $data['MemTotal'] ?? 0;
		$free      = $data['MemFree'] ?? 0;
		$available = $data['MemAvailable'] ?? 0;
		$buffers   = $data['Buffers'] ?? 0;
		$cached    = $data['Cached'] ?? 0;

		$used = $total - $free - $buffers - $cached;
		$toGB = fn($kb) => round($kb / 1048576, 2); // 1 GB = 1024*1024 KB

		return [
			'total_gb'   => $toGB($total),
			'used_gb'    => $toGB($used),
			'free_gb'    => $toGB($free),
			'cached_gb'  => $toGB($cached),
			'buffers_gb' => $toGB($buffers),
		];
	}
}
