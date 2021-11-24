```php
use Wnd\View\Wnd_Form_WP;

/**
 * 当我们定义一个表单类作为某个固定功能模块，而该表单又可能在后期需要拓展。此时，我们可以在表单中设定一个 filter。
 * 后期，通过 这个 filter 我们可以对表单结构做出删改，甚至完全重写，而无需直接修改原有模块的代码。
 */

/**
 * @since 2019.03.10 表单filter举例
 */
add_filter('wnd_demo_form', 'wnd_filer_form_filter', 10, 1);
function wnd_filer_form_filter($input_values) {
	// 去掉一个现有字段（按表单顺序 0、1、2……）
	unset($input_values[0]);

	// 新增一个字段
	$temp_form = new Wnd_Form_WP();
	$temp_form->add_textarea(
		[
			'name'        => 'content',
			'label'       => 'content',
			'placeholder' => 'placeholder content add by filter',
			'required'    => true,
		]
	);

	// 将新增的字段数组数据合并写入并返回
	return wp_parse_args($temp_form->get_input_values(), $input_values);
}

wnd_demo_form();

/**
 * 提交表单数据至本插件定义的 Rest Api
 * 主要区别： $form->set_action($url, $method) => $form->set_route($route, $endpoint);
 */
function wnd_demo_form() {
	$form = new Wnd_Form_WP($is_ajax_submit = true);
	$form->add_form_attr('data-test', 'test-value');
	$form->set_form_title('标题');

	$form->add_text(
		[
			'name' => 'test',
		]
	);

	$form->add_radio(
		[
			'name'     => 'radio',
			'options'  => ['key1' => 'value1', 'key2' => 'value2'],
			'label'    => 'SEX',
			'required' => false,
			'checked'  => 'woman',
		]
	);

	$form->set_route('action', 'wnd_insert_post');
	$form->set_submit_button('Submit', 'is-primary');

	$form->set_filter(__FUNCTION__);

	// ajax 渲染
	echo '<div id="demo"></div>';
	$form->render('#demo');
}
```