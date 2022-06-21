<?php
namespace Wnd\Component\Payment\Alipay;

/**
 * 支付宝证书类
 * @link https://github.com/alipay/alipay-sdk-php-all/blob/57ec51c7d098df388f2dc90230ff23a6c5d93751/aop/AopCertClient.php
 *
 * @since 0.9.58.3
 */
class AlipayCertClient {

	/**
	 * 从证书内容中提取序列号
	 * @param $certContent
	 * @return string
	 */
	public static function getCertSNFromContent(string $certContent): string{
		$ssl = openssl_x509_parse($certContent);
		$SN  = md5(static::array2string(array_reverse($ssl['issuer'])) . $ssl['serialNumber']);
		return $SN;
	}

	/**
	 * 从公钥证书内容中提取公钥
	 * @param $certContent
	 * @return mixed
	 */
	public static function getPublicKeyFromContent(string $certContent): string{
		$pkey       = openssl_pkey_get_public($certContent);
		$keyData    = openssl_pkey_get_details($pkey);
		$public_key = str_replace('-----BEGIN PUBLIC KEY-----', '', $keyData['key']);
		$public_key = trim(str_replace('-----END PUBLIC KEY-----', '', $public_key));
		return $public_key;
	}

	/**
	 * 提取根证书序列号
	 * @param $certContent  根证书
	 * @return string|null
	 */
	public static function getRootCertSNFromContent(string $certContent) {
		$array = explode('-----END CERTIFICATE-----', $certContent);
		$SN    = null;
		for ($i = 0; $i < count($array) - 1; $i++) {
			$ssl[$i] = openssl_x509_parse($array[$i] . '-----END CERTIFICATE-----');
			if (strpos($ssl[$i]['serialNumber'], '0x') === 0) {
				$ssl[$i]['serialNumber'] = static::hex2dec($ssl[$i]['serialNumberHex']);
			}
			if ($ssl[$i]['signatureTypeLN'] == 'sha1WithRSAEncryption' || $ssl[$i]['signatureTypeLN'] == 'sha256WithRSAEncryption') {
				if ($SN == null) {
					$SN = md5(static::array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
				} else {

					$SN = $SN . '_' . md5(static::array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
				}
			}
		}
		return $SN;
	}

	/**
	 * 0x转高精度数字
	 * @param  $hex
	 * @return int|string
	 */
	private static function hex2dec($hex) {
		$dec = 0;
		$len = strlen($hex);
		for ($i = 1; $i <= $len; $i++) {
			$dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
		}
		return $dec;
	}

	private static function array2string(array $array) {
		$string = [];
		foreach ($array as $key => $value) {
			$string[] = $key . '=' . $value;
		}
		return implode(',', $string);
	}
}
