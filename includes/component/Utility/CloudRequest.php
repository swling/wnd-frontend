<?php
namespace Wnd\Component\Utility;

use Exception;

/**
 *@since 0.9.30
 *云平台产品签名助手基类
 */
abstract class CloudRequest {
	protected $secretID;
	protected $secretKey;
	protected $timestamp;
	protected $expiration = 1800;
	protected $url;
	protected $method;
	protected $path;
	protected $queryString = '';
	protected $headers     = [];
	protected $body;

	/**
	 *注意为简化代码 $params 仅支持一维数组
	 */
	function __construct(string $accessID, string $secretKey) {
		$this->secretID  = $accessID;
		$this->secretKey = $secretKey;
		$this->timestamp = time();
	}

	public function request(string $url, array $args): array{
		$defaults = [
			'method'  => 'POST',
			'headers' => [],
			'body'    => [],
		];
		$args = array_merge($defaults, $args);

		$url_arr           = parse_url($url);
		$this->url         = $url;
		$this->path        = $url_arr['path'] ?? '';
		$this->queryString = $url_arr['query'] ?? '';

		$this->method                   = strtoupper($args['method']);
		$this->body                     = $args['body'];
		$this->headers                  = $args['headers'];
		$this->headers['host']          = $url_arr['host'];
		$this->headers['Authorization'] = $this->genAuthorization();

		return $this->excuteRequest();
	}

	/**
	 *生成Authorization
	 */
	abstract protected function genAuthorization(): string;

	/**
	 *拆分为独立方法，以便某些情况子类可重写覆盖
	 */
	protected function excuteRequest() {
		$request = \wp_remote_request(
			$this->url,
			[
				'method'  => $this->method,
				'body'    => $this->body,
				'headers' => $this->headers,
			]
		);

		if (is_wp_error($request)) {
			throw new Exception($request->get_error_message());
		}

		// 解析响应为数组并返回
		return json_decode($request['body'], true);
	}
}
