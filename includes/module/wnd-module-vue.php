<?php

namespace Wnd\Module;

use Exception;
use ReflectionClass;
use Wnd\Module\Wnd_Module;

/**
 * Vue 模块抽象基类
 * - 统一定义 php class 与 vue 文件的目录对应关系
 * - 统一定义 JavaScript 数据变量名 module_data
 * @since 0.9.87
 */
abstract class Wnd_Module_Vue extends Wnd_Module {

	protected $type = 'html';

	// Html 模块无需结构输出
	protected function structure(): array {
		return [];
	}

	// 读取 Vue 文件
	final protected static function build(array $args = []): string {
		$data         = static::parse_data($args);
		$data['lang'] = get_locale();
		$html         = '<script>var module_data = ' . json_encode($data) . '</script>';

		$file_path = static::get_file_path();

		// 主题目录下的文件优先级高于插件目录下的文件
		$file = get_template_directory() . $file_path;
		if (file_exists($file)) {
			$html .= file_get_contents($file);
		} elseif (file_exists(WND_PATH . $file_path)) {
			$html .= file_get_contents(WND_PATH . $file_path);
		} else {
			throw new Exception('vue file not exists:' . $file_path);
		}

		return $html;

		/**
		 * 采用 vue 文件编写代码，并通过 php 读取文件文本作为字符串使用
		 * 主要目的是便于编辑，避免在 php 文件中混入大量 HTML 源码，难以维护
		 * 虽然的确基于 vue 构建，然而在这里，它并不是标准的 vue 文件，而是 HTML 文件
		 * 之所以使用 .vue 后缀是因为 .HTML 文件在文件夹中将以浏览器图标展示，非常丑陋，毫无科技感
		 * 仅此而已
		 */
	}

	/**
	 * class 与文件目录对应关系：
	 * Wnd\Module\${common\Wnd_SKU_Form} => /includes/module-vue/${common/sku-form}.vue
	 *
	 * 对比 PHP 类文件：在插件或主题 module 同层级的 module-vue 目录中，同路径下移除 wnd_ 前缀，以 vue 结尾
	 * 例如：Wnd\Module\Common\Wnd_SKU_Form => /includes/module-vue/common/sku-form.vue
	 *
	 * 该方法用于自动获取当前类对应的 vue 文件路径
	 */
	protected static function get_file_path(): string {
		$class_name = (new ReflectionClass(get_called_class()))->name;
		$class_name = strtolower($class_name);
		$class_name = str_replace('wnd_', '', $class_name);
		$class_name = str_replace('_', '-', $class_name);
		$path       = explode('\\', $class_name, 3)[2];
		$path       = str_replace('\\', '/', $path);
		return '/includes/module-vue/' . $path . '.vue';
	}

	// 该方法用于解析数据，返回给前端使用，返回值将会被转化为 JSON 格式，以在 Vue 文件中接收数据
	protected static function parse_data(array $args): array {
		return $args;
	}

}
