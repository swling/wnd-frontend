<?php

/**
 *加载php文件
 */
require WNDWP_PATH . 'inc/handle-array.php'; //数组形式储存 meta、option
require WNDWP_PATH . 'inc/handle-ajax.php'; //处理ajax action

require WNDWP_PATH . 'inc/class-wnd-form-data.php'; //表单数据处理类
require WNDWP_PATH . 'inc/class-wnd-form.php'; //表单生成类
require WNDWP_PATH . 'inc/class-wnd-ajax-form.php'; //Ajax表单生成类
require WNDWP_PATH . 'inc/class-wnd-user-form.php'; //用户表单生成类
require WNDWP_PATH . 'inc/class-wnd-post-form.php'; //文章表单生成类

require WNDWP_PATH . 'inc/wnd-options.php'; //配置选项
require WNDWP_PATH . 'inc/wnd-database.php'; //数据库

require WNDWP_PATH . 'inc/inc-functions.php'; //通用函数定义
require WNDWP_PATH . 'inc/inc-admin.php'; //管理函数
require WNDWP_PATH . 'inc/inc-post.php'; //post相关自定义函数
require WNDWP_PATH . 'inc/inc-user.php'; //user相关自定义函数
require WNDWP_PATH . 'inc/inc-media.php'; //媒体文件处理函数
require WNDWP_PATH . 'inc/inc-verify.php'; //验证模块
require WNDWP_PATH . 'inc/inc-term.php'; //分类、标签
require WNDWP_PATH . 'inc/inc-finance.php'; //财务
require WNDWP_PATH . 'inc/inc-post-type.php'; //自定义类型

require WNDWP_PATH . 'inc/ajax-post.php'; //ajax 文章发布编辑
require WNDWP_PATH . 'inc/ajax-media.php'; //ajax 媒体处理
require WNDWP_PATH . 'inc/ajax-user.php'; //ajax 用户
require WNDWP_PATH . 'inc/ajax-functions.php'; //其他ajax操作
require WNDWP_PATH . 'inc/ajax-pay.php'; //ajax付费服务

require WNDWP_PATH . 'inc/add-action.php'; //添加的动作
require WNDWP_PATH . 'inc/add-filter.php'; //添加的钩子

require WNDWP_PATH . 'template/user.php'; //user模板
require WNDWP_PATH . 'template/post.php'; //post模板
require WNDWP_PATH . 'template/term.php'; //term模板
require WNDWP_PATH . 'template/archive.php'; //归档和列表模板
require WNDWP_PATH . 'template/multiple-tabs.php'; //多重筛选
require WNDWP_PATH . 'template/finance.php'; //财务模板
require WNDWP_PATH . 'template/admin.php'; //前端管理模板
