<?php
namespace Wnd\Component\JWT;

use Exception;

/**
 * Google JWT 验证类（OpenSSL 验签 + 外部传入 JWK）
 *
 * 用途：
 * - 验证 Google 前端传来的 idToken 签名
 * - 返回 payload，可用于延签
 * - 仅支持 RS256
 */
class GoogleJwtVerifier {

	/**
	 * 验证 Google ID Token
	 *
	 * @param string $idToken 前端传来的 JWT
	 * @param array  $jwks    Google JWKS 集合  @see https://www.googleapis.com/oauth2/v3/certs
	 * @return object         JWT payload
	 * @throws Exception
	 */
	public function verifyIdToken(string $idToken, array $jwks): object {
		$jwk = $this->getJwkForToken($idToken, $jwks);
		$pem = $this->jwkToPem($jwk);
		return $this->verifyWithOpenSSL($idToken, $pem);
	}

/**
 * 从 Google JWK 集合中提取与 ID Token 匹配的 JWK
 *
 * @param string $idToken
 * @param array  $jwks
 * @return array
 * @throws Exception
 */
	protected function getJwkForToken(string $idToken, array $jwks): array {
		// 解析 JWT 头部
		$parts = explode('.', $idToken);
		if (count($parts) !== 3) {
			throw new Exception('Invalid JWT format');
		}

		$header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
		if (!isset($header['kid'])) {
			throw new Exception("JWT header missing 'kid'");
		}

		if ($header['alg'] !== 'RS256') {
			throw new Exception('Unsupported JWT algorithm: ' . $header['alg']);
		}

		$kid = $header['kid'];

		// 遍历 JWK 集合，找到 kid 匹配的公钥
		foreach ($jwks['keys'] as $jwk) {
			if (isset($jwk['kid']) && $jwk['kid'] === $kid) {
				return $jwk;
			}
		}

		throw new Exception("No matching JWK found for kid: $kid");
	}

	/**
	 * JWK 转 PEM
	 */
	protected function jwkToPem(array $jwk): string {
		$modulus  = $this->urlsafeB64Decode($jwk['n']);
		$exponent = $this->urlsafeB64Decode($jwk['e']);

		$modulus  = "\x00" . $modulus; // 避免负数
		$modulus  = pack('Ca*a*', 0x02, $this->asn1Length(strlen($modulus)), $modulus);
		$exponent = pack('Ca*a*', 0x02, $this->asn1Length(strlen($exponent)), $exponent);

		$sequence  = $modulus . $exponent;
		$sequence  = pack('Ca*a*', 0x30, $this->asn1Length(strlen($sequence)), $sequence);
		$bitstring = "\x00" . $sequence;
		$bitstring = pack('Ca*a*', 0x03, $this->asn1Length(strlen($bitstring)), $bitstring);

		$rsa_oid  = pack('H*', '300D06092A864886F70D0101010500'); // rsaEncryption OID
		$sequence = $rsa_oid . $bitstring;
		$sequence = pack('Ca*a*', 0x30, $this->asn1Length(strlen($sequence)), $sequence);

		return "-----BEGIN PUBLIC KEY-----\r\n" .
		chunk_split(base64_encode($sequence), 64, "\r\n") .
			"-----END PUBLIC KEY-----\r\n";
	}

	/**
	 * OpenSSL 验证 JWT 签名
	 */
	protected function verifyWithOpenSSL(string $jwt, string $pem): object {
		$parts = explode('.', $jwt);
		if (count($parts) !== 3) {
			throw new Exception('Invalid JWT format');
		}

		[$headerB64, $payloadB64, $sigB64] = $parts;
		$data                              = $headerB64 . '.' . $payloadB64;
		$signature                         = $this->urlsafeB64Decode($sigB64);

		$header  = json_decode($this->urlsafeB64Decode($headerB64), true);
		$payload = json_decode($this->urlsafeB64Decode($payloadB64), false);

		if (!$header || !$payload) {
			throw new Exception('Invalid JWT header or payload');
		}

		$algo        = $header['alg'] ?? 'RS256';
		$opensslAlgo = match ($algo) {
			'RS256' => OPENSSL_ALGO_SHA256,
			'RS384' => OPENSSL_ALGO_SHA384,
			'RS512' => OPENSSL_ALGO_SHA512,
			default => throw new Exception("Unsupported algorithm: $algo"),
		};

		$ok = openssl_verify($data, $signature, $pem, $opensslAlgo);
		if ($ok !== 1) {
			throw new Exception('JWT signature invalid');
		}

		// 可选：检查过期时间
		if (isset($payload->exp) && $payload->exp < time()) {
			throw new Exception('JWT expired');
		}

		return $payload;
	}

	/**
	 * URL safe Base64 解码
	 */
	protected function urlsafeB64Decode(string $data): string {
		$remainder = strlen($data) % 4;
		if ($remainder) {
			$data .= str_repeat('=', 4 - $remainder);
		}
		return base64_decode(strtr($data, '-_', '+/'));
	}

	/**
	 * ASN.1 length 编码
	 */
	protected function asn1Length(int $length): string {
		if ($length < 0x80) {
			return chr($length);
		}
		$temp = ltrim(pack('N', $length), "\x00");
		return chr(0x80 | strlen($temp)) . $temp;
	}
}
