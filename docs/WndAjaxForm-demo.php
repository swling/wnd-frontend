<?php
/**
 *适配WndWP插件ajax表单生成
 *@since 2019.03.10
 */

/**
 *@since 2019.03.10 表单filter举例
 *
 */
add_filter('_wnd_demo_form', 'my_add_filer_form_filter', $priority = 10, $accepted_args = 1);
function my_add_filer_form_filter($input_values) {

	// 去掉一个现有字段（按表单顺序 0、1、2……）
	unset($input_values[0]);

	// 新增一个字段
	$temp_form = new Wnd_Ajax_Form;
	$temp_form->add_textarea(
		array(
			'name' => 'content',
			'label' => 'content',
			'placeholder' => 'placeholder content add by filter',
			'required' => true,
		)
	);

	// 将新增的字段数组数据合并写入并返回
	return wp_parse_args($temp_form->get_input_values(), $input_values);

}

/**
 *@since 2019.03.10 ajax表单demo
 */
function _wnd_demo_form() {

	$form = new Wnd_Ajax_Form();

	$form->set_form_attr('id="my-form-id"');
	$form->set_form_title('标题');

	// input
	$form->add_text(
		array(
			'name' => 'user_name',
			'value' => '',
			'placeholder' => 'user name',
			'label' => 'User name<span class="required">*</span> ',
			'has_icons' => 'left', //icon position "left" orf "right"
			'icon' => '<i class="fas fa-user"></i>', // icon html @link https://fontawesome.com/
			'autofocus' => 'autofocus',
			'required' => true,
		)
	);

	// input
	$form->add_email(
		array(
			'name' => 'email',
			'value' => '',
			'placeholder' => 'email',
			'label' => 'Email <span class="required">*</span>',
			'has_icons' => 'left',
			'icon' => '<i class="fas fa-envelope"></i>',
			'required' => false,
		)
	);

	// password
	$form->add_password(
		array(
			'name' => 'password',
			'value' => '',
			'label' => 'Password <span class="required">*</span>',
			'placeholder' => 'password',
			'has_icons' => 'left',
			'icon' => '<i class="fas fa-unlock-alt"></i>',
			'required' => false,
		)
	);

	// has addon
	$form->add_text(
		array(
			'addon' => '<button type="button" class="send-code button is-primary">获取验证码</button>',
			'name' => 'test',
		)
	);

	// html
	$form->add_html('<div class="field is-horizontal"><div class="field-body">');

	// dropdown
	$form->add_dropdown(
		array(
			'name' => 'dropdown1',
			'options' => array('select1' => 'value1', 'select2' => 'value2'),
			'label' => 'Dropdown1',
			'required' => false,
			'checked' => 'value2', //default checked value
		)
	);

	// dropdown
	$form->add_dropdown(
		array(
			'name' => 'dropdown2',
			'options' => array('select1' => 'value1', 'select2' => 'value2'),
			'label' => 'Dropdown2',
			'required' => false,
			'checked' => 'value2', //default checked value
		)
	);

	$form->add_html('</div></div>');

	// radio
	$form->add_Radio(
		array(
			'name' => 'radio',
			'value' => array('key1' => 'value1', 'key2' => 'value2'),
			'label' => 'SEX',
			'required' => false,
			'checked' => 'woman', //default checked value
		)
	);

	// checkbox
	$form->add_checkbox(
		array(

			'name' => 'checkbox',
			'value' => array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'),
			'label' => 'checkbox',
			'checked' => 'value3', //default checked value
		)
	);

	// upload image ajax 后台wnd_file_upload已匹配规则，此处强制input name: file 无需额外设置
	$form->add_image_upload(
		array(
			'id' => 'image-upload', //container id
			'label' => 'Image upload',
			'thumbnail' => 'https://www.baidu.com/img/baidu_jgylogo3.gif', // default thumbnail image url, maybe replace this after ajax uploaded
			'thumbnail_size' => array('width' => 100, 'height' => 100), //thumbnail image size
			'file_id' => 0, //data-file-id on delete button，in some situation, you want delete the file
			'data' => array( // some hidden input,maybe useful in ajax upload
				'meta_key' => 'avatar',
				'save_width' => '0', //图片文件存储最大宽度 0 为不限制
				'save_hight' => '0', //图片文件存储最大过度 0 为不限制
				'post_parent' => 0, //如果设置了post parent, 则上传的附件id将保留在对应的wnd_post_meta 否则保留为 wnd_user_meta
			),
		)
	);

	// upload file ajax 后台wnd_file_upload已匹配规则，此处强制input name: file 无需额外设置
	$form->add_file_upload(
		array(
			'id' => 'file-upload', //container id
			'label' => 'File upland',
			'file_name' => 'file name', //文件显示名称
			'file_id' => 0, //data-file-id on delete button，in some situation, you want delete the file
			'data' => array( // some hidden input,maybe useful in ajax upload
				'meta_key' => 'file',
				'post_parent' => 0, //如果设置了post parent, 则上传的附件id将保留在对应的wnd_post_meta 否则保留为 wnd_user_meta
			),
		)
	);

	// textarea
	$form->add_textarea(
		array(
			'name' => 'content',
			'label' => 'content',
			'placeholder' => 'placeholder content',
			'required' => true,
		)
	);

	$form->add_sms_verify($verify_type = 'verify', $template = '');

	$form->add_email_verify($verify_type = 'verify', $template = '');

	// 与该表单数据匹配的后端处理函数
	$form->set_action('wnd_inset_post');

	$form->set_submit_button('Submit', 'is-primary');

	/**
	 *@since 2019.03.10 设置表单结构filter，用法详见顶部代码*
	 */
	// 以当前函数名设置filter hook
	$form->set_filter(__FUNCTION__);

	$form->build();

	echo $form->html;
}