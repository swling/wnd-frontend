```php
/**
 * 多层级分类联动
 * @see 本实例最终采用了 $form->render('#demo'); 将表单在当前页面渲染，但在实际开发中，应该新增对应的表单类 Module 通过 reset api 调用 @see Module\Wnd_Module_Form
 * @since 2020.04.15
 */
use Wnd\View\Wnd_Form_Post;
$form = new Wnd_Form_Post;
$form->add_post_term_select(['taxonomy' => 'category']);
$form->set_submit_button('提交');

echo '<div id="demo"></div>';
$form->render('#demo');
```