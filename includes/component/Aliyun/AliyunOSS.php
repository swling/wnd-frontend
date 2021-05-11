<?php
namespace Wnd\Component\Aliyun;

use Exception;

/**
 *@since 0.9.29
 *@link https://cloud.tencent.com/document/product/436/7751
 *阿里云对象存储
 */
class AliyunOSS {

	private $secret_id      = ''; //'云 API 密钥 SecretId';
	private $secret_key     = ''; //'云 API 密钥 SecretKey';
	private $endpoint       = ''; // COS 节点
	private $file_path_name = ''; // 文件在节点中的相对存储路径（签名需要）
	private $file_uri       = ''; // 文件的完整 URI： $this->endpoint . $this->file_path_name

	// 初始化
	public function __construct(string $secret_id, string $secret_key, string $endpoint) {
		$this->secret_id  = $secret_id;
		$this->secret_key = $secret_key;
		$this->endpoint   = $endpoint;
	}

	/**
	 *设置文件存储路径
	 *以 '/' 开头
	 */
	public function set_file_path_name(string $file_path_name) {
		$this->file_path_name = $file_path_name;
		$this->file_uri       = $this->endpoint . $this->file_path_name;
	}

	/**
	 * 上传文件
	 */
	function upload_file(string $source_file, int $timeout = 1800) {
		//设置头部
		$mime_type = mime_content_type($source_file);
		$md5       = base64_encode(md5_file($source_file, true));
		$headers   = [
			'Date:' . gmdate('D, d M Y H:i:s') . ' GMT',
			'Content-Type:' . $mime_type,
			'Content-MD5:' . $md5,
			'Authorization:OSS ' . $this->secret_id . ':' . $this->create_authorization('PUT', $mime_type, $md5),
		];

		$file = fopen($source_file, 'rb');
		if (!$file) {
			throw new Exception($source_file . 'invalid');
		}

		$ch = curl_init(); //初始化curl
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //返回字符串,而不直接输出
		curl_setopt($ch, CURLOPT_URL, $this->file_uri); //设置put到的url
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证对等证书
		// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); //不检查服务器SSL证书

		curl_setopt($ch, CURLOPT_PUT, true); //设置为PUT请求
		curl_setopt($ch, CURLOPT_INFILE, $file); //设置资源句柄
		curl_setopt($ch, CURLOPT_INFILESIZE, filesize($source_file));

		curl_setopt($ch, CURLOPT_HEADER, true); // 开启header信息以供调试
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);

		curl_exec($ch);
		$response = curl_getinfo($ch);

		fclose($file);
		curl_close($ch);

		if (200 != $response['http_code']) {
			throw new Exception(json_encode($response));
		}
		return $response;
	}

	/**
	 *Delete
	 *@link https://cloud.tencent.com/document/product/436/7743
	 **/
	public function delete_file(int $timeout = 30) {
		//设置头部
		$headers = [
			'Date:' . gmdate('D, d M Y H:i:s') . ' GMT',
			'Authorization:OSS ' . $this->secret_id . ':' . $this->create_authorization('DELETE'),
		];

		$ch = curl_init(); //初始化curl
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //返回字符串,而不直接输出
		curl_setopt($ch, CURLOPT_URL, $this->file_uri); //设置delete url
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证对等证书
		// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); //不检查服务器SSL证书

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

		// curl_setopt($ch, CURLOPT_HEADER, true); // 开启header信息以供调试
		// curl_setopt($ch, CURLINFO_HEADER_OUT, true);

		curl_exec($ch);
		$response = curl_getinfo($ch);
		curl_close($ch);

		if (204 != $response['http_code']) {
			throw new \Exception($response['http_code']);
		}

		return $response;
	}

	/**
	 * 获取签名
	 */
	protected function create_authorization(string $method, string $minType = '', $md5 = '') {
		$dateTime = gmdate('D, d M Y H:i:s') . ' GMT';
		$bucket   = $this->parse_bucket();

		//生成签名：换行符必须使用双引号
		$str       = $method . "\n" . $md5 . "\n" . $minType . "\n" . $dateTime . "\n/" . $bucket . $this->file_path_name;
		$signature = base64_encode(hash_hmac('sha1', $str, $this->secret_key, true));
		return $signature;
	}

	/**
	 *根据 endpoint 域名解析出 bucket
	 */
	protected function parse_bucket(): string{
		$parsedUrl = parse_url($this->endpoint);
		$host      = explode('.', $parsedUrl['host']);
		$subdomain = $host[0];

		return $subdomain;
	}
}
