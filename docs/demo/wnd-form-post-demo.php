<?php
/**
 *@since 2020.04.15
 *
 *多层级分类联动
 */
use Wnd\View\Wnd_Form_Post;
$form = new Wnd_Form_Post;
$form->add_html('<div class="field is-horizontal"><div class="field-body">');
$form->add_post_term_select(['taxonomy' => 'category'], $label = '', $required = true, $dynamic = true);
$form->add_dynamic_sub_term_select('category', 1, $label = '', '二级');
$form->add_dynamic_sub_term_select('category', 2, $label = '', '三级');
$form->add_html('</div></div>');
$form->set_submit_button('提交');
$form->build();
echo $form->html;
