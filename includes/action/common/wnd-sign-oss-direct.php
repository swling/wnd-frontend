<?php
namespace Wnd\Action\Common;

use Exception;
use Wnd\Action\Wnd_Action;
use Wnd\Getway\Wnd_Object_Storage;
use Wnd\Utility\Wnd_OSS_Handler;

/**
 * 浏览器直传对象存储签名
 * - 随机重命名文件
 * - 返回完整的 URL 及 header
 * - 不写入 WP 附件数据库
 * - @since 0.9.57.12 短期内同一 endpoint 重复上传的文件，直接返回现有文件信息（文件信息基于内存缓存）
 *
 * ## 安全策略 @since 0.9.50.1
 * - 本签名主要应用于临时文件上传存储，通常面向含匿名用户在内的所有用户用户开放
 * - 出于安全考虑，本签名对应的节点不可与站内常规对象存储节点相同
 */
class Wnd_Sign_OSS_Direct extends Wnd_Action {

	/**
	 * 本操作非标准表单请求，无需验证签名
	 */
	protected $verify_sign = false;

	private $oss_sp;
	private $endpoint;
	private $method;
	private $mime_type;
	private $md5;
	private $file_path_name;
	private $file;
	private $query = [];

	/**
	 * 短期类重复上传的文件，直接返回 url 不再存储
	 * - 基于对象存储信息，及文件 md5 判断
	 * - 依赖内存缓存（memcached 及 redis）
	 * @since 0.9.57.10
	 */
	private $is_duplicate_file;
	private $cache_key;

	protected function parse_data() {
		$this->oss_sp   = $this->data['oss_sp'] ?? '';
		$this->endpoint = $this->data['endpoint'] ?? '';
		$this->method   = $this->data['method'] ?? 'PUT';
		if ('PUT' == $this->method) {
			$this->parse_put_data();
		} else {
			$this->parse_delete_data();
		}
	}

	protected function check() {
		// 处于安全考虑，不写入附件的浏览器直传对象存储，不可与站内常规对象存储为同一个节点
		$oss_handler = Wnd_OSS_Handler::get_instance();
		if (!$oss_handler->is_direct_endpoint($this->endpoint)) {
			throw new Exception('This endpoint is not allowed.');
		}
	}

	protected function execute(): array {
		if ('PUT' == $this->method) {
			return $this->sign_put();
		} elseif ('DELETE' == $this->method) {
			return $this->sign_delete();
		}
	}

	private function sign_put(): array {
		$oss = Wnd_Object_Storage::get_instance($this->oss_sp, $this->endpoint);
		$oss->setFilePathName($this->file_path_name);
		$headers    = $oss->generateHeaders($this->method, $this->mime_type, $this->md5);
		$url        = $oss->getFileUri();
		$signed_url = $oss->getFileUri(1800, $this->query);

		/**
		 * - 阿里云 oss 内网地址需要替换
		 * - 腾讯云文档声称会智能解析 @link https://cloud.tencent.com/document/product/436/6224#.E5.86.85.E7.BD.91.E5.92.8C.E5.A4.96.E7.BD.91.E8.AE.BF.E9.97.AE
		 */
		if ('Aliyun' == $this->oss_sp) {
			$internal_url    = str_replace('.aliyuncs.com', '-internal.aliyuncs.com', $url);
			$signed_internal = str_replace('.aliyuncs.com', '-internal.aliyuncs.com', $signed_url);
		} else {
			$internal_url = $url;
		}

		$data = [
			'put_url'         => $url,
			'url'             => $url,
			'signed_url'      => $signed_url,
			'internal'        => $internal_url,
			'signed_internal' => $signed_internal,
			'headers'         => $headers,
			'is_duplicate'    => $this->is_duplicate_file,
			'cache_key'       => $this->cache_key,
		];

		// 将文件信息写入缓存
		if (!$this->is_duplicate_file) {
			$this->set_cache($url, $internal_url, $this->file_path_name);
		}

		return ['status' => 1, 'data' => $data];
	}

	private function sign_delete(): array {
		$path = parse_url($this->file)['path'];
		$oss  = Wnd_Object_Storage::get_instance($this->oss_sp, $this->endpoint);
		$oss->setFilePathName($path);
		$headers = $oss->generateHeaders($this->method);
		$data    = [
			'delete_url' => $oss->getFileUri(),
			'headers'    => $headers,
		];
		return ['status' => 1, 'data' => $data];
	}

	private function parse_delete_data() {
		$this->file = $this->data['file'] ?? '';
	}

	private function parse_put_data() {
		$ext = $this->data['extension'] ?? '';
		if (!$ext) {
			throw new Exception('Invalid file type');
		}
		$this->mime_type = $this->data['mime_type'] ?? '';
		$this->md5       = $this->data['md5'] ?? '';

		// OSS 请求参数（如裁剪变换等，具体参看对象储存文档）
		$query       = $this->data['query'] ?? [];
		$this->query = is_array($query) ? $query : [];

		// 如果文件被缓存（短时期重复上传）
		$this->cache_key = $this->generate_cache_key();
		$cache           = $this->get_cache();
		if (false === $cache) {
			$this->file_path_name = wnd_date('Y/m/d/H/') . uniqid() . '_' . $this->user_id . '.' . $ext;
		} else {
			$this->file_path_name    = $cache['file_path_name'];
			$this->is_duplicate_file = true;
		}
	}

	private function set_cache(string $url, string $internal_url, string $file_path_name): bool {
		$data = [
			'url'            => $url,
			'internal'       => $internal_url,
			'file_path_name' => $file_path_name,
		];

		return wp_cache_set($this->cache_key, $data, 'oss_direct', 3600);
	}

	private function get_cache() {
		return wp_cache_get($this->cache_key, 'oss_direct');
	}

	private function generate_cache_key() {
		return md5($this->endpoint . $this->md5);
	}

	public static function delete_cache(string $cache_key) {
		return wp_cache_delete($cache_key, 'oss_direct');
	}
}
