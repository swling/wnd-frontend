<?php
namespace Wnd\Component\Qcloud;

use Exception;

/**
 * @since 0.8.73
 * 腾讯云产品 API 签名及请求 Trait（API 3.0 V1 版本签名）
 *
 * API PHP 文档
 * @link https://cloud.tencent.com/document/product/1278/46715
 *
 * 在 云API密钥 上申请的标识身份的 SecretId，一个 SecretId 对应唯一的 SecretKey。非主账户需分配对应产品权限。
 * @link https://console.cloud.tencent.com/capi
 *
 */
class QcloudRequest {

	// 腾讯云API Secret ID
	protected $secret_id;

	// 腾讯云API Secret Key
	protected $secret_key;

	// 服务地址（endpoint 域名，不含 Http 协议头）
	protected $endpoint;

	// 请求参数
	protected $params = [];

	/**
	 *构造
	 */
	public function __construct(string $secret_id, string $secret_key, string $endpoint, array $params) {
		$this->secret_id  = $secret_id;
		$this->secret_key = $secret_key;
		$this->endpoint   = $endpoint;
		$this->params     = $params;
	}

	/**
	 * 请求服务器
	 */
	public function request(): array{
		// 添加参数签名
		$this->params['Signature'] = $this->sign($this->params);

		/**
		 * 获取响应报文
		 * application/x-www-form-urlencoded，必须使用签名方法 v1（HmacSHA1 或 HmacSHA256）
		 */
		$request = wp_remote_post('https://' . $this->endpoint,
			[
				'body'    => $this->params,
				'headers' => ['Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8'],
			]
		);
		if (is_wp_error($request)) {
			throw new Exception($request->get_error_message());
		}

		// 解析响应为数组并返回
		return json_decode($request['body'], true);
	}

	/**
	 *@link https://cloud.tencent.com/document/product/1278/46715#2.-.E8.8E.B7.E5.8F.96-api-3.0-v1-.E7.89.88.E6.9C.AC.E7.AD.BE.E5.90.8D
	 *签名
	 */
	protected function sign(array $param): string{
		ksort($param);

		$signStr = 'POST' . $this->endpoint . '/?';
		foreach ($param as $key => $value) {
			$signStr = $signStr . $key . '=' . $value . '&';
		}unset($key, $value);
		$signStr = substr($signStr, 0, -1);

		return base64_encode(hash_hmac('sha1', $signStr, $this->secret_key, true));
	}
}
