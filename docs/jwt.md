# JWT

```php
/**
 * 约定 Payload 数据格式如下：
 *  - iss (issuer)：签发人
 *  - exp (expiration time)：过期时间
 *  - sub (subject)：主题
 *  - aud (audience)：受众
 *  - nbf (Not Before)：生效时间
 *  - iat (Issued At)：签发时间
 *  - jti (JWT ID)：编号
 *
 * Payload 数据格式建议遵循约定，同时可根据实际应用场景自行添加或移除一项或多项
 */
$jwt = new Wnd\Utility\Wnd_JWT;

// 根据 payload 生成 Token
$payload = [
	'iss' => 'admin777', 
	'iat' => time(), 
	'exp' => time() + 7200, 
	'nbf' => time(), 
	'sub' => 'www.admin.com'
];
$token   = $jwt::getToken($payload);

// 根据 Token 还原 payload
$getPayload = $jwt::verifyToken($token);
var_dump($getPayload);
```