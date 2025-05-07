<?php
namespace Wnd\Utility;

use Exception;
use Wnd\Getway\Wnd_Object_Storage;
use Wnd\Utility\Wnd_Singleton_Trait;

/**
 * ### 服务器上传基本逻辑：
 * - 等 WordPress上 传完成后，根据 attachment id 获取服务器文件路径，上传至oss
 * - 若不保留本地文件，则上传 oss 成功后，删除本地文件
 *
 * ### 浏览器直传基本逻辑
 * - 浏览器直传无需本类中的 【upload_to_oss】，【delete_local_file】方法，但同样需要对链接进行重写，同步删除操作等
 * - 前端发送基本文件信息，请求对应 API 接口，获取 OSS 签名
 * - 前端将文件携带签名，直接发送给对应 OSS 节点
 * - 浏览器直传不需要服务器操作文件，减少文件中转，降低服务器带宽，但签名会暴露。
 *   Aliyun OSS 签名中包含 secretID，也将明文暴露，但签名算法在后端，所以安全性无需担心
 *
 * @since 0.9.29
 * @since 0.9.86 采用独立附件数据表
 */
class Wnd_OSS_Handler {

	use Wnd_Singleton_Trait;

	// 是否保留本地文件
	protected $local_storage;
	protected $service_provider;
	protected $oss_dir              = ''; // 文件在节点中的相对存储路径
	protected $endpoint             = ''; // 储存节点
	protected $oss_base_url         = ''; // 外网访问 URL
	protected $endpoint_private     = ''; // 私有储存节点
	protected $oss_base_url_private = ''; // 私有节点外网 URL
	protected $sign_expires         = 600; // 私有读 URL 链接（签名）有效时间（秒）

	// Configure && Hook
	private function __construct() {
		$this->local_storage        = (int) wnd_get_config('oss_local_storage');
		$this->service_provider     = wnd_get_config('oss_sp');
		$this->oss_dir              = trim(wnd_get_config('oss_dir'), '/');
		$this->endpoint             = wnd_get_config('oss_endpoint');
		$this->oss_base_url         = wnd_get_config('oss_base_url');
		$this->endpoint_private     = wnd_get_config('oss_endpoint_private');
		$this->oss_base_url_private = wnd_get_config('oss_base_url_private');
		$this->sign_expires         = wnd_get_config('oss_sign_expires') ?: $this->sign_expires;

		$this->add_local_storage_hook();

		// 同步删除远程文件
		add_action('before_delete_wnd_attachment', [$this, 'delete_oss_file'], 10, 1);

		// 重写附件链接
		add_filter('wnd_get_attachment_url', [$this, 'filter_attachment_url'], 10, 2);
	}

	/**
	 * 本地文件处理钩子
	 * @since 0.9.35.5
	 */
	private function add_local_storage_hook() {
		// 上传文件
		add_action('wnd_upload_file', [$this, 'upload_to_oss'], 10, 1);
	}

	/**
	 * 移除本地文件处理钩子
	 * - 如前端浏览器直传文件至 OSS 时
	 * @since 0.9.35.5
	 */
	public function remove_local_storage_hook() {
		remove_action('wnd_upload_file', [$this, 'upload_to_oss'], 10, 1);
	}

	/**
	 * 在WordPress上传到本地服务器之后，将文件上传到oss
	 * @since 0.9.85
	 */
	public function upload_to_oss(int $attachment_id) {
		if (-1 == $this->local_storage) {
			return;
		}

		$attachment = wnd_get_attachment($attachment_id);
		// 获取WordPress上传并处理后文件
		$uploadpath = wp_get_upload_dir();
		$file       = $uploadpath['basedir'] . "/$attachment->file_path";
		$is_private = 'file' == $attachment->meta_key;

		try {
			$file_path_name = $this->oss_dir . '/' . $attachment->file_path;
			$object_storage = $this->get_object_storage_instance($is_private);
			$object_storage->setFilePathName($file_path_name);
			$object_storage->uploadFile($file);

			// 删除本地文件
			if (1 != $this->local_storage) {
				return wnd_delete_attachment_file($attachment->ID);
			}
		} catch (Exception $e) {
			/**
			 * @data 2020.10.20
			 * 同步上传失败，则删除本条附件，防止产生孤立附件
			 */
			wnd_delete_attachment($attachment_id);
			exit($e->getMessage() . '@' . __FUNCTION__);
		}
	}

	/**
	 * 判断当前附件是否为私有存储
	 * - 是否开启私有存储
	 * - 当前附件 meta key 是否为 'file'
	 * 即：私有存储仅针对“付费下载”（价格可设置为0）
	 *
	 * 仅支持插件编辑器前端上传
	 * - 上传时将 meta key 写入 Attachment Post 的 post_content_filtered 字段
	 * - @see Action\Wnd_Upload_File
	 * - @see Action\Wnd_Sign_OSS_Upload
	 *
	 * @since 0.9.39
	 */
	public function is_private_storage(int $attachment_id): bool {
		if (!$this->is_private_storage_available()) {
			return false;
		}

		$meta_key = wnd_get_attachment($attachment_id)->meta_key ?? '';

		return 'file' == $meta_key;
	}

