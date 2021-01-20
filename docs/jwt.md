# JWT

```php
/**
 *iss (issuer)：签发人
 *exp (expiration time)：过期时间
 *sub (subject)：主题
 *aud (audience)：受众
 *nbf (Not Before)：生效时间
 *iat (Issued At)：签发时间
 *jti (JWT ID)：编号
 */
$jwt = new Wnd\Utility\Wnd_JWT;

//自己使用测试begin
$payload = array('iss' => 'admin777', 'iat' => time(), 'exp' => time() + 7200, 'nbf' => time(), 'sub' => 'www.admin.com', 'jti' => md5(uniqid('JWT') . time()));
$token   = $jwt::getToken($payload);
// setcookie('token', $token);

//对token进行验证签名
// $token      = $_COOKIE['token'];
$getPayload = $jwt::verifyToken($token);
// echo "<br><br>";
var_dump($getPayload);
// echo "<br><br>";
//自己使用时候end
```