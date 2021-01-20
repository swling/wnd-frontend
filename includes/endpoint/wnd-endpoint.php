<?php
namespace Wnd\Endpoint;

/**
 *@since 0.9.17
 *自定义路由端点处理抽象基类
 *
 *路径与对应类文件：
 * - wp-json/wnd/route/wnd_test  => Wnd\Endpoint\Wnd_Test
 * - wp-json/wnd/route/wndt_test => Wndt\Endpoint\Wndt_Test
 *
 *Endpoint 类相关响应应直接输出，而非返回值
 */
abstract class Wnd_Endpoint {

	/**
	 *Request Data Array
	 */
	protected $data = [];

	/**
	 *响应类型
	 */
	protected $content_type;

	/**
	 *构造
	 *
	 * - 校验请求数据
	 * - 核查权限许可
	 *
	 */
	public function __construct() {
		$this->data = ('POST' == $_SERVER['REQUEST_METHOD']) ? $_POST : $_GET;

		$this->check();
		$this->set_content_type();
		$this->do();
	}

	/**
	 *权限检测
	 */
	protected function check() {
		return true;
	}

	/**
	 * - 不同格式的响应，应设置对应的 Content-type 如纯文本：header('Content-Type:text/plain; charset=UTF-8');
	 *
	 *	常见的媒体格式类型如下：
	 *
	 *	 - text/html ： HTML格式
	 *	 - text/plain ：纯文本格式
	 *	 - text/xml ： XML格式
	 *	 - image/gif ：gif图片格式
	 *	 - image/jpeg ：jpg图片格式
	 *	 - image/png：png图片格式
	 *
	 *	以application开头的媒体格式类型：
	 *
	 *	- application/xhtml+xml ：XHTML格式
	 *	- application/xml： XML数据格式
	 *	- application/atom+xml ：Atom XML聚合格式
	 *	- application/json： JSON数据格式
	 *	- application/pdf：pdf格式
	 *	- application/msword ： Word文档格式
	 *	- application/octet-stream ： 二进制流数据（如常见的文件下载）
	 *	- application/x-www-form-urlencoded ： <form encType=””>中默认的encType，form表单数据被编码为key/value格式发送到服务器（表单默认的提交数据的格式）
	 */
	protected function set_content_type() {
		switch ($this->content_type) {
		case 'html':
			$content_type = 'text/html';
			break;

		case 'xml':
			$content_type = 'application/xml';
			break;

		case 'json':
			$content_type = 'application/json';
			break;

		default:
			$content_type = 'text/plain';
			break;
		}

		header('Content-Type: ' . $content_type . '; charset=' . get_option('blog_charset'));
	}

	/**
	 *执行操作
	 *
	 * - 文本响应数据应直接输出；图像、文件等则应返回对应对象
	 *
	 */
	abstract protected function do();
}
