<?php
namespace Wnd\Application;

use Exception;
use ReflectionClass;
use Wnd\Model\Wnd_SKU;

/**
 * 主题 Web Application 接口
 * @since 0.1.0
 *
 * 抽象基类设计为单例模式的注意事项
 *  - 需要注意在子类中单独定义 protected static $instance;
 *    以避免子类继承基类的 $instance 静态属性，导致所有子类共用基类静态属性，造成所有实例为第一个实例的问题
 *
 * 参考文档：
 * @link https://www.cnblogs.com/Nietzsche--Nc/p/6635419.html
 */
abstract class Wnd_App_abstract {

	/**
	 * 如需发起付费请求的应用，必须将次属性配置为对应付费请求的 Action 名称（不含命名空间）
	 * @see Wnd\Action\Wnd_Action_App::check();
	 */
	public $action = '';

	// ################### 单例模式代码开始
	private static $instance = null;

	final protected function __construct() {
		$this->init();
	}

	protected function init() {}

	public static function get_instance() {
		if (static::$instance === null) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	final protected function __clone() {}
	// ################### 单例模式代码结束

	/**
	 * 整合application 接口信息
	 * - 构建本地 web 应用
	 * - 对接外部小程序
	 */
	public function get_app_info(): array {
		$current_app = (new ReflectionClass(get_called_class()))->getShortName();
		$temp_slug   = str_replace('wndt_', '', strtolower($current_app));
		$slug        = str_replace('_', '-', $temp_slug);
		$app_post    = wnd_get_post_by_slug($slug, 'app');
		$price       = wnd_get_post_price($app_post->ID);
		return [
			'app_id' => $app_post->ID ?? 0,
			'price'  => $price,
			'sku'    => Wnd_SKU::get_object_sku($app_post->ID ?? 0),
		];
	}

	public static function is_noindex(): bool {
		return false;
	}

	public static function is_canonical(): bool {
		return true;
	}

	// 标题
	public static function get_title(): string {
		return '';
	}

	// 描述
	public static function get_description(): string {
		return '';
	}

	// 交互界面
	abstract public function render();

	// CSS 样式
	public static function generate_head() {}

	/**
	 * 根据当前 application post 获取对应应用名称
	 */
	public static function get_app_name($post = false): string {
		$post = get_post($post);
		if (!$post or 'app' != $post->post_type) {
			return '';
		}

		$category_obj = wp_get_object_terms($post->ID, 'app_cat')[0] ?? false;
		if (!$category_obj) {
			return '';
		}

		$category = ucfirst($category_obj->slug);
		$app_name = 'Wndt_' . str_replace('-', '_', $post->post_name);
		$app_name = 'Wndt\Application\\' . $category . '\\' . $app_name;

		if (!class_exists($app_name)) {
			return '';
		}

		return $app_name;
	}

	/**
	 * 渲染 application
	 */
	public static function render_app() {
		$app = static::get_app_name();
		if (!$app) {
			return false;
		}

		try {
			$app_instance = $app::get_instance();
			$app_instance->render();
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * 渲染 application style
	 */
	public static function print_app_head() {
		$app = static::get_app_name();
		if (!$app) {
			return false;
		}

		$app::generate_head();
	}

	/**
	 * 渲染 application Head meta
	 */
	public static function print_app_meta() {
		$app = static::get_app_name();
		if (!$app) {
			return false;
		}

		echo '<meta name="description" content="' . $app::get_description() . '">' . PHP_EOL;

		// noindex
		$noindex = $app::is_noindex();
		if ($noindex) {
			echo '<meta name="robots" content="noindex">' . PHP_EOL;
		}
	}

	/**
	 * 渲染 application Head meta
	 */
	public static function print_app_data() {
		$app = static::get_app_name();
		if (!$app) {
			return false;
		}

		$app_instance = $app::get_instance();

		echo '<script>var app = {}; var app_info = ' . json_encode($app_instance->get_app_info(), JSON_UNESCAPED_UNICODE) . ';</script>';

	}

	/**
	 * 渲染 application
	 */
	public static function get_app_title(string $title): string {
		$app = static::get_app_name();
		if (!$app) {
			return '';
		}

		return $app::get_title($title);
	}

	/**
	 * 渲染 canonical link
	 */
	public static function is_canonical_post(): bool {
		$app = static::get_app_name();
		if (!$app) {
			return true;
		}

		return $app::is_canonical();
	}

}
