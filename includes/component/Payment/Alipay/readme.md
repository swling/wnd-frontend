### 证书生成教程
- https://opensupport.alipay.com/support/helpcenter/207/201602471154#

### 证书序列及支付宝公钥获取方法

```php

use Wnd\Component\Payment\Alipay\AlipayCertClient;

// 应用公钥证书序列号，获取方法：AlipayCertClient::getCertSNFromContent($certContent); 对应证书；appCertPublicKey_{xxx}.crt
$cert = '证书文本内容';
echo AlipayCertClient::getCertSNFromContent($cert) . '<br/>';

// 支付宝根证书序列号，获取方法：AlipayCertClient::getRootCertSNFromContent($certContent); 对应证书：alipayRootCert.crt
$cert = '证书文本内容';

echo AlipayCertClient::getRootCertSNFromContent($cert) . '<br/>';
// exit;

// 支付宝公钥，获取方法：AlipayCertClient::getPublicKeyFromContent($cert); 对应证书：alipayCertPublicKey_RSA2.crt
$cert = '证书文本内容';

$aliPayPublicKey = AlipayCertClient::getPublicKeyFromContent($cert);
echo ($aliPayPublicKey) . '<br>';

```