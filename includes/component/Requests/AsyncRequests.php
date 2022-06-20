<?php
namespace Wnd\Component\Requests;

use Exception;

/**
 * 使用fsocketopen()方式发送异步请求
 * 基本原理：完成请求发送后即关闭连接，不再等待请求地址的响应结果，从而达到【异步请求】的效果
 *
 * @link https://www.jianshu.com/p/b0e8a7a1f71b
 * @link https://learnku.com/articles/53333
 *
 * @link https://www.laruence.com/2008/04/14/318.html
 * @link https://www.laruence.com/2008/04/16/98.html
 *
 * fsockopen 文件上传参考
 * @link https://blog.csdn.net/fdipzone/article/details/11712607
 *
 * 注意：
 * - 如果你要对建立在套接字基础上的读写操作设置操作时间设置连接时限，请使用stream_set_timeout()，fsockopen()的连接时限（timeout）的参数仅仅在套接字连接的时候生效。
 * - PHP在发送信息给浏览器时，才会检测连接是否已经中断为确保请求执行
 * - 尽管如此，为防止用户关闭客户端引起中断，建议在被请求的 php 脚本页添加：
 *   ignore_user_abort(true);
 *   set_time_limit(0);
 */
class AsyncRequests {

	private $url;
	private $args;
	private $fp;
	private $json_request;

	private $host;
	private $path;
	private $port;
	private $method;
	private $headers;
	private $data;

	/**
	 * - $args['body'] 为数组数据时，采用 Form 提交
	 * - $args['body'] 为字符串时，采用 JSON 提交
	 * - GET 请求请直接设定对应 URK 无需设置 $args['body']
	 */
	public function __construct(string $url, array $args = []) {
		$defaults = [
			'body'           => [],
			'fsock_timeout'  => 5,
			'stream_timeout' => 60,
			'method'         => 'POST',
			'authorization'  => '',
			'cookie'         => '',
		];
		$this->args = array_merge($defaults, $args);
		$this->url  = $url;

		$this->parseRequest();
		$this->initSock();
		$this->request();
	}

	private function parseRequest() {
		// FormDate or JSON
		if (is_array($this->args['body'])) {
			$this->data         = http_build_query($this->args['body']);
			$this->json_request = false;
		} else {
			$this->data         = $this->args['body'];
			$this->json_request = true;
		}

		$this->method = $this->args['method'];

		$this->parseUrl();
		$this->buildHeaders();
	}

	private function parseUrl() {
		$urlParmas  = parse_url($this->url);
		$this->host = $urlParmas['host'];
		$this->port = 80;

		if (isset($urlParmas['query'])) {
			$this->path = $urlParmas['path'] . '?' . $urlParmas['query'];
		} else {
			$this->path = $urlParmas['path'];
		}

		if ($urlParmas['scheme'] == 'https') {
			$this->host = 'ssl://' . $this->host;
			$this->port = 443;
		}
	}

	/**
	 * 传递参数为url=?p1=1&p2=2的方式,使用application/x-www-form-urlencoded方式;
	 * 传递参数为json字符串的方式,并且在请求体的body中,使用application/json
	 */
	private function buildHeaders() {
		$this->headers = $this->method . ' ' . $this->path . " HTTP/1.1\r\n";
		$this->headers .= 'Host: ' . $this->host . "\r\n";
		$this->headers .= 'Content-Length: ' . strlen($this->data) . "\r\n"; // 不可省略
		$this->headers .= $this->json_request ? "Content-Type: application/json\r\n" : "Content-Type: application/x-www-form-urlencoded\r\n";

		// Authorization
		if ($this->args['authorization']) {
			$this->headers .= 'Authorization: ' . $this->args['Authorization'] . "\r\n";
		}

		// 传递 cookie
		if (!empty($this->args['cookie'])) {
			$_cookie = strval(NULL);
			foreach ($this->args['cookie'] as $k => $v) {
				$_cookie .= $k . '=' . $v . '; ';
			}
			$cookie_str = 'Cookie: ' . $_cookie . " \r\n"; //传递Cookie
			$this->headers .= $cookie_str;
		}

		// 必须放置在最后一位
		$this->headers .= "Connection: close\r\n\r\n";
	}

	private function initSock() {
		/**
		 * PHP 5.6 & 7.0 Only Configuration 不验证 ssl
		 * @link https://www.php.net/manual/zh/migration56.openssl.php
		 *
		 * stream_socket_client 相较于 fsockopen 提供了更多选项
		 */
		$context = stream_context_create(
			[
				'ssl' => [
					//'ciphers' => 'RC4-MD5',
					'verify_host'      => FALSE,
					'verify_peer_name' => FALSE,
					'verify_peer'      => FALSE,
				],
			]
		);
		$this->fp = stream_socket_client($this->host . ':' . $this->port, $error_code, $error_msg, $this->args['fsock_timeout'], STREAM_CLIENT_CONNECT, $context);
		if (!$this->fp) {
			throw new Exception('fsockopen error. error_code: ' . $error_code . 'msg: ' . $error_msg);
		}

		stream_set_blocking($this->fp, false); //开启了手册上说的非阻塞模式
		stream_set_timeout($this->fp, $this->args['stream_timeout']); // 数据处理超时
	}

	private function request() {
		fwrite($this->fp, $this->headers . $this->data);
		usleep(1000); // 如果没有这延时，可能在nginx服务器上就无法执行成功
		fclose($this->fp);
	}
}
