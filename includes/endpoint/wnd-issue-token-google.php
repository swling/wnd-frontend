<?php
namespace Wnd\Endpoint;

use Exception;
use Wnd\Component\JWT\GoogleJwtVerifier;
use Wnd\Endpoint\Wnd_Issue_Token_Abstract;

/**
 * App 客户端 Google 登录
 * @since 0.9.91
 */
class Wnd_Issue_Token_Google extends Wnd_Issue_Token_Abstract {

	protected $app_type = 'google';

	protected function get_app_openid(): string {
		$idToken  = $this->data['idToken'] ?? '';
		$clientId = $this->data['clientId'] ?? '';

		if (!$idToken) {
			throw new Exception('idToken 参数缺失');
		}

		if (!$clientId) {
			// throw new Exception('clientId 参数缺失');
		}

		$payload = $this->verify_google_id_token($idToken, $clientId);

		// 设置用户信息
		$this->display_name = $payload->name ?? '';
		$this->avatar_url   = $payload->picture ?? '';
		$this->email        = $payload->email ?? '';

		return $payload->sub;
	}

	/**
	 * 验证 Google 登录的 ID Token
	 *
	 * @param string $idToken 前端传过来的 ID Token
	 * @param string $clientId 你在 Google Cloud Console 里的 OAuth Client ID
	 * @return object|WP_Error 验证成功返回 payload 对象，失败返回 WP_Error
	 */
	private function verify_google_id_token(string $idToken, string $clientId): object {
		$jwk_url   = 'https://www.googleapis.com/oauth2/v3/certs';
		$cache_key = 'google_jwk_keys';
		$cache_ttl = HOUR_IN_SECONDS * 24; // 缓存 1 小时

		// 尝试从 WP 缓存获取
		$jwks = wp_cache_get($cache_key);
		if (!$jwks) {
			// 不在缓存则请求 Google
			$response = wp_remote_get($jwk_url, ['timeout' => 5]);
			if (is_wp_error($response)) {
				throw new Exception('jwk_fetch_failed', '获取 Google JWKs 失败');
			}

			$body = wp_remote_retrieve_body($response);
			$jwks = json_decode($body, true);

			if (!$jwks || empty($jwks['keys'])) {
				throw new Exception('jwk_invalid', 'Google JWKs 数据无效');
			}

			// 存入 WP 缓存
			wp_cache_set($cache_key, $jwks, '', $cache_ttl);
		}

		try {
			$verifier = new GoogleJwtVerifier();
			$decoded  = $verifier->verifyIdToken($idToken, $jwks);

			// ----------------
			// 额外校验
			// ----------------
			if (!in_array($decoded->iss, ['accounts.google.com', 'https://accounts.google.com'], true)) {
				throw new Exception('invalid_iss', '签发方无效');
			}

			if ($decoded->aud !== $clientId) {
				// throw new Exception('invalid_aud', 'aud 不匹配');
			}

			if ($decoded->exp < time()) {
				throw new Exception('expired_token', 'Token 已过期');
			}

			// ✅ 验证通过
			return $decoded;

		} catch (Exception $e) {
			throw new Exception('invalid_token', '验证失败: ' . $e->getMessage());
		}
	}

}
