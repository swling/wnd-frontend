<?php
use Wnd\View\Wnd_Form_WP;

/**
 * ## 统一提示：表单共分为如下模式
 * - 常规表单常规提交
 * - 常规表单 ajax 提交
 * - JS 表单 ajax 提交
 *
 * ### 常规表单与 JS 表单
 * - 通过 php echo 输出表单 HTML 为常规表单
 * - 通过 JavaScript（vue）接收表单结构数据并渲染的，称之为 JS 表单
 *
 * ### 避免常规表单
 * - 常规表单不支持文件上传，不支持富媒体编辑器，不支持各类联动等交互功能（如需支持需要自行编写 JavaScript 以实现）
 * - 绝大部分情况应避免使用常规表单
 * - 常规表单的唯一用途是：用 php 代码的方式代替手写 HTML Form
 *
 * ### 截至 v0.9.56.1 我们知道以下限制，后期可能会修正，但目前就是这样
 * - 常规表单与 JS 表单 Ajax 提交时，目前仅支持站内 API 请求，请求节点设定方式：set_route('route', 'endpoint');
 * - JS 表单仅支持 Ajax 提交
 *
 * @since 0.9.56.1
 */

/**
 * ## 常规表单，常规提交
 * @since 2019.07.17
 */
$form = new Wnd_Form_WP(false);
$form->add_text(
	[
		'name'        => 'wd',
		'label'       => 'content',
		'placeholder' => '搜索关键词',
		'required'    => true,
	]
);
$form->set_submit_button('提交');
$form->set_action('https://www.baidu.com/s', 'GET');
echo $form->build();

/**
 * ## 常规表单，Ajax 提交
 * - ajax 提交需要 set_route('route', 'endpoint'); 设定节点
 * @since 2019.07.17
 */
$form = new Wnd_Form_WP(true);
$form->add_text(
	[
		'name'        => 'user_name',
		'label'       => '用户名',
		'placeholder' => '用户名',
		'required'    => true,
	]
);
$form->set_submit_button('登录');
$form->set_route('action', 'wnd_login');
echo $form->build();

/**
 * ## JS 表单 Ajax 提交
 * - api 请求为本插件主要使用方法
 * - 本插件 Module 中表单类模块，均采用 ajax 表单
 * - Module Form 模块与本实例不同在于：Module 中返回的是表单的结构数据，通过 rest api 转化为 json数据 供前端渲染 @see Module\Wnd_Module_Form
 * - 设置请求节点方法 set_route('route', 'endpoint');
 * @since 2019.07.17
 */
$form = new Wnd_Form_WP(true);
$form->add_text(
	[
		'name'        => 'user_name',
		'label'       => '用户名',
		'placeholder' => '用户名',
		'required'    => true,
	]
);
$form->set_submit_button('登录');
$form->set_route('action', 'wnd_login');

// ajax 渲染
echo '<div id="demo"></div>';
$form->render('#demo');
