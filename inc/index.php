<?php

/*
 *加载php文件
 */
require WNDWP_PATH . 'inc/handle-array.php'; //数组形式储存 meta、option
require WNDWP_PATH . 'inc/handle-form.php'; //表单数据处理
require WNDWP_PATH . 'inc/handle-ajax.php'; //处理ajax action

require WNDWP_PATH . 'inc/functions.php'; //通用函数定义
require WNDWP_PATH . 'inc/options.php'; //配置选项
require WNDWP_PATH . 'inc/database.php'; //数据库

require WNDWP_PATH . 'inc/post.php'; //文章发布编辑
require WNDWP_PATH . 'inc/media.php'; //媒体处理
require WNDWP_PATH . 'inc/user.php'; //用户

require WNDWP_PATH . 'inc/pay.php'; //付费功能
require WNDWP_PATH . 'pay/functions.php'; //充值模块

require WNDWP_PATH . 'inc/add-action.php'; //添加的动作
require WNDWP_PATH . 'inc/add-filter.php'; //添加的钩子

require WNDWP_PATH . 'template/user.php'; //user模板
require WNDWP_PATH . 'template/post.php'; //post模板

// 短信功能
if (wnd_get_option('wndwp', 'wnd_sms_enable') !=0 ) {
	require WNDWP_PATH . 'sms/sms.php'; //短信模块
}

// Term功能
if (wnd_get_option('wndwp', 'wnd_term_enable') !=0 ) {
	require WNDWP_PATH . 'inc/term.php'; //分类、标签
}
