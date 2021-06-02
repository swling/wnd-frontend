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
		switch (strtoupper($service_provider)) {
		case 'OSS':
			$class_name = '\Wnd\Component\Aliyun\AliyunOSS';
			$secret_id  = wnd_get_config('aliyun_secretid');
			$secret_key = wnd_get_config('aliyun_secretkey');
			break;

		case 'COS':
			$class_name = '\Wnd\Component\Qcloud\QcloudCOS';
			$secret_id  = wnd_get_config('tencent_secretid');
			$secret_key = wnd_get_config('tencent_secretkey');
			break;

		default:
			throw new Exception('object storage service invalid');
			break;
		}

		if (class_exists($class_name)) {
			return new $class_name($secret_id, $secret_key, $endpoint);
		} else {
			throw new Exception('object storage service invalid');
		}
	}
}
