<?php
namespace Wnd\Component\CloudClient;

use Wnd\Component\Requests\Requests;

/**
 * 云平台产品签名助手基类
 * 默认在请求 headers 中添加 'Host' 及 'Authorization'
 *
 * @since 0.9.30
 */
abstract class CloudClient {
	protected $secretID;
	protected $secretKey;
	protected $timestamp;
	protected $method;
	protected $url;
	protected $host;
	protected $path;
	protected $queryString = '';
	protected $headers     = [];
	protected $body;

	public function __construct(string $accessID, string $secretKey) {
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
		$this->host        = $url_arr['host'];
		$this->path        = $url_arr['path'] ?? '';
		$this->queryString = $url_arr['query'] ?? '';

		$this->method                   = strtoupper($args['method']);
		$this->body                     = $args['body'];
		$this->headers                  = $args['headers'];
		$this->headers['Host']          = $this->host;
		$this->headers['Authorization'] = $this->genAuthorization();

		return $this->excuteRequest();
	}

	/**
	 * 生成Authorization
	 */
	abstract protected function genAuthorization(): string;

	/**
	 * 拆分为独立方法，以便某些情况子类可重写覆盖
	 */
	protected function excuteRequest(): array{
		$request  = new Requests;
		$response = $request->request(
			$this->url,
			[
				'method'  => $this->method,
				'body'    => $this->body,
				'headers' => $this->headers,
			]
		);

		return json_decode($response['body'], true);
	}
}
