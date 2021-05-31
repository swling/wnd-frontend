<?php
namespace Wnd\Utility;

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
		if (!in_array($service_provider, static::$service_providers)) {
			throw new Exception('$service_provider Only supports: ' . implode(' | ', static::$service_providers));
		}

		$class_name = '\Wnd\Component\\' . $service_provider . '\\SignatureHelper';
		if (!class_exists($class_name)) {
			throw new Exception(__('未定义', 'wnd') . ':' . $class_name);
		}

		$api_key_method = __CLASS__ . '::get_api_key_' . $service_provider;
		extract($api_key_method());

		return new $class_name($secret_id, $secret_key);
	}

	private static function get_api_key_aliyun(): array{
		$secret_id  = wnd_get_config('aliyun_secretid');
		$secret_key = wnd_get_config('aliyun_secretkey');

		return compact('secret_id', 'secret_key');
	}

	private static function get_api_key_qcloud(): array{
		$secret_id  = wnd_get_config('tencent_secretid');
		$secret_key = wnd_get_config('tencent_secretkey');

		return compact('secret_id', 'secret_key');
	}

	private static function get_api_key_baidubce(): array{
		$secret_id  = wnd_get_config('baidu_secretid');
		$secret_key = wnd_get_config('baidu_secretkey');

		return compact('secret_id', 'secret_key');
	}
}
