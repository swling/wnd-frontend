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

	// Configure && Hook
	private function __construct() {
		$this->local_storage        = (int) wnd_get_config('oss_local_storage');
		$this->service_provider     = wnd_get_config('oss_sp');
		$this->oss_dir              = trim(wnd_get_config('oss_dir'), '/');
		$this->endpoint             = wnd_get_config('oss_endpoint');
		$this->oss_base_url         = wnd_get_config('oss_base_url');
		$this->endpoint_private     = wnd_get_config('oss_endpoint_private');
		$this->oss_base_url_private = wnd_get_config('oss_base_url_private');

		$this->add_local_storage_hook();

		// 同步删除远程文件
		add_action('delete_attachment', [$this, 'delete_oss_file'], 10, 1);

		// 重写附件链接
		add_filter('wp_get_attachment_metadata', [$this, 'filter_attachment_meta'], 10, 1);
		add_filter('wp_get_attachment_url', [$this, 'filter_attachment_url'], 10, 2);
		add_filter('wp_calculate_image_srcset', [$this, 'filter_wp_srcset'], 10, 1);
		add_filter('wp_get_attachment_image_src', [$this, 'filter_attachment_image_src'], 10, 1);
	}

	/**
	 * 本地文件处理钩子
	 * @since 0.9.35.5
	 */
	private function add_local_storage_hook() {
		// 上传文件
		add_action('add_attachment', [$this, 'upload_to_oss'], 10, 1);

		/**
		 * 生成本地文件 meta 之后 删除文件
		 * @see apply_filters( 'wp_generate_attachment_metadata', $metadata, $attachment_id, 'create' );
		 */
		add_filter('wp_generate_attachment_metadata', [$this, 'delete_local_file'], 10, 2);
	}

	/**
	 * 移除本地文件处理钩子
	 * - 如前端浏览器直传文件至 OSS 时
	 * @since 0.9.35.5
	 */
	public function remove_local_storage_hook() {
		remove_action('add_attachment', [$this, 'upload_to_oss'], 10);
		remove_filter('wp_generate_attachment_metadata', [$this, 'delete_local_file'], 10);
	}

	/**
	 * 在WordPress上传到本地服务器之后，将文件上传到oss
	 * @since 2019.07.26
	 */
	public function upload_to_oss(int $attachment_id) {
		// 获取WordPress上传并处理后文件
		$file       = get_attached_file($attachment_id);
		$is_private = $this->is_private_storage($attachment_id);

		try {
			$file_path_name = $this->parse_file_path_name($file);
			$object_storage = $this->get_object_storage_instance($is_private);
			$object_storage->setFilePathName($file_path_name);
			$object_storage->uploadFile($file);
		} catch (Exception $e) {
			/**
			 * @data 2020.10.20
			 * 同步上传失败，则删除本条附件，防止产生孤立附件
			 */
			wp_delete_attachment($attachment_id, true);
			exit($e->getMessage() . '@' . __FUNCTION__);
		}
	}

	/**
	 * 根据用户设定选择是否清理本地文件
	 * @see do_action( "added_{$meta_type}_meta", $mid, $object_id, $meta_key, $_meta_value )
	 * @since WordPress读取本地文件信息并存入字段后
	 */
	public function delete_local_file(array $data, int $attachment_id): array{
		if ($this->local_storage > 0) {
			return $data;
		}

		/**
		 * $meta = wp_get_attachment_metadata($post_ID);
		 * 因为插件对 wp_get_attachment_metadata 进行了oss远程重写，因此此处不可采用 wp_get_attachment_metadata获取
		 */
		$meta         = get_post_meta($attachment_id, '_wp_attachment_metadata', true);
		$backup_sizes = get_post_meta($attachment_id, '_wp_attachment_backup_sizes', true);
		$file         = get_attached_file($attachment_id);
		$delete       = wp_delete_attachment_files($attachment_id, $meta, $backup_sizes, $file);
		if (!$delete) {
			throw new Exception('wp_delete_attachment_files filed @' . __FUNCTION__);
		}

		return $data;
	}

	/**
	 * 删除OSS文件
	 * do_action( 'delete_attachment', $post_id );
	 * @since 2019.07.26
	 */
	public function delete_oss_file(int $attachment_id) {
		$file       = get_attached_file($attachment_id);
		$is_private = $this->is_private_storage($attachment_id);

		try {
			$file_path_name = $this->parse_file_path_name($file);
			$object_storage = $this->get_object_storage_instance($is_private);
			$object_storage->setFilePathName($file_path_name);
			$object_storage->deleteFile();
		} catch (Exception $e) {
			return $e->getMessage() . '@' . __FUNCTION__;
		}
	}

	/**
	 * 替换wordpress file meta
	 * @since 2019.07.25
	 */
	public function filter_attachment_meta(array $data): array{
		if (empty($data['sizes']) || (wp_debug_backtrace_summary(null, 4, false)[0] == 'wp_delete_attachment')) {
			return $data;
		}

		/**
		 *
		 * WordPress的缩略图仅保存了文件名，不包含日期信息，即使文件是按月归档，路径信息也仅在data['file']中
		 * @since 07.26.19：34
		 */
		$file = basename($data['file']);

		foreach ($data['sizes'] as $size => $info) {
			$data['sizes'][$size]['file'] = $file;
		}

		return $data;
	}

	/**
	 * 对象存储图片处理。若指定云平台不支持图像处理则返回原链接
	 * @since 2019.07.26
	 */
	protected function resize_image(string $img_url, int $width, int $height): string {
		return $this->get_object_storage_instance()->resizeImage($img_url, $width, $height);
	}

	/**
	 * 根据用户配置重写附件链接
	 * apply_filters( 'wp_get_attachment_url', $url, $post->ID )
	 *
	 * @since 2019.07.25
	 * @since 0.9.39 新增私有存储
	 */
	public function filter_attachment_url(string $url, int $attachment_id): string{
		$is_private   = $this->is_private_storage($attachment_id);
		$oss_base_url = $is_private ? $this->oss_base_url_private : $this->oss_base_url;
		$oss_file_url = str_replace(wp_get_upload_dir()['baseurl'], $oss_base_url, $url);

		return $oss_file_url;
	}

	/**
	 * wp_get_attachment_image
	 * return apply_filters( 'wp_get_attachment_image_src', $image, $attachment_id, $size, $icon );
	 * @since 2019.07.25
	 */
	public function filter_attachment_image_src(array $image): array{
		$oss_image = [
			$this->resize_image($image[0], $image[1], $image[2]),
			$image[1],
			$image[2],
		];

		return $oss_image;
	}

	/**
	 * WordPress后台附件列表使用了srcset，由于我们采用了远程图片，需禁用此功能
	 * 否则无法正常显示，即使已通过wp_get_attachment_url filter重写链接
	 */
	public function filter_wp_srcset($sources) {
		return false;
	}

	/**
	 * 对象存储实例
	 */
	protected function get_object_storage_instance(bool $is_private = false): object{
		$endpoint = $is_private ? $this->endpoint_private : $this->endpoint;
		return Wnd_Object_Storage::get_instance($this->service_provider, $endpoint);
	}

	/**
	 * 获取 WP 服务器文件路径，并根据 OSS 存储目录，生成最终的 OSS 存储路径
	 */
	protected function parse_file_path_name(string $source_file): string{
		// WP 本地上传文件目录
		$local_base_dir = wp_get_upload_dir()['basedir'];

		return $this->oss_dir . str_replace($local_base_dir, '', $source_file);
	}

	/**
	 * 根据文件名，生成直传 OSS 所需的参数
	 * @since 0.9.33.7
	 */
	public function get_oss_sign_params(string $method, string $local_file, string $content_type = '', string $md5 = '', bool $is_private = false): array{
		// OSS 存储路径
		$file_path_name = $this->parse_file_path_name($local_file);

		// 获取 OSS 签名
		$oss = $this->get_object_storage_instance($is_private);
		$oss->setFilePathName($file_path_name);
		$headers = $oss->generateHeaders($method, $content_type, $md5);
		$url     = $oss->getFileUri();

		return [
			'url'     => $url,
			'headers' => $headers,
		];
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
	private function is_private_storage(int $attachment_id): bool {
		if (!$this->endpoint_private) {
			return false;
		}

		$meta_key = get_post($attachment_id)->post_content_filtered ?? '';

		return 'file' == $meta_key;
	}
}
