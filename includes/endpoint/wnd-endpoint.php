<?php
namespace Wnd\Endpoint;

use Wnd\Utility\Wnd_Request;

/**
 *@since 0.9.17
 *自定义路由处理类
 *
 *路径与对应类文件：
 * - /wnd-route/wnd_test  => Wnd\Endpoint\Wnd_Test
 * - /wnd-route/wndt_test => Wndt\Endpoint\Wndt_Test
 *
 *Endpoint 类相关响应应直接输出，而非返回值
 */
abstract class Wnd_Endpoint {

	/**
	 *Request Data Array
	 */
	protected $data = [];

	/**
	 *当前用户 Object
	 */
	protected $user;

	/**
	 *当前用户 ID Int
	 */
	protected $user_id;

	/**
	 *解析表单数据时，是否验证表单签名
	 */
	protected $verify_sign = false;

	/**
	 *解析表单数据时，是否进行人机验证（如果存在）
	 */
	protected $validate_captcha = false;

	/**
	 * Instance of Wnd_Request
	 */
	protected $request;

	/**
	 *构造
	 *
	 * - 校验请求数据
	 * - 核查权限许可
	 *
	 */
	public function __construct() {
		$this->request = new Wnd_Request($this->verify_sign, $this->validate_captcha);
		$this->data    = $this->request->get_request();
		$this->user    = wp_get_current_user();
		$this->user_id = $this->user->ID ?? 0;

		$this->check();
		$this->do();
	}

	/**
	 *权限检测
	 */
	protected function check() {
		return true;
	}

	/**
	 *执行操作
	 *
	 * - 响应数据应直接输出
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
	 *
	 */
	abstract protected function do();
}
