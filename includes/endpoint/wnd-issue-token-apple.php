<?php
namespace Wnd\Endpoint;

use Exception;
use Wnd\Component\JWT\GoogleJwtVerifier;
use Wnd\Endpoint\Wnd_Issue_Token_Abstract;

/**
 * App 客户端 Apple 登录
 */
class Wnd_Issue_Token_Apple extends Wnd_Issue_Token_Abstract {

	protected $app_type = 'apple';

	protected function get_app_openid(): string {
		$idToken = $this->data['identityToken'] ?? '';

		if (!$idToken) {
			throw new Exception('idToken 参数缺失');
		}

		$payload = $this->verify_apple_id_token($idToken);

		// 设置用户信息：Apple Token 解码不含 name、头像、email。email 仅在首次授权时提供，且可能为空
		$givenName          = $this->data['givenName'] ?? '';
		$familyName         = $this->data['familyName'] ?? '';
		$this->display_name = trim("$givenName $familyName");
		$this->avatar_url   = '';
		$this->email        = $this->data['email'] ?? '';

		// Apple 用户唯一标识
		return $payload->sub;
	}

	/**
	 * 验证 Apple 登录的 ID Token
	 *
	 * @param string $idToken 前端传过来的 Apple ID Token
	 * @return object 验证成功返回 payload 对象
	 * @throws Exception 验证失败抛出异常
	 */
	private function verify_apple_id_token(string $idToken): object {
		$jwk_url   = 'https://appleid.apple.com/auth/keys';
		$cache_key = 'apple_jwk_keys';

		// 尝试从 WP 缓存获取
		$jwks = wp_cache_get($cache_key);
		if (!$jwks) {
			// 不在缓存则请求 Apple
			$response = wp_remote_get($jwk_url, ['timeout' => 5]);
			if (is_wp_error($response)) {
				throw new Exception('jwk_fetch_failed', '获取 Apple JWKs 失败');
			}

			$body = wp_remote_retrieve_body($response);
			$jwks = json_decode($body, true);

			if (!$jwks || empty($jwks['keys'])) {
				throw new Exception('jwk_invalid', 'Apple JWKs 数据无效');
			}

			// 解析 HTTP 响应头中的 Cache-Control:max-age
			$headers   = wp_remote_retrieve_headers($response);
			$cache_ttl = HOUR_IN_SECONDS; // 默认 1 小时
			if (!empty($headers['cache-control']) && preg_match('/max-age=(\d+)/', $headers['cache-control'], $matches)) {
				$cache_ttl = (int) $matches[1];
			}

			// 存入 WP 缓存
			wp_cache_set($cache_key, $jwks, '', $cache_ttl);
		}

		try {
			// 验证 JWT
			$verifier = new GoogleJwtVerifier();
			$decoded  = $verifier->verifyIdToken($idToken, $jwks);

			// ----------------
			// 额外校验
			// ----------------
			if ($decoded->iss !== 'https://appleid.apple.com') {
				throw new Exception('invalid_iss', '签发方无效');
			}

			if ($decoded->exp < time()) {
				throw new Exception('expired_token', 'Token 已过期');
			}

			// 如果需要可以校验 aud
			// if ($decoded->aud !== 'com.example.app') {
			//     throw new Exception('invalid_aud', 'aud 不匹配');
			// }

			// ✅ 验证通过
			return $decoded;

		} catch (Exception $e) {
			throw new Exception('invalid_token', '验证失败: ' . $e->getMessage());
		}
	}

}
