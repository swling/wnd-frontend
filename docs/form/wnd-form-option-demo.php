<?php
/**
 * ## Option Form
 * - 本实例中：所有表单数据将以数组形式存储在 wndt option数据中
 * - 具体表单字段将以对应的form name为数组键名存储对应的value
 * - 如logo值调用方法：get_option('wndt')['logo']
 * - 文件上传存储值为attachment id
 *
 * ### 除Wnd_Form常规字段之外，新增了如下字段：
 * - 页面下拉 add_page_select('ucenter', '用户中心页面', true);
 * - Term下拉 add_term_select($option_key, $args_or_taxonomy, $label = '', $required = true, $dynamic_sub = false);
 *
 * @see 本实例最终采用了 $form->render('#demo'); 将表单在当前页面渲染，但在实际开发中，应该新增对应的表单类 Module 通过 reset api 调用 @see Module\Wnd_Module_Form
 */

use Wnd\View\Wnd_Form_Option;

$form = new Wnd_Form_Option('wndt', false);
$form->add_image_upload('banner', 0, 0, 'Banner');

$form->add_text(
	[
		'name'     => 'logo',
		'label'    => 'Logo',
		'required' => false,
	]
);

$form->add_number(
	[
		'name'     => 'gallery_picture_limit',
		'label'    => '产品相册图片',
		'required' => false,
	]
);

$form->add_page_select('ucenter', '用户中心页面', true);

$form->add_term_select('default_cat', 'category', $label = '默认分类', $required = true, $dynamic_sub = false);

$form->add_url(
	[
		'name'     => 'social_redirect_url',
		'label'    => '社交登录回调地址',
		'required' => false,
	]
);

$form->add_textarea(
	[
		'name'     => 'statistical_code',
		'label'    => '流量统计代码',
		'required' => false,
	]
);

$form->set_submit_button('保存', 'is-danger');
// echo $form->build();

echo '<div id="demo"></div>';
$form->render('#demo');
