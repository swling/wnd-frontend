<?php
namespace Wnd\Module;

use Exception;

/**
 * UI模块基类
 *
 * 模块支持两种类型：
 * - 实例化构建通过 get_structure() 方法输出数组数据 交付 API 转 Json 供前端渲染
 * - HTML 模块可通过 static::render() 直接静态访问
 * 注意：
 * - HTML 模块必须实现方法 static::build():string， 抽象方法 $this->structure():array 直接返回空数组
 * - 非 HTML 模块必须实现方法 $this->structure():array，抽象方法 static::build():string 可输出 HTML 字符串，或返回空值
 * - 未达到上述要求的模块定义为无效模块
 *
 * @since 2019.10.02
 */
abstract class Wnd_Module {

	/**
	 * 其他一些 Module 公共特性
	 */
	use Wnd_Module_Trait;

	/**
	 * 定义模块类型，供前端适配不同方法渲染
	 * 正是因为该属性的多样性不得设置为静态属性（可能引发难以排除的 bug）
	 * @since 0.9.25
	 */
	protected $type = '';

	protected $args = [];

	protected $structure = [];

	/**
	 * 实例化构建用于 WP API
	 *
	 * @param $args  	传参数组，对象，或http请求字符
	 * @param $force 是否强制传参，忽略                    GET 请求参数
	 */
	public function __construct(array $args = [], bool $force = false) {
		/**
		 * 子类必须定义模块类型，供前端适配不同方法渲染
		 * @since 0.9.25
		 */
		if (!$this->type) {
			throw new Exception(__('未定义 Module 类型', 'wnd'));
		}

		/**
		 * Init
		 */
		$this->args = static::init_module($args, $force);
	}

	/**
	 * 初始化
	 * - 参数解析
	 * - 权限检查
	 * @since 0.9.25
	 */
	private static function init_module(array $args, bool $force): array {
		/**
		 * 默认 $_GET 参数优先，若设置 $force = true 则忽略 $_GET
		 */
		$args = $force ? wp_parse_args($args) : wp_parse_args($_GET, $args);

		// 权限检测
		static::check($args);

		return $args;
	}

	/**
	 * 权限核查请复写本方法
	 */
	protected static function check($args) {
		return;
	}

	/**
	 * 输出 Module 数据结构，经由 Controller 转为Json 后供前端渲染
	 * @since 0.9.25
	 */
	public function get_structure(): array {
		return [
			'type'        => $this->type,
			'structure'   => ('html' == $this->type) ? static::build($this->args) : $this->structure($this->args),
			'request_url' => wnd_get_current_url(),
		];
	}

	/**
	 * 非 HTML 模块构建 Array
	 */
	abstract protected function structure(): array;

	/**
	 * HTML Module 静态渲染
	 *
	 * @param  $args  	传参数组，对象，或http请求字符
	 * @param  $force 是否强制传参，忽略                    GET 请求参数
	 * @return string HTML 字符串
	 */
	public static function render(array $args = [], bool $force = false): string {
		/**
		 * 默认 $_GET 参数优先，若设置 $force = true 则忽略 $_GET
		 */
		$args = static::init_module($args, $force);

		// 生成 Html
		return static::build($args);
	}

	/**
	 * HTML 模块构建常规字符串数据
	 * - 此处不添加 $args 参数，因为如果父类添加，则所有子类必须添加会导致大量无需传参的 Module 必须设置无效传参
	 * - 接受传参子类可自行添加带默认值的传参 $args = [] 即可
	 * - 如果设置传参，则必须设置参数的默认值，否则无法匹配本类方法一致性
	 *
	 * @return string HTML 字符串
	 */
	abstract protected static function build(): string;

}
