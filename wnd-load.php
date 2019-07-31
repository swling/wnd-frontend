<?php

/**
 *加载php文件
 */

// basic
require WND_PATH . 'wnd-options.php'; //配置选项
require WND_PATH . 'wnd-api.php'; // API

// core
require WND_PATH . 'includes/core/inc-functions.php'; //通用函数定义
require WND_PATH . 'includes/core/inc-post.php'; //post相关自定义函数
require WND_PATH . 'includes/core/inc-user.php'; //user相关自定义函数
require WND_PATH . 'includes/core/inc-media.php'; //媒体文件处理函数
require WND_PATH . 'includes/core/inc-meta.php'; //数组形式储存 meta、option
require WND_PATH . 'includes/core/inc-term.php'; //分类、标签

require WND_PATH . 'includes/core/inc-database.php'; //数据库
require WND_PATH . 'includes/core/inc-admin.php'; //管理函数
require WND_PATH . 'includes/core/inc-verify.php'; //验证模块
require WND_PATH . 'includes/core/inc-finance.php'; //财务
require WND_PATH . 'includes/core/inc-post-type-status.php'; //自定义文章类型及状态

// ajax
require WND_PATH . 'includes/ajax/ajax-post.php'; //ajax 文章发布编辑
require WND_PATH . 'includes/ajax/ajax-media.php'; //ajax 媒体处理
require WND_PATH . 'includes/ajax/ajax-user.php'; //ajax 用户
require WND_PATH . 'includes/ajax/ajax-functions.php'; //其他ajax操作
require WND_PATH . 'includes/ajax/ajax-pay.php'; //ajax付费服务

// hook
require WND_PATH . 'includes/hook/add-action.php'; //添加动作
require WND_PATH . 'includes/hook/add-filter.php'; //添加钩子
require WND_PATH . 'includes/hook/remove-action.php'; //移除动作

// template
require WND_PATH . 'templates/tpl-general.php'; //通用模板
require WND_PATH . 'templates/tpl-user.php'; //user模板
require WND_PATH . 'templates/tpl-post.php'; //post模板
require WND_PATH . 'templates/tpl-term.php'; //term模板
require WND_PATH . 'templates/tpl-archive.php'; //归档和列表模板
require WND_PATH . 'templates/tpl-finance.php'; //财务模板
require WND_PATH . 'templates/tpl-panel.php'; //前端管理面板
require WND_PATH . 'templates/tpl-gallery.php'; //橱窗相册

/**
 *@since 2019.07.31
 *自动加载类文件
 *
 *实例
 *类名: 	Wnd_Form
 *路径: 	/includes/class/class-wnd-form.php
 */
function wnd_class_loader($class) {
	$file_name = 'class-' . str_replace('_', '-', $class);
	$file = __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . $file_name . '.php';
	if (file_exists($file)) {
		require $file;
	}
}
spl_autoload_register('wnd_class_loader');
