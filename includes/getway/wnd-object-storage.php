<?php
namespace Wnd\Getway;

use Exception;
use Wnd\Component\Utility\CloudObjectStorage;
use Wnd\Getway\Wnd_Cloud_API;

/**
 *@since 0.9.29
 *第三方云平台对象存储生成器
 * - 根据服务商选择对应处理类
 * - 读取并配置云平台 accesskey
 */
abstract class Wnd_Object_Storage {
	// 实例化
	public static function get_instance(string $service_provider, string $endpoint): CloudObjectStorage{
		Wnd_Cloud_API::check_service_provider($service_provider);

		$class_name = '\Wnd\Component\\' . $service_provider . '\ObjectStorage';
		$api_keys   = Wnd_Cloud_API::get_api_key($service_provider);

		if (class_exists($class_name)) {
			extract($api_keys);
			return new $class_name($secret_id, $secret_key, $endpoint);
		} else {
			throw new Exception($service_provider . ' object storage service invalid');
		}
	}
}
