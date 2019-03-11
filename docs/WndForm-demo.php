<?php
/**
 *常规表单生成
 *@since 2019.03.10
 */

$form = new Wnd_Form();

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
		// 'label'	=>'addon'
	)
);

// html
$form->add_html('<div class="field is-horizontal"><div class="field-body">');

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

$form->add_html('</div></div>');

// select
$form->add_select(
	array(
		'name' => 'select',
		'options' => array('select1' => 'value1', 'select2' => 'value2'),
		'label' => 'Dropdown',
		'required' => false,
		'checked' => 'value2', //default checked value
	)
);

// checkbox
$form->add_checkbox(
	array(

		'name' => 'checkbox',
		'value' => 1,
		'label' => 'checkbox',
		'checked' => 1, //default checked
	)
);

// upload image
$form->add_image_upload(
	array(
		'id' => 'image-upload',
		'name' => 'file', // file input field name
		'label' => 'Image upload',
		'thumbnail' => 'https://www.baidu.com/img/baidu_jgylogo3.gif', // default thumbnail image url, maybe replace this after ajax uploaded
		'thumbnail_size' => array('width' => 100, 'height' => 100), //thumbnail image size
		'file_id' => 10, //data-file-id on delete button，in some situation, you want delete the file
		'data' => array( // some hidden input,maybe useful in ajax upload
			'meta_key' => 'avatar',
			'save_width' => '0',
			'save_hight' => '0',
		),
	)
);

// upload file
$form->add_file_upload(
	array(
		'id' => 'file-upload',
		'name' => 'file', // file input field name
		'label' => 'File upland',
		'file_name' => 'file name',
		'file_id' => 0, //data-file-id on delete button，in some situation, you want delete the file
		'data' => array('meta_key' => 'file'), // some hidden input,maybe useful in ajax upload
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

$form->set_action('post', 'https://www.baidu.com');
$form->set_form_attr('id="my-form-id"');
$form->set_submit_button('Submit', 'is-primary');

$form->build();

echo $form->html;
