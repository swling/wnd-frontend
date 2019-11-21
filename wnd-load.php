<?php
/**
 *@since 2019.07.31
 *自动加载类文件
 *
 * 本插件：
 * new Wnd\Model\Wnd_Auth;
 * 对应文件路径
 * includes/model/class-wnd-auth.php
 *
 * 第三方组件：
 * new Wnd\Component\Aliyun\Sms\SignatureHelper;
 * includes/component/Aliyun/Sms/SignatureHelper.php
 * (注意：第三方组件文件及文件目录需要区分大小写)
 */
spl_autoload_register(function ($class) {
	// 命名空间前缀及对应目录
	$base_prefix      = 'Wnd\\';
	$component_prefix = 'Wnd\\Component';
	$base_dir         = WND_PATH . 'includes';
	$component_dir    = WND_PATH . 'includes' . DIRECTORY_SEPARATOR . 'component';

	/**
	 *本插件集成的第三方组件，按通用驼峰命名规则
	 *请注意文件及文件夹大小写必须一一对应
	 */
	if (0 === stripos($class, $component_prefix)) {
		$path = substr($class, strlen($component_prefix));
		$path = str_replace('\\', DIRECTORY_SEPARATOR, $path);

		$file = $component_dir . DIRECTORY_SEPARATOR . $path . '.php';
		if (file_exists($file)) {
			require $file;
		}

		return;
	}

	/**
	 *本插件类规则
	 */
	if (0 === stripos($class, $base_prefix)) {
		$class = strtolower($class);
		$path  = substr($class, strlen($base_prefix));
		$path  = str_replace('_', '-', $path);
		$path  = str_replace('\\', DIRECTORY_SEPARATOR, $path);
		$path  = str_replace('wnd-', 'class-wnd-', $path);

		$file = $base_dir . DIRECTORY_SEPARATOR . $path . '.php';
		if (file_exists($file)) {
			require $file;
		}
	}
});

// 配置选项
if (is_admin()) {
	require WND_PATH . 'wnd-options.php'; //配置选项
}

// 初始化
Wnd\Model\Wnd_Init::init();

// function
require WND_PATH . 'includes/function/inc-meta.php'; //数组形式储存 meta、option
require WND_PATH . 'includes/function/inc-general.php'; //通用函数定义
require WND_PATH . 'includes/function/inc-post.php'; //post相关自定义函数
require WND_PATH . 'includes/function/inc-user.php'; //user相关自定义函数
require WND_PATH . 'includes/function/inc-media.php'; //媒体文件处理函数
require WND_PATH . 'includes/function/inc-finance.php'; //财务

require WND_PATH . 'includes/function/tpl-general.php'; //通用模板
require WND_PATH . 'includes/function/tpl-list.php'; //post list模板
require WND_PATH . 'includes/function/tpl-term.php'; //term模板

// hook
require WND_PATH . 'includes/hook/add-action.php'; //添加动作
require WND_PATH . 'includes/hook/add-filter.php'; //添加钩子
require WND_PATH . 'includes/hook/remove.php'; //移除
