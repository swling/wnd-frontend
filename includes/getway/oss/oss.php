<?php
namespace Wnd\Getway\OSS;

use Wnd\Component\Aliyun\AliyunOSS;
use Wnd\Utility\Wnd_Object_Storage;

/**
 *阿里云 OSS
 *@since 0.9.29
 */
class OSS extends Wnd_Object_Storage {

	private $secret_id  = ''; //"云 API 密钥 SecretId";
	private $secret_key = ''; //"云 API 密钥 SecretKey";

	// 初始化
	public function __construct() {
		$this->secret_id    = wnd_get_config('aliyun_secretid');
		$this->secret_key   = wnd_get_config('aliyun_secretkey');
		$this->endpoint     = wnd_get_config('oss_endpoint');
		$this->oss_dir      = trim(wnd_get_config('oss_dir'), '/'); // 文件在节点中的相对存储路径
		$this->oss_base_url = wnd_get_config('oss_base_url'); // 外网访问 URL
	}

	/**
	 *PUT
	 */
	public function upload_file(string $source_file) {
		$file_path_name = $this->parse_file_path_name($source_file);
		$oss            = $this->get_oss_instance();
		$oss->set_file_path_name($file_path_name);
		$oss->upload_file($source_file);
	}

	/**
	 * Delete
	 *
	 **/
	public function delete_file(string $source_file) {
		$file_path_name = $this->parse_file_path_name($source_file);
		$oss            = $this->get_oss_instance();
		$oss->set_file_path_name($file_path_name);
		$oss->delete_file();
	}

	/**
	 *云平台图片缩放处理
	 */
	public function resize_image(string $image_url, int $width, int $height): string {
		return "{$image_url}?x-oss-process=image/resize,m_fill,h_{$height},w_{$width}";
	}

	/**
	 *oss 实例
	 */
	protected function get_oss_instance(): AliyunOSS {
		return new AliyunOSS($this->secret_id, $this->secret_key, $this->endpoint);
	}
}
