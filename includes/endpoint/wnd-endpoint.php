<?php
namespace Wnd\Endpoint;

use Exception;
use Wnd\Controller\Wnd_Defender_Action;
use Wnd\Controller\Wnd_Request;
use WP_REST_Request;

/**
 * 非标准数据路由端点处理，抽象基类
 * - Wnd\Endpoint 主要用于处理与外部第三方平台的交互响应如：支付回调通知、微信公众号通讯等，或系统内其他非 Json 数据交互
 * - 响应数据格式将在具体 Endpoint 类中定义
 * - Endpoint 类相关响应数据应直接输出，而非返回值
 * 路径与对应类文件：
 * - wp-json/wnd/route/wnd_test  => Wnd\Endpoint\Wnd_Test
 * - wp-json/wnd/route/wndt_test => Wndt\Endpoint\Wndt_Test
 * @since 0.9.17
 */
abstract class Wnd_Endpoint {

	/**
	 * Instance of Wnd_Request
	 */
	protected $request;

	/**
	 * Request Data Array
	 */
	protected $data = [];

	/**
	 * 响应类型
	 */
	protected $content_type;

	/**
	 * 时间范围
	 * 与 $this->max_actions 结合，用于控制操作执行频次
	 */
	public $period;

	/**
	 * 最多执行
	 * 与 $this->period 结合，用于控制操作执行频次
	 */
	public $max_actions;

	/**
	 * 构造
	 *
	 * - 校验请求数据
	 * - 核查权限许可
	 *
	 */
	final public function __construct(WP_REST_Request $wp_rest_request) {
		/**
		 * 重写 Rest API 输出
		 */
		add_filter('rest_pre_serve_request', '__return_true', 10);

		$this->request = new Wnd_Request($wp_rest_request, false, false);
		$this->data    = $this->request->get_request();

		try {
			// 防护
			$defender = new Wnd_Defender_Action($this);
			$defender->defend_action();
			$defender->write_log();

			$this->check();
			$this->set_content_type();
			$this->do();
		} catch (Exception $e) {
			echo json_encode(['status' => 0, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
		}

		exit;
	}

	/**
	 * 权限检测
	 */
	protected function check() {
		return true;
	}

	/**
	 *  不同格式的响应，应设置对应的 Content-type 如纯文本：header('Content-Type:text/plain; charset=UTF-8');
	 *
	 * 	常见的媒体格式类型如下：
	 * 	 - text/html ： HTML格式
	 * 	 - text/plain ：纯文本格式
	 * 	 - text/xml ： XML格式
	 * 	 - image/gif ：gif图片格式
	 * 	 - image/jpeg ：jpg图片格式
	 * 	 - image/png：png图片格式
	 *
	 * 	以application开头的媒体格式类型：
	 * 	- application/xhtml+xml ：XHTML格式
	 * 	- application/xml： XML数据格式
	 * 	- application/atom+xml ：Atom XML聚合格式
	 * 	- application/json： JSON数据格式
	 * 	- application/pdf：pdf格式
	 * 	- application/msword ： Word文档格式
	 * 	- application/octet-stream ： 二进制流数据（如常见的文件下载）
	 * 	- application/x-www-form-urlencoded ： <form encType=””>中默认的encType，form表单数据被编码为key/value格式发送到服务器（表单默认的提交数据的格式）
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

			case 'script':
				$content_type = 'application/javascript';
				break;

			default:
				$content_type = 'text/plain';
				break;
		}

		header('Content-Type: ' . $content_type . '; charset=' . get_option('blog_charset'));
	}

	/**
	 * 执行操作
	 * - 文本响应数据应直接输出；图像、文件等则应返回对应对象
	 */
	abstract protected function do();

	/**
	 * 获取当前操作类名称
	 * @since 0.9.50
	 */
	public static function get_class_name(): string {
		$class_name = get_called_class();
		return strtolower($class_name);
	}
}
