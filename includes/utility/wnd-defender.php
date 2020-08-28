<?php
namespace Wnd\Utility;

use Memcached;

/**
 *安全防护
 *@since 0.8.61
 *
 *使用方法：
 *启用拦截应该在加载WP之前，如在 wp-config.php 中手动引入本文件
 *
 * require dirname(__FILE__) . '/wp-content/plugins/wnd-frontend/includes/utility/wnd-defender.php';
 * new Wnd\Utility\Wnd_Defender(60, 5, 1800);
 *
 * 防护依赖 Memcached 缓存、暂未支持 Redis
 *
 * 备注：本类应仅依赖 PHP 及 Memcached，不得依赖 WP 环境
 */
class Wnd_Defender {

	/**
	 *客户端ip
	 */
	public $ip;

	/**
	 *IP段
	 */
	public $ip_base;

	/**
	 *单个IP内存缓存Key
	 */
	public $key;

	/**
	 *IP段内存缓存Key
	 */
	public $base_key;

	/**
	 *访问次数统计
	 */
	public $count;

	/**
	 *IP段拦截统计
	 */
	public $base_count;

	/**
	 *拦截计数时间段
	 */
	protected $period = 60;

	/**
	 *在规定时间段最多错误次数
	 */
	protected $max_connections = 10;

	/**
	 *锁定时间
	 */
	protected $blocked_time = 1800;

	/**
	 *构造拦截器
	 *@param int $period 			拦截统计时间范围
	 *@param int $max_connections 	拦截时间范围内，单ip允许的最大连接数
	 *@param int $blocked_time 		符合拦截条件的ip锁定时间
	 */
	public function __construct(int $period, int $max_connections, int $blocked_time) {
		if (!class_exists('Memcached')) {
			return;
		}

		$this->period          = $period;
		$this->max_connections = ('POST' == $_SERVER['REQUEST_METHOD']) ? $max_connections / 2 : $max_connections;
		$this->blocked_time    = $blocked_time;
		$this->ip              = static::get_real_ip();
		$this->ip_base         = preg_replace('/(\d+)\.(\d+)\.(\d+)\.(\d+)/is', "$1.$2.$3", $this->ip);
		$this->key             = $this->build_key($this->ip);
		$this->base_key        = $this->build_key($this->ip_base);

		$this->defend();
	}

	/**
	 *核查防护
	 *单个IP规定时间内返回超限拦截，并记录ip端
	 *IP段累积拦截超限，拦截整个IP段（为避免误杀，IP段拦截时间为IP检测时间段，而非IP拦截时间）
	 */
	protected function defend() {
		$m = new Memcached();
		$m->addServer('localhost', 11211);
		$this->count      = $m->get($this->key);
		$this->base_count = $m->get($this->base_key);

		// IP段拦截
		if ($this->base_count > $this->max_connections) {
			header('HTTP/1.1 403 Forbidden');
			exit('Blocked By IP Base : ' . $this->base_count . '-' . $this->ip);
		}

		// 首次访问
		if (!$this->count) {
			$m->set($this->key, 1, $this->period);
			return;
		}

		// 拦截检测时间范围内再次访问
		$m->increment($this->key, 1);

		// 如果当前访问首次被拦截，统计IP段拦截次数
		if ($this->count == $this->max_connections) {
			if ($this->base_count) {
				$m->increment($this->base_key, 1);
			} else {
				$m->set($this->base_key, 1, $this->period);
			}
		}

		// 符合拦截条件：屏蔽当前IP，并新增记录
		if ($this->count >= $this->max_connections) {
			$m->set($this->key, $this->count + 1, $this->blocked_time);

			header('HTTP/1.1 403 Forbidden');
			exit('Blocked By IP : ' . $this->count . ' - ' . $this->ip);
		}
	}

	/**
	 *生成识别 key
	 */
	protected function build_key($key): string {
		return 'wnd_' . $key;
	}

	//获取客户端真实ip地址
	protected static function get_real_ip(): string {
		static $realip;
		if (isset($_SERVER)) {
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			} else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
				$realip = $_SERVER['HTTP_CLIENT_IP'];
			} else {
				$realip = $_SERVER['REMOTE_ADDR'];
			}
		} else {
			if (getenv('HTTP_X_FORWARDED_FOR')) {
				$realip = getenv('HTTP_X_FORWARDED_FOR');
			} else if (getenv('HTTP_CLIENT_IP')) {
				$realip = getenv('HTTP_CLIENT_IP');
			} else {
				$realip = getenv('REMOTE_ADDR');
			}
		}
		return $realip;
	}
}
