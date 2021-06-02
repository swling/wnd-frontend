<?php
namespace Wnd\Getway;

use Exception;
use Wnd\Component\Utility\CloudRequest;

/**
 *@since 0.9.30
 *统一封装云计算平台 API 请求
 */
abstract class Wnd_Cloud_API {

	private static $service_providers = ['Aliyun', 'Qcloud', 'BaiduBce'];

	/**
	 *自动选择子类处理当前业务
	 */
	public static function get_instance(string $service_provider): CloudRequest {
		static::check_service_provider($service_provider);

		$class_name = '\Wnd\Component\\' . $service_provider . '\\SignatureHelper';
		if (!class_exists($class_name)) {
			throw new Exception(__('未定义', 'wnd') . ':' . $class_name);
		}

		extract(static::get_api_key($service_provider));

		return new $class_name($secret_id, $secret_key);
	}

	/**
	 *读取 Access Key
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
	 *检测云平台服务商是否有效
	 */
	public static function check_service_provider(string $service_provider) {
		if (!in_array($service_provider, static::$service_providers)) {
			throw new Exception('Error: [' . $service_provider . ']. Only supports: ' . implode(' | ', static::$service_providers));
		}
	}
}
