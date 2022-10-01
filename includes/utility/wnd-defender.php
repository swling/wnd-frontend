<?php
namespace Wnd\Utility;

use Exception;
use Memcached;

/**
 * 安全防护
 *
 * ## 使用方法：
 * - 启用拦截应该在加载WP之前，如在 wp-config.php 中手动引入本文件
 * - 防护依赖 Memcached 缓存、暂未支持 Redis
 *
 * require dirname(__FILE__) . '/wp-content/plugins/wnd-frontend/includes/utility/wnd-defender.php';
 * Wnd\Utility\Wnd_Defender::get_instance(60, 5, 1800);
 *
 * 备注：本类仅依赖 PHP 及 Memcached，不依赖 WP 环境
 *
 * @since 0.8.61
 */
class Wnd_Defender {

	/**
	 * 拦截计数时间段（秒）
	 */
	protected $period;

	/**
	 * 在规定时间段最多错误次数
	 */
	protected $max_connections = 0;

	/**
	 * 锁定时间（秒）
	 */
	protected $blocked_time = 0;

	/**
	 * 客户端ip
	 */
	public $ip;

	/**
	 * IP段
	 */
	public $ip_base;

	/**
	 * 单个IP内存缓存Key
	 */
	public $key;

	/**
	 * IP段内存缓存Key
	 */
	public $base_key;

	/**
	 * 访问次数统计
	 */
	public $count = 0;

	/**
	 * IP段拦截统计
	 */
	public $base_count = 0;

	/**
	 * 屏蔽日志内存缓存 Key
	 */
	public static $block_logs_key = 'wnd_block_logs';

	/**
	 * 屏蔽记录数
	 */
	protected $block_logs_limit = 50;

	/**
	 * 当前操作
	 */
	protected $action;

	/**
	 * 高危操作
	 */
	protected $risky_actions = ['wnd_send_code', 'wnd_login', 'wnd_reset_password'];

	/**
	 * 高危操作数据内存缓存 Key
	 */
	protected $insight_key;

	/**
	 * 高危操作检测时间范围内允许更改的请求次数
	 */
	protected $max_request_changes = 5;

	/**
	 * 内存缓存
	 */
	protected $cache;

	/**
	 * 单例实例
	 */
	private static $instance;

	// 请求URL信息
	private $pathinfo;

	/**
	 * 单例模式
	 */
	public static function get_instance(int $period, int $max_connections, int $blocked_time) {
		if (!(self::$instance instanceof self)) {
			static::$instance = new self($period, $max_connections, $blocked_time);
		}

		return static::$instance;
	}

	/**
	 * 构造拦截器
	 * @param int $period          	拦截统计时间范围
	 * @param int $max_connections 	拦截时间范围内，单ip允许的最大连接数
	 * @param int $blocked_time    	符合拦截条件的ip锁定时间
	 */
	protected function __construct(int $period, int $max_connections, int $blocked_time) {
		try {
			$this->cache_init();
		} catch (Exception $e) {
			return $e;
		}

		// 拦截配置
		$this->period          = $period;
		$this->max_connections = $max_connections;
		$this->blocked_time    = $blocked_time;

		// IP 属性
		$this->ip          = static::get_real_ip();
		$this->ip_base     = preg_replace('/(\d+)\.(\d+)\.(\d+)\.(\d+)/is', '$1.$2.$3', $this->ip);
		$this->key         = $this->build_key($this->ip);
		$this->base_key    = $this->build_key($this->ip_base);
		$this->insight_key = 'wnd_insight_' . $this->ip;

		// IP 缓存数据
		$this->count      = $this->cache_get($this->key);
		$this->base_count = $this->cache_get($this->base_key);

		// URL 信息
		$this->pathinfo = pathinfo($_SERVER['REQUEST_URI']);
		$this->action   = $this->pathinfo['filename'];

		// 高风险操作探知
		if (in_array($this->action, $this->risky_actions)) {
			$this->insight();
		}

		// 直接拦截违禁的拓展名请求
		$this->defend_extension();

		// xmlrpc
		$this->defend_xmlrpc();

		// Defender
		$this->defend();
	}

