<?php
namespace Wnd\Utility;

use Exception;
use Wnd\Getway\Wnd_Object_Storage;
use Wnd\Utility\Wnd_Singleton_Trait;

/**
 *@since 0.9.29
 *基本逻辑：
 *等WordPress上传完成后（需要用到WordPress的尺寸裁剪等功能）
 *根据attachment id获取服务器文件路径，上传至oss
 *上传oss成功后，删除本地文件
 */
class Wnd_Attachment_Handler {

	use Wnd_Singleton_Trait;

	// 是否保留本地文件
	protected $local_storage;
	protected $service_provider;
	protected $endpoint     = ''; // COS 节点
	protected $oss_dir      = ''; // 文件在节点中的相对存储路径
	protected $oss_base_url = ''; // 外网访问 URL

	// Configure && Hook
	private function __construct() {
		$this->local_storage    = wnd_get_config('oss_local_storage');
		$this->service_provider = wnd_get_config('oss_sp');
		$this->endpoint         = wnd_get_config('oss_endpoint');
		$this->oss_dir          = trim(wnd_get_config('oss_dir'), '/'); // 文件在节点中的相对存储路径
		$this->oss_base_url     = wnd_get_config('oss_base_url'); // 外网访问 URL

		// 上传文件
		add_action('add_attachment', array($this, 'upload_to_oss'), 10, 1);
		// 删除本地文件
		add_action('added_post_meta', array($this, 'delete_local_file'), 10, 4);
		// 同步删除远程文件
		add_action('delete_attachment', array($this, 'delete_oss_file'), 10, 1);

		// 重写附件链接
		add_filter('wp_get_attachment_metadata', array($this, 'filter_attachment_meta'), 10, 1);
		add_filter('wp_get_attachment_url', array($this, 'filter_attachment_url'), 10, 2);
		add_filter('wp_calculate_image_srcset', array($this, 'filter_wp_srcset'), 10, 1);
		add_filter('wp_get_attachment_image_src', array($this, 'filter_attachment_image_src'), 10, 1);
	}

	/**
	 *@since 2019.07.26
	 *在WordPress上传到本地服务器之后，将文件上传到oss
	 **/
	public function upload_to_oss($post_ID) {
		// 获取WordPress上传并处理后文件
		$file = get_attached_file($post_ID);

		try {
			$file_path_name = $this->parse_file_path_name($file);
			$object_storage = $this->get_object_storage_instance();
			$object_storage->setFilePathName($file_path_name);
			$object_storage->uploadFile($file);
		} catch (Exception $e) {
			/**
			 *@data 2020.10.20
			 *同步上传失败，则删除本条附件，防止产生孤立附件
			 */
			wp_delete_attachment($post_ID, true);
			exit($e->getMessage() . '@' . __FUNCTION__);
		}
	}

	/**
	 *@since WordPress读取本地文件信息并存入字段后
	 *根据用户设定选择是否清理本地文件
	 *
	 *@see do_action( "added_{$meta_type}_meta", $mid, $object_id, $meta_key, $_meta_value )
	 **/
	public function delete_local_file($meta_id, $post_ID, $meta_key, $meta_value) {
		if ('_wp_attachment_metadata' != $meta_key or $this->local_storage) {
			return;
		}

		/**
		 *$meta = wp_get_attachment_metadata($post_ID);
		 *因为插件对 wp_get_attachment_metadata 进行了oss远程重写，因此此处不可采用 wp_get_attachment_metadata获取
		 */
		$meta         = $meta_value;
		$backup_sizes = get_post_meta($post_ID, '_wp_attachment_backup_sizes', true);
		$file         = get_attached_file($post_ID);
		wp_delete_attachment_files($post_ID, $meta, $backup_sizes, $file);
	}

	/**
	 *@since 2019.07.26
	 *删除OSS文件
	 *
	 *do_action( 'delete_attachment', $post_id );
	 **/
	public function delete_oss_file($post_ID) {

		// 获取WordPress文件信息，并替换字符后，设定oss文件存储路径
		$file = get_attached_file($post_ID);

		try {
			$file_path_name = $this->parse_file_path_name($file);
			$object_storage = $this->get_object_storage_instance();
			$object_storage->setFilePathName($file_path_name);
			$object_storage->deleteFile();
		} catch (Exception $e) {
			return $e->getMessage() . '@' . __FUNCTION__;
		}
	}

	/**
	 *@since 2019.07.25
	 *替换wordpress file meta
	 */
	public function filter_attachment_meta($data): array{
		if (empty($data['sizes']) || (wp_debug_backtrace_summary(null, 4, false)[0] == 'wp_delete_attachment')) {
			return $data;
		}

		/**
		 *
		 *WordPress的缩略图仅保存了文件名，不包含日期信息，即使文件是按月归档，路径信息也仅在data['file']中
		 *@since 07.26.19：34
		 */
		$file = basename($data['file']);

		foreach ($data['sizes'] as $size => $info) {
			$data['sizes'][$size]['file'] = $file;
		}

		return $data;
	}

	/**
	 *@since 2019.07.26
	 *对象存储图片处理。若指定云平台不支持图像处理则返回原链接
	 */
	protected function resize_image($img_url, $width, $height): string {
		return $this->get_object_storage_instance()->resizeImage($img_url, $width, $height);
	}

	/**
	 *@since 2019.07.25
	 *根据用户配置重写附件链接
	 * apply_filters( 'wp_get_attachment_url', $url, $post->ID )
	 */
	public function filter_attachment_url($url, $post_ID): string {
		return str_replace(wp_get_upload_dir()['baseurl'], $this->oss_base_url, $url);
	}

	/**
	 *@since 2019.07.25
	 *wp_get_attachment_image
	 *return apply_filters( 'wp_get_attachment_image_src', $image, $attachment_id, $size, $icon );
	 */
	public function filter_attachment_image_src($image): array{
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
	 *对象存储实例
	 */
	protected function get_object_storage_instance(): object {
		return Wnd_Object_Storage::get_instance($this->service_provider, $this->endpoint);
	}

	/**
	 *获取 WP 服务器文件路径，并根据 OSS 存储目录，生成最终的 OSS 存储路径
	 */
	protected function parse_file_path_name(string $source_file): string{
		// WP 本地上传文件目录
		$local_base_dir = wp_get_upload_dir()['basedir'];

		return $this->oss_dir . str_replace($local_base_dir, '', $source_file);
	}
}
