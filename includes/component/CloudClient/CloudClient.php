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
	protected $timeout;
	protected $host;
	protected $path;
	protected $queryString = '';
	protected $headers     = [];
	protected $body;
	protected $response;

	public function __construct(string $secretID, string $secretKey) {
		$this->secretID  = $secretID;
		$this->secretKey = $secretKey;
		$this->timestamp = time();
	}

	public function request(string $url, array $args): array{
		$this->buildRequestParams($url, $args);
		$this->excuteRequest();
		return $this->handleResponse();
	}

	/**
	 * 构建完整的请求参数
	 */
	private function buildRequestParams(string $url, array $args): array{
		$defaults = [
			'method'  => 'POST',
			'headers' => [],
			'body'    => [],
			'timeout' => 10,
		];
		$args = array_merge($defaults, $args);

		$url_arr           = parse_url($url);
		$this->url         = $url;
		$this->host        = $url_arr['host'];
		$this->path        = $url_arr['path'] ?? '';
		$this->queryString = $url_arr['query'] ?? '';

		$this->method                   = strtoupper($args['method']);
		$this->timeout                  = $args['timeout'];
		$this->body                     = $args['body'];
		$this->headers                  = $args['headers'];
		$this->headers['Host']          = $this->host;
		$this->headers['Authorization'] = $this->generateAuthorization();

		return [
			'url'     => $this->url,
			'method'  => $this->method,
			'body'    => $this->body,
			'headers' => $this->headers,
			'timeout' => $this->timeout,
		];
	}

	/**
	 * 生成Authorization
	 */
	abstract protected function generateAuthorization(): string;

	/**
	 * 拆分为独立方法，以便某些情况子类可重写覆盖
	 */
	protected function excuteRequest(): array{
		$request        = new Requests;
		$this->response = $request->request(
			$this->url,
			[
				'method'  => $this->method,
				'body'    => $this->body,
				'headers' => $this->headers,
				'timeout' => $this->timeout,
			]
		);

		return $this->response;
	}

	/**
	 * 解析响应结果为数组数据
	 */
	protected function handleResponse(): array{
		$responseBody = json_decode($this->response['body'], true);
		static::checkResponse($responseBody);

		return $responseBody;
	}

	/**
	 * 根据响应数据核查相关请求是否成功
	 * 若出现错误，则抛出异常
	 */
	abstract protected static function checkResponse(array $responseBody);
}