	/**
	 * 获取客户端 ip 地址
	 *
	 */
	protected static function get_real_ip(): string{
		$ip = '';
		if (isset($_SERVER)) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['REMOTE_ADDR']);
		} else {
			$ip = getenv('HTTP_X_FORWARDED_FOR') ?: (getenv('HTTP_CLIENT_IP') ?: getenv('REMOTE_ADDR'));
		}

		return $ip ?: '';
	}

	/**
	 * 生成识别 key
	 */
	protected function build_key($key): string {
		return 'wnd_' . $key;
	}

	/**
	 * 智能高危分析，统计同 ip 针对同一个操作接口发起的请求数据变化次数（伪造请求通常会随机生成请求数据）
	 * - 同 IP 发送验证码，持续更换手机号，邮箱号
	 * - 同 IP 登录，持续更换用户名或密码
	 */
	protected function insight() {
		$request       = md5(json_encode($_REQUEST));
		$cache         = $this->cache->get($this->insight_key);
		$cache         = is_array($cache) ? $cache : [];
		$cache_request = $cache[$this->action]['request'] ?? '';
		$cache_changes = $cache[$this->action]['changes'] ?? '';

		// 当前请求数据已更改，更新更改次数、写入缓存
		if ($request != $cache_request) {
			$cache_changes++;

			$cache[$this->action] = ['request' => $request, 'changes' => $cache_changes];
			$this->cache->set($this->insight_key, $cache, $this->period);
		}

		/**
		 * 指定时间范围里，请求数据更改次数超过规定值，立即拦截
		 *
		 * - 检测 $this->count 原因：本缓存数据较为复杂，只能使用 set 更新，导致其过期时间将随之更新。因此数据可能产生累积，导致无法确保有效检测时间范围
		 */
		if ($this->count >= $this->max_request_changes and $cache_changes >= $this->max_request_changes) {
			$this->max_connections = 0;
		}
	}

	/**
	 * 根据请求后缀直接拦截恶意请求，并修正延长设定拦截时间
	 * @since 0.9.39.7
	 */
	protected function defend_extension() {
		$ext = $this->pathinfo['extension'] ?? '';
		if (!$ext) {
			return;
		}

		$threat_extension = ['jsp', 'jspx', 'action', 'asp', 'aspx', 'do', 'cgi', 'exe', 'bak', 'inc', '7z'];
		if (in_array($ext, $threat_extension)) {
			$this->count        = $this->max_connections;
			$this->base_count   = $this->base_count + $this->max_connections;
			$this->blocked_time = 3600 * 24;
		}
	}

	/**
	 * 防护 xmlrpc 端口
	 * @since 0.8.64
	 */
	protected function defend_xmlrpc() {
		if ('POST' != $_SERVER['REQUEST_METHOD']) {
			return;
		}

		$base_name = $this->pathinfo['basename'] ?? '';
		if ('xmlrpc.php' != $base_name and 'wp-comments-post.php' != $base_name) {
			return;
		}

		$this->count = $this->max_connections;
	}

	/**
	 * 核查防护
	 * 单个IP规定时间内返回超限拦截，并记录ip端
	 * IP段累积拦截超限，拦截整个IP段（为避免误杀，IP段拦截时间为IP检测时间段，而非IP拦截时间）
	 */
	protected function defend() {
		// IP段拦截：IP段拦截频率条件固定为单个ip访问频率的三倍
		if ($this->base_count > $this->max_connections * 3) {
			$this->intercept(true);
		}

		// 首次访问
		if (!$this->count) {
			$this->cache_set($this->key, 1, $this->period);
			return;
		}

		// 统计IP段拦截次数，
		if ($this->base_count) {
			$this->cache_inc($this->base_key, 1);
		} else {
			$this->cache_set($this->base_key, 1, $this->period);
		}

		/**
		 * 符合拦截条件：
		 * - 更新当前 IP 记录
		 * - 中断当前 IP 连接
		 */
		if ($this->count >= $this->max_connections) {
			$this->cache_set($this->key, $this->count + 1, $this->blocked_time);
			$this->intercept(false);
		}

		// 非首次访问，但尚未达到拦截条件：累计访问次数
		$this->cache_inc($this->key, 1);
	}

	/**
	 * ### 拦截
	 * - 写入拦截日志
	 * - 阻断访问
	 * @since 0.9.39
	 */
	protected function intercept(bool $ip_base_intercept) {
		$this->write_block_logs();
		http_response_code(429);
		if ($ip_base_intercept) {
			exit('Too Many Requests. Blocked By IP Base : ' . $this->base_count . ' - ' . $this->ip);
		} else {
			exit('Too Many Requests. Blocked By IP : ' . $this->count . ' - ' . $this->ip);
		}
	}

	/**
	 * 记录屏蔽ip的请求信息，以供分析
	 *
	 */
	protected function write_block_logs() {
		// 新增当前IP拦截日志时间及累计拦截次数
		$_REQUEST['wnd_time']   = date('m-d H:i:s', current_time('timestamp'));
		$_REQUEST['wnd_count']  = $this->count + 1;
		$_REQUEST['wnd_server'] = $_SERVER;

		// 移除用户密码，防止意外泄露
		unset($_SERVER['_user_user_pass']);

		$block_logs = array_merge(array_reverse($this->get_block_logs()), [$this->ip => $_REQUEST]);
		$block_logs = array_reverse($block_logs);
		$block_logs = array_slice($block_logs, 0, $this->block_logs_limit);

		$this->cache_set(static::$block_logs_key, $block_logs, 3600 * 24);
	}

	/**
	 * 记录屏蔽ip的请求信息，以供分析
	 *
	 */
	public function get_block_logs(): array{
		$logs = $this->cache_get(static::$block_logs_key);

		return is_array($logs) ? $logs : [];
	}

	/**
	 * 封装实例化内存缓存初始化，以便重写以适配其他内存缓存如 redis
	 */
	protected function cache_init() {
		if (!class_exists('Memcached')) {
			throw new Exception('Memcached is not installed yet');
		}

		$this->cache = new Memcached();
		$this->cache->addServer('localhost', 11211);
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
		return $this->cache->set($key, $value, $expiration);
	}

	/**
	 * 封装内存缓存增加方法，以便重写以适配其他内存缓存如 redis
	 * 新增
	 */
	protected function cache_inc($key, $offset) {
		return $this->cache->increment($key, $offset);
	}

	/**
	 * 封装内存缓存设置方法，以便重写以适配其他内存缓存如 redis
	 * 删除
	 */
	protected function cache_delete($key) {
		return $this->cache->delete($key);
	}

	/**
	 * 重置拦截统计：可用于防止错误拦截已登录用户
	 */
	public function reset() {
		if (!$this->cache) {
			return;
		}

		$this->cache_delete($this->key);
		$this->cache_delete($this->base_key);
	}
}
