<?php
namespace Wnd\Utility;

/**
 * PHP JWT
 * @since 0.8.61
 *
 */
class Wnd_JWT {

	//头部
	private static $header = array(
		'alg' => 'HS256', //生成signature的算法
		'typ' => 'JWT', //类型
	);

	//使用HMAC生成信息摘要时所使用的密钥
	private static $key = '123456';

	/**
	 * 获取jwt token
	 * @param array $payload jwt载荷   格式如下非必须
	 *
	 *iss (issuer)：签发人
	 *exp (expiration time)：过期时间
	 *sub (subject)：主题
	 *aud (audience)：受众
	 *nbf (Not Before)：生效时间
	 *iat (Issued At)：签发时间
	 *jti (JWT ID)：编号
	 *
	 * @return bool|string
	 */
	public static function getToken(array $payload): string {
		if (!is_array($payload)) {
			return '';
		}

		$base64header  = self::base64UrlEncode(json_encode(self::$header, JSON_UNESCAPED_UNICODE));
		$base64payload = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE));
		$token         = $base64header . '.' . $base64payload . '.' . self::signature($base64header . '.' . $base64payload, self::$key, self::$header['alg']);
		return $token;
	}

	/**
	 * 验证token是否有效,默认验证exp,nbf,iat时间
	 * @param string $Token 需要验证的token
	 * @return bool|string
	 */
	public static function verifyToken(string $Token) {
		$tokens = explode('.', $Token);
		if (count($tokens) != 3) {
			return false;
		}

		list($base64header, $base64payload, $sign) = $tokens;

		//获取jwt算法
		$base64decodeheader = json_decode(self::base64UrlDecode($base64header), true);
		if (empty($base64decodeheader['alg'])) {
			return false;
		}

		//签名验证
		if (self::signature($base64header . '.' . $base64payload, self::$key, $base64decodeheader['alg']) !== $sign) {
			return false;
		}

		$payload = json_decode(self::base64UrlDecode($base64payload), true);

		//签发时间大于当前服务器时间验证失败
		if (isset($payload['iat']) && $payload['iat'] > time()) {
			return false;
		}

		//过期时间小宇当前服务器时间验证失败
		if (isset($payload['exp']) && $payload['exp'] < time()) {
			return false;
		}

		//该nbf时间之前不接收处理该Token
		if (isset($payload['nbf']) && $payload['nbf'] > time()) {
			return false;
		}

		return $payload;
	}

	/**
	 * base64UrlEncode   https://jwt.io/  中base64UrlEncode编码实现
	 * @param string $input 需要编码的字符串
	 * @return string
	 */
	private static function base64UrlEncode(string $input): string {
		return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
	}

	/**
	 * base64UrlEncode  https://jwt.io/  中base64UrlEncode解码实现
	 * @param string $input 需要解码的字符串
	 * @return bool|string
	 */
	private static function base64UrlDecode(string $input): string{
		$remainder = strlen($input) % 4;
		if ($remainder) {
			$addlen = 4 - $remainder;
			$input .= str_repeat('=', $addlen);
		}
		return base64_decode(strtr($input, '-_', '+/')) ?: '';
	}

	/**
	 * HMACSHA256签名   https://jwt.io/  中HMACSHA256签名实现
	 * @param string $input 为base64UrlEncode(header).".".base64UrlEncode(payload)
	 * @param string $key
	 * @param string $alg   算法方式
	 * @return mixed
	 */
	private static function signature(string $input, string $key, string $alg = 'HS256'): string{
		$alg_config = array(
			'HS256' => 'sha256',
		);
		return self::base64UrlEncode(hash_hmac($alg_config[$alg], $input, $key, true));
	}
}
