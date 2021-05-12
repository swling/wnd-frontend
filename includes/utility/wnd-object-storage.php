<?php
namespace Wnd\Utility;

use Exception;

/**
 *@since 0.9.29
 *第三方云平台对象存储抽象基类，子类需完成如下三个方法
 * - 同步上传
 * - 同步删除
 * - 对图像进行裁剪（通常云平台均支持 uri 参数实现动态裁剪）
 */
abstract class Wnd_Object_Storage {
	protected $endpoint     = ''; // COS 节点
	protected $oss_dir      = ''; // 文件在节点中的相对存储路径
	protected $oss_base_url = ''; // 外网访问 URL

	// 实例化
	public static function get_instance() {
		$service    = wnd_get_config('oss_sp');
		$class_name = '\Wnd\Getway\OSS\\' . $service;
		if (class_exists($class_name)) {
			return new $class_name();
		}

		throw new Exception('object storage service invali');
	}

	/**
	 *获取 WP 服务器文件路径，并根据 OSS 存储目录，生成最终的 OSS 存储路径
	 */
	protected function parse_file_path_name(string $source_file): string{
		// WP 本地上传文件目录
		$local_base_dir = wp_get_upload_dir()['basedir'];
		$oss_dir        = $this->oss_dir ? ('/' . $this->oss_dir) : '';

		return $oss_dir . str_replace($local_base_dir, '', $source_file);
	}

	/**
	 *@since 2019.07.26
	 *在WordPress上传到本地服务器之后，将文件上传到oss
	 **/
	abstract public function upload_file(string $source_file);

	/**
	 *@since 2019.07.26
	 *删除OSS文件
	 *
	 *do_action( 'delete_attachment', $post_id );
	 **/
	abstract public function delete_file(string $source_file);

	/**
	 *@since 2019.07.26
	 *云平台图片处理
	 */
	abstract public function resize_image(string $image_url, int $width, int $height): string;

	/**
	 *@since 2019.07.25
	 *根据用户配置重写附件链接
	 * apply_filters( 'wp_get_attachment_url', $url, $post->ID )
	 */
	public function rewrite_attachment_url(string $url, int $post_ID): string {
		return str_replace(wp_get_upload_dir()['baseurl'], $this->oss_base_url, $url);
	}
}
