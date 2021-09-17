<?php
namespace Wnd\Getway;

use Exception;
use Wnd\Component\CloudClient\CloudClient;

/**
 * 统一封装云计算平台 API 请求
 * @since 0.9.30
 */
abstract class Wnd_Cloud_Client {

	private static $service_providers = ['Aliyun', 'Qcloud', 'BaiduBce'];

	/**
	 * 自动选择子类处理当前业务
	 *
	 * @since 0.9.36 当外部传入密匙时，覆盖后台设置
	 */
	public static function get_instance(string $service_provider, string $secret_id = '', string $secret_key = ''): CloudClient {
		// 服务商
		static::check_service_provider($service_provider);
		$class_name = '\Wnd\Component\CloudClient\\' . $service_provider;
		if (!class_exists($class_name)) {
			throw new Exception(__('未定义', 'wnd') . ':' . $class_name);
		}

		// 密匙
		if ($secret_id and !$secret_key) {
			throw new Exception('missed secret key');
		}

		if ($secret_key and !$secret_id) {
			throw new Exception('missed secret ID');
		}

		if (!$secret_id or !$secret_key) {
			extract(static::get_api_key($service_provider));
		}

		return new $class_name($secret_id, $secret_key);
	}

	/**
	 * 读取 Access Key
	 */
	public static function get_api_key(string $service_provider): array{
		switch ($service_provider) {
			case 'Aliyun':
				$secret_id  = wnd_get_config('aliyun_secretid');
				$secret_key = wnd_get_config('aliyun_secretkey');
				break;

			case 'Qcloud':
				$secret_id  = wnd_get_config('tencent_secretid');
				$secret_key = wnd_get_config('tencent_secretkey');
				break;

			case 'BaiduBce':
				$secret_id  = wnd_get_config('baidu_secretid');
				$secret_key = wnd_get_config('baidu_secretkey');
				break;

			default:
				throw new Exception('$service_provider Only supports: ' . implode(' | ', static::$service_providers));
				break;
		}

		return compact('secret_id', 'secret_key');
	}

	/**
	 * 检测云平台服务商是否有效
	 */
	public static function check_service_provider(string $service_provider) {
		if (!in_array($service_provider, static::$service_providers)) {
			throw new Exception('Error: [' . $service_provider . ']. Only supports: ' . implode(' | ', static::$service_providers));
		}
	}
}
