<?php
namespace Wnd\Utility;

use ErrorException;
use Exception;

/**
 * @since 0.9.59.3
 *
 * 错误处理类（单例模式）
 * - 面向用户：关闭错误显示
 * - 面向开发者：记录所有错误
 *
 * 注：
 * - 为最大程度记录代码中的错误，此类应首先加载，故类中不得依赖项目其他文件
 *
 * @link https://www.php.net/manual/zh/function.set-error-handler.php
 * @link https://www.php.net/manual/zh/ini.list.php
 */
class Wnd_Error_Handler {

	private static $instance;

	public static function get_instance() {
		if (!static::$instance) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	private function __construct() {
		// 面向用户：关闭错误显示；面向开发者：记录所有错误
		if (WP_DEBUG_DISPLAY) {
			ini_set('display_errors', 1);
		} elseif (null !== WP_DEBUG_DISPLAY) {
			ini_set('display_errors', 0);
		}

		error_reporting(E_ALL);
		register_shutdown_function(__CLASS__ . '::check_for_fatal');
		set_error_handler(__CLASS__ . '::log_error');
		set_exception_handler(__CLASS__ . '::log_exception');
	}

	public static function log_exception(Exception $e) {
		$error = 'Type: ' . get_class($e) . "; Message: {$e->getMessage()}; File: {$e->getFile()}; Line: {$e->getLine()};";
		$error .= 'Request from ' . static::get_client_ip() . '. @' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$html = '<article class="column message is-danger">';
		$html .= '<div class="message-header">';
		$html .= '<p>异常</p>';
		$html .= '</div>';
		$html .= '<div class="message-body">' . $error . '</div>';
		$html .= '</article>';
		echo $html;

		static::write_log($error, 'wnd_exception');
	}

	/**
	 * Checks for a fatal error, work around for set_error_handler not working on fatal errors.
	 */
	public static function check_for_fatal() {
		$error = error_get_last();
		if (!$error) {
			return;
		}

		if ($error['type'] == E_ERROR) {
			static::log_exception(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
			exit();
		}
	}

	/**
	 * Error handler
	 */
	public static function log_error(int $code, string $str, string $file, int $line) {
		$log = 'Type: ' . $code . "; Message: {$str}; File: {$file}; Line: {$line};";

		/**
		 * WP 内核 8129 错误：未来版本中可能无法正常工作的代码警告
		 * - 主要为 WP 内核代码中存在的一些 PHP 版本兼容性警告
		 * - 此类警告只能等待 WP 官方升级，不宜自行处理，故不记录日志
		 *
		 * @link https://www.php.net/manual/zh/errorfunc.constants.php
		 */
		if (8192 == $code and str_starts_with($file, ABSPATH . WPINC)) {
			return false;
		}

		static::write_log($log, 'wnd_php_error');
	}

	/**
	 * 记录错误日志
	 * @since 0.9.38
	 */
	public static function write_log(string $msg, string $file_name = 'wnd_error') {
		$wnd_option       = get_option('wnd');
		$enable_error_log = $wnd_option['enable_error_log'] ?? false;
		if (!$enable_error_log) {
			return;
		}

		@error_log($msg . ' @' . wp_date('Y-m-d H:i:s', time()) . "\n", 3, WP_PLUGIN_DIR . '/' . $file_name . '.log');
	}

	/**
	 * 获取用户ip
	 * @since 初始化
	 *
	 * @return 	string 	IP address
	 */
	private static function get_client_ip(): string{
		$ip = '';
		if (isset($_SERVER)) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['REMOTE_ADDR']);
		}

		return $ip ?: '';
	}

}
