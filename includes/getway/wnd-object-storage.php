<?php
namespace Wnd\Getway;

use Exception;
use Wnd\Component\CloudObjectStorage\CloudObjectStorage;
use Wnd\Getway\Wnd_Cloud_Client;

/**
 * 第三方云平台对象存储生成器
 * - 根据服务商选择对应处理类
 * - 读取并配置云平台 accesskey
 * @since 0.9.29
 * @since 0.9.36 当外部传入密匙时，覆盖后台设置
 */
abstract class Wnd_Object_Storage {

	public static function get_instance(string $service_provider, string $endpoint, string $secret_id = '', string $secret_key = ''): CloudObjectStorage{
		// 服务商
		Wnd_Cloud_Client::check_service_provider($service_provider);
		$class_name = '\Wnd\Component\CloudObjectStorage\\' . $service_provider;
		if (!class_exists($class_name)) {
			throw new Exception($service_provider . ' object storage service invalid');
		}

		// 密匙
		if ($secret_id and !$secret_key) {
			throw new Exception('missed secret key');
		}

		if ($secret_key and !$secret_id) {
			throw new Exception('missed secret ID');
		}

		if (!$secret_id or !$secret_key) {
			extract(Wnd_Cloud_Client::get_api_key($service_provider));
		}

		return new $class_name($secret_id, $secret_key, $endpoint);
	}
}
