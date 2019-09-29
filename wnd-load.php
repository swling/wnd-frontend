<?php
/**
 *@since 2019.07.31
 *自动加载类文件
 *
 *实例
 *类名: Wnd_Form
 *路径: /includes/class/class-wnd-form.php
 */
function wnd_class_loader($class) {
	$file_name = 'class-' . str_replace('_', '-', strtolower($class));
	$file      = __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . $file_name . '.php';
	if (file_exists($file)) {
		require $file;
	}
}
spl_autoload_register('wnd_class_loader');

// basic
require WND_PATH . 'wnd-options.php'; //配置选项
require WND_PATH . 'wnd-api.php'; // API

// core
require WND_PATH . 'includes/functions/inc-general.php'; //通用函数定义
require WND_PATH . 'includes/functions/inc-post.php'; //post相关自定义函数
require WND_PATH . 'includes/functions/inc-user.php'; //user相关自定义函数
require WND_PATH . 'includes/functions/inc-media.php'; //媒体文件处理函数
require WND_PATH . 'includes/functions/inc-meta.php'; //数组形式储存 meta、option

require WND_PATH . 'includes/functions/inc-database.php'; //数据库
require WND_PATH . 'includes/functions/inc-admin.php'; //管理函数
require WND_PATH . 'includes/functions/inc-finance.php'; //财务
require WND_PATH . 'includes/functions/inc-post-type-status.php'; //自定义文章类型及状态

// ajax
require WND_PATH . 'includes/ajax/ajax-post.php'; //ajax 文章发布编辑
require WND_PATH . 'includes/ajax/ajax-media.php'; //ajax 媒体处理
require WND_PATH . 'includes/ajax/ajax-user.php'; //ajax 用户
require WND_PATH . 'includes/ajax/ajax-actions.php'; //其他ajax操作
require WND_PATH . 'includes/ajax/ajax-pay.php'; //ajax付费服务

// hook
require WND_PATH . 'includes/hook/add-action.php'; //添加动作
require WND_PATH . 'includes/hook/add-filter.php'; //添加钩子
require WND_PATH . 'includes/hook/remove.php'; //移除

// template
require WND_PATH . 'templates/tpl-general.php'; //通用模板
require WND_PATH . 'templates/tpl-user.php'; //user模板
require WND_PATH . 'templates/tpl-post.php'; //post模板
require WND_PATH . 'templates/tpl-list.php'; //post list模板
require WND_PATH . 'templates/tpl-term.php'; //term模板
require WND_PATH . 'templates/tpl-finance.php'; //财务模板
require WND_PATH . 'templates/tpl-panel.php'; //前端管理面板
require WND_PATH . 'templates/tpl-gallery.php'; //橱窗相册

/**
 *分类关联标签
 */
if (wnd_get_option('wnd', 'wnd_enable_terms') == 1) {
	new Wnd_Tag_Under_Category();
}
