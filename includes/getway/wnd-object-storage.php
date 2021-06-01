<?php
namespace Wnd\Getway;

use Exception;
use Wnd\Component\Utility\ObjectStorage;

/**
 *@since 0.9.29
 *第三方云平台生成器
 * - 根据服务商选择对应处理类
 * - 读取并配置云平台 accesskey
 */
abstract class Wnd_Object_Storage {
	// 实例化
	public static function get_instance(string $service_provider, string $endpoint): ObjectStorage {
		switch ($service_provider) {
		case 'OSS':
			$class_name = '\Wnd\Component\Aliyun\AliyunOSS';
			break;

		case 'COS':
			$class_name = '\Wnd\Component\Qcloud\QcloudCOS';
			break;

		default:
			throw new Exception('object storage service invalid');
			break;
		}

		if (class_exists($class_name)) {
			$api_key_method = __CLASS__ . '::get_api_key_' . $service_provider;
			extract($api_key_method());

			return new $class_name($secret_id, $secret_key, $endpoint);
		} else {
			throw new Exception('object storage service invalid');
		}
	}

	private static function get_api_key_oss(): array{
		$secret_id  = wnd_get_config('aliyun_secretid');
		$secret_key = wnd_get_config('aliyun_secretkey');

		return compact('secret_id', 'secret_key');
	}

	private static function get_api_key_cos(): array{
		$secret_id  = wnd_get_config('tencent_secretid');
		$secret_key = wnd_get_config('tencent_secretkey');

		return compact('secret_id', 'secret_key');
	}

	private static function get_api_key_bos(): array{
		$secret_id  = wnd_get_config('baidu_secretid');
		$secret_key = wnd_get_config('baidu_secretkey');

		return compact('secret_id', 'secret_key');
	}
}
