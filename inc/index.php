<?php

/**
 *加载php文件
 */
require WND_PATH . 'inc/wnd-api.php'; // API

require WND_PATH . 'inc/class-wnd-form-data.php'; //表单数据处理类
require WND_PATH . 'inc/class-wnd-form.php'; //表单生成类
require WND_PATH . 'inc/class-wnd-ajax-form.php'; //Ajax表单生成类
require WND_PATH . 'inc/class-wnd-user-form.php'; //用户表单生成类
require WND_PATH . 'inc/class-wnd-post-form.php'; //文章表单生成类

require WND_PATH . 'inc/wnd-options.php'; //配置选项
require WND_PATH . 'inc/wnd-database.php'; //数据库

require WND_PATH . 'inc/inc-functions.php'; //通用函数定义
require WND_PATH . 'inc/inc-post.php'; //post相关自定义函数
require WND_PATH . 'inc/inc-user.php'; //user相关自定义函数
require WND_PATH . 'inc/inc-media.php'; //媒体文件处理函数
require WND_PATH . 'inc/inc-meta.php'; //数组形式储存 meta、option
require WND_PATH . 'inc/inc-term.php'; //分类、标签

require WND_PATH . 'inc/inc-admin.php'; //管理函数
require WND_PATH . 'inc/inc-verify.php'; //验证模块
require WND_PATH . 'inc/inc-finance.php'; //财务
require WND_PATH . 'inc/inc-post-type-status.php'; //自定义文章类型及状态

require WND_PATH . 'inc/ajax-post.php'; //ajax 文章发布编辑
require WND_PATH . 'inc/ajax-media.php'; //ajax 媒体处理
require WND_PATH . 'inc/ajax-user.php'; //ajax 用户
require WND_PATH . 'inc/ajax-functions.php'; //其他ajax操作
require WND_PATH . 'inc/ajax-pay.php'; //ajax付费服务

require WND_PATH . 'inc/add-action.php'; //添加的动作
require WND_PATH . 'inc/add-filter.php'; //添加的钩子

require WND_PATH . 'template/tpl-functions.php'; //模板函数
require WND_PATH . 'template/tpl-user.php'; //user模板
require WND_PATH . 'template/tpl-post.php'; //post模板
require WND_PATH . 'template/tpl-term.php'; //term模板
require WND_PATH . 'template/tpl-archive.php'; //归档和列表模板
require WND_PATH . 'template/tpl-filter.php'; //多重筛选
require WND_PATH . 'template/tpl-finance.php'; //财务模板
require WND_PATH . 'template/tpl-admin.php'; //前端管理模板
require WND_PATH . 'template/tpl-gallery.php'; //橱窗相册