	/**
	 * 当前站点配置私有存储是否可用
	 * @since 0.9.39
	 */
	private function is_private_storage_available(): bool {
		if ($this->endpoint_private and wnd_get_config('enable_oss')) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 获取 WP 服务器文件路径，并根据 OSS 存储目录，生成最终的 OSS 存储路径
	 */
	private function parse_file_path_name(string $source_file): string {
		// WP 本地上传文件目录
		$local_base_dir = wp_get_upload_dir()['basedir'];

		return $this->oss_dir . str_replace($local_base_dir, '', $source_file);
	}

	/**
	 * 对象存储实例
	 */
	private function get_object_storage_instance(bool $is_private = false): object {
		if ($is_private and $this->endpoint_private) {
			$endpoint = $this->endpoint_private;
		} else {
			$endpoint = $this->endpoint;
		}

		return Wnd_Object_Storage::get_instance($this->service_provider, $endpoint);
	}

	/**
	 * 删除OSS文件(插件独立附件数据表)
	 * @since 0.9.85
	 */
	public function delete_oss_file(object $attachment) {
		$is_private = 'file' == $attachment->meta_key;

		try {
			$file_path_name = $this->oss_dir . '/' . $attachment->file_path;
			$object_storage = $this->get_object_storage_instance($is_private);
			$object_storage->setFilePathName($file_path_name);
			$object_storage->deleteFile();
		} catch (Exception $e) {
			return $e->getMessage() . '@' . __FUNCTION__;
		}
	}

	/**
	 * 根据用户配置重写附件链接
	 * apply_filters( 'wp_get_attachment_url', $url, $post->ID )
	 *
	 * @since 2019.07.25
	 * @since 0.9.39 新增私有存储
	 */
	public function filter_attachment_url(string $url, int $attachment_id): string {
		$is_private   = $this->is_private_storage($attachment_id);
		$oss_base_url = $is_private ? $this->oss_base_url_private : $this->oss_base_url;
		$oss_file_url = str_replace(wp_get_upload_dir()['baseurl'], $oss_base_url, $url);

		if (!$is_private) {
			return $oss_file_url;
		}

		$file_path_name = parse_url($oss_file_url)['path'];
		$oss            = $this->get_object_storage_instance($is_private);
		$oss->setFilePathName($file_path_name);
		return $oss->getFileUri($this->sign_expires);
	}

	/**
	 * 对象存储图片处理。若指定云平台不支持图像处理则返回原链接
	 * @since 2019.07.26
	 */
	private function resize_image(string $img_url, int $width, int $height): string {
		return $this->get_object_storage_instance()->resizeImage($img_url, $width, $height);
	}

	/**
	 * 根据文件名，生成直传 OSS 所需的参数
	 * @since 0.9.33.7
	 */
	public function sign_oss_request(string $method, string $local_file, string $content_type = '', string $md5 = '', bool $is_private = false): array {
		// OSS 存储路径
		$file_path_name = $this->parse_file_path_name($local_file);

		// 如果当前未配置私有存储，忽略传参
		$is_private = ($this->is_private_storage_available() and $is_private);

		// 获取 OSS 签名
		$oss = $this->get_object_storage_instance($is_private);
		$oss->setFilePathName($file_path_name);
		$headers    = $oss->generateHeaders($method, ['Content-Type' => $content_type, 'Content-MD5' => $md5]);
		$url        = $oss->getFileUri();
		$signed_url = $is_private ? $oss->getFileUri($this->sign_expires) : '';

		return [
			'signed_url' => $signed_url,
			'url'        => $url,
			'headers'    => $headers,
		];
	}

	/**
	 * 判断指定 OSS 节点是否为站外节点：即存储节点不为当前插件配置的存储节点
	 * 对象储存的 bucket 为全局唯一，且作为签名依据
	 * @since 0.9.50.1
	 */
	public function is_direct_endpoint(string $endpoint): bool {
		$buckets = wnd_get_config('oss_direct_bucket');
		$buckets = explode(',', $buckets);
		$bucket  = static::parse_bucket($endpoint);

		return in_array($bucket, $buckets);
	}

	/**
	 * 根据 endpoint 域名解析出 bucket
	 */
	private static function parse_bucket(string $endpoint): string {
		$parsedUrl = parse_url($endpoint);
		if (!isset($parsedUrl['host'])) {
			return '';
		}

		$host = explode('.', $parsedUrl['host']);
		return $host[0] ?? '';
	}
}
