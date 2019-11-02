<?php
/**
 *@since 2019.07.31
 *自动加载类文件
 *
 * new Wnd\Model\Wnd_Auth;
 * 对应文件路径
 * includes/model/class-wnd-auth.php
 *
 */
spl_autoload_register(function ($class) {

	// 命名空间前缀
	$prefix = 'wnd\\';

	// 命名空间对应的根目录
	$base_dir = WND_PATH . 'includes';

	// 统一大小写，并检测传入类是否为当前命名空间
	$len   = strlen($prefix);
	$class = strtolower($class);
	if (strncmp($prefix, $class, $len) !== 0) {
		return;
	}

	// 获取实际文件路径
	$path = substr($class, $len);
	$path = str_replace('_', '-', $path);
	$path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
	$path = str_replace('wnd-', 'class-wnd-', $path);

	$file = $base_dir . DIRECTORY_SEPARATOR . $path . '.php';
	if (file_exists($file)) {
		require $file;
	}
});

// 配置选项
if (is_admin()) {
	require WND_PATH . 'wnd-options.php'; //配置选项
}

// 初始化
Wnd\Model\Wnd_Init::init();

// function
require WND_PATH . 'includes/functions/inc-meta.php'; //数组形式储存 meta、option
require WND_PATH . 'includes/functions/inc-general.php'; //通用函数定义
require WND_PATH . 'includes/functions/inc-post.php'; //post相关自定义函数
require WND_PATH . 'includes/functions/inc-user.php'; //user相关自定义函数
require WND_PATH . 'includes/functions/inc-media.php'; //媒体文件处理函数
require WND_PATH . 'includes/functions/inc-finance.php'; //财务

require WND_PATH . 'includes/functions/tpl-general.php'; //通用模板
require WND_PATH . 'includes/functions/tpl-list.php'; //post list模板
require WND_PATH . 'includes/functions/tpl-term.php'; //term模板

// hook
require WND_PATH . 'includes/hook/add-action.php'; //添加动作
require WND_PATH . 'includes/hook/add-filter.php'; //添加钩子
require WND_PATH . 'includes/hook/remove.php'; //移除
