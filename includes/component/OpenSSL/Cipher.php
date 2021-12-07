<?php
namespace Wnd\Component\OpenSSL;

use Exception;

/**
 * OpenSSL 文本加密解密
 * @link https://www.php.net/manual/en/function.openssl-encrypt.php
 * @link https://www.php.net/manual/en/function.openssl-decrypt.php
 * @since 0.9.56.6
 */
class Cipher {

	private $key;
	private $cipher;
	private $iv;
	private $tag;

	/**
	 * @param string $cipher 算法 	默认 'aes-128-gcm'
	 * @param string $key    密匙 	should have been previously generated in a cryptographically safe way, like openssl_random_pseudo_bytes
	 */
	public function __construct(string $cipher = '', string $key = '') {
		$this->key    = $key;
		$this->cipher = strtolower($cipher ?: 'aes-128-gcm');

		if (!in_array($this->cipher, openssl_get_cipher_methods())) {
			throw new Exception('The current environment does not support  ' . $this->cipher);
		}

		$ivlen    = openssl_cipher_iv_length($this->cipher);
		$this->iv = openssl_random_pseudo_bytes($ivlen);
	}

	public function encrypt(string $plaintext): string{
		// $this->tag 用于接收并存储变量（PHP Doc：使用 AEAD 密码模式（GCM 或 CCM）时传引用的验证标签）
		$ciphertext = openssl_encrypt($plaintext, $this->cipher, $this->key, 0, $this->iv, $this->tag);
		return $ciphertext ?: '';
	}

	public function decrypt(string $ciphertext): string{
		$original_plaintext = openssl_decrypt($ciphertext, $this->cipher, $this->key, 0, $this->iv, $this->tag);
		return $original_plaintext ?: '';
	}
}
