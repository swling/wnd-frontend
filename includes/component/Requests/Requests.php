<?php
namespace Wnd\Component\Requests;

use Exception;

/**
 * Curl Request
 */
class Requests {

	private $curl;

	private static $defaults = [
		'method'   => 'GET',
		'headers'  => [],
		'body'     => [],
		'timeout'  => 10,
		'filename' => '',
		'referer'  => '',
	];

	private $args;

	private $file;

	public function request(string $url, array $args): array{
		$this->initRequest($url, $args);
		$method = strtoupper($this->args['method']);

		switch ($method) {
			case 'GET':
				$this->get();
				break;
			case 'POST':
				$this->post();
				break;
			case 'PUT':
				$this->PUT();
				break;
			case 'DELETE':
				$this->DELETE();
				break;

			default:
				throw new Exception('Invalid method：' . $method);
				break;
		}

		return $this->execute_request();
	}

	/**
	 * Init
	 *
	 */
	private function initRequest(string $url, array $args) {
		$this->args = array_merge(static::$defaults, $args);
		$headers    = static::arrayToHeaders($this->args['headers']);

		$this->curl = curl_init($url);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true); //返回字符串,而不直接输出
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->args['timeout']);
		if ($this->args['body']) {
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->args['body']);
		}
		if ($this->args['referer']) {
			curl_setopt($this->curl, CURLOPT_REFERER, $this->args['referer']);
		}
		// curl_setopt($this->$curl, CURLOPT_SSL_VERIFYPEER, false); //不验证对等证书
		// curl_setopt($this->$curl, CURLOPT_SSL_VERIFYHOST, 0); //不检查服务器SSL证书
	}

	/**
	 * 将数组键值对转为 curl headers 数组
	 *
	 */
	private static function arrayToHeaders(array $headers): array{
		$result = [];
		foreach ($headers as $key => $value) {
			$result[] = $key . ':' . $value;
		}

		return $result;
	}

	/**
	 * Get
	 */
	private function get() {
		curl_setopt($this->curl, CURLOPT_HTTPGET, true); // 设置请求方式为 GET
	}

	/**
	 * Post
	 */
	private function post() {
		curl_setopt($this->curl, CURLOPT_POST, true); // 设置请求方式为 POST
	}

	/**
	 * PUT
	 */
	private function put() {
		curl_setopt($this->curl, CURLOPT_PUT, true); //设置为PUT请求

		if (!$this->args['filename']) {
			return;
		}

		$this->file = fopen($this->args['filename'], 'rb');
		$filesize   = filesize($this->args['filename']);

		curl_setopt($this->curl, CURLOPT_INFILE, $this->file); //设置资源句柄
		curl_setopt($this->curl, CURLOPT_INFILESIZE, $filesize);
	}

	/**
	 * Delete
	 */
	private function delete() {
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
	}

	/**
	 * excute
	 */
	private function execute_request(): array{
		// curl_setopt($this->curl, CURLOPT_HEADER, true); // 开启header信息以供调试
		// curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);
		$body    = curl_exec($this->curl);
		$headers = curl_getinfo($this->curl);

		if ($this->file) {
			fclose($this->file);
		}

		if (curl_errno($this->curl)) {
			throw new Exception('Curl error: ' . curl_error($this->curl));
		}
		curl_close($this->curl);

		return compact('headers', 'body');
	}
}
