<?php
use Wnd\View\Wnd_Form;

/**
 *仅仅输出表单字段
 *@since 2019.04.28
 */
$form = new Wnd_Form;
$form->add_text(
	array(
		'addon_right' => '<button type="button" class="send-code button is-primary">获取验证码</button>',
		'name'        => 'test',
	)
);
echo $form->get_input_fields();

/**
 *字段数组数据输出与设置
 *@since 2019.04.28
 */
$form = new Wnd_Form();
$form->add_hidden('hidden_key', 'hidden_value');
// 获取当前表单的组成数据数组（通常用于配合 filter 过滤）
$form->get_input_values();
// 直接设置表单的组成数组（通常用于配合 filter 过滤）
// $form->set_input_values($input_values);

/**
 *常规表单生成
 *@since 2019.03.10
 */
$form = new Wnd_Form();
$form->add_form_attr('data-test', 'test-value');
$form->set_form_title('标题');

// input
$form->add_text(
	array(
		'id'          => 'demo' . uniqid(),
		'name'        => 'user_name',
		'value'       => '',
		'placeholder' => 'user name',
		'label'       => 'User name<span class="required">*</span> ',
		'icon_right'  => '<i class="fas fa-user"></i>', // icon html @link https://fontawesome.com/
		'icon_left'   => '<i class="fas fa-user"></i>', // icon html @link https://fontawesome.com/
		'autofocus'   => 'autofocus',
		'required'    => true,
		'readonly'    => false,
	)
);

// has addon and icon
$form->add_text(
	array(
		'icon_right'  => '<i class="fas fa-user"></i>', // icon html @link https://fontawesome.com/
		'icon_left'   => '<i class="fas fa-user"></i>', // icon html @link https://fontawesome.com/
		'addon_right' => '<button type="button" class="send-code button is-primary">获取验证码</button>',
		'addon_left'  => '<button type="button" class="send-code button is-primary">获取验证码</button>',
		'name'        => 'test',
		// 'label' => 'Input with addons',
		// 'disabled' => true,
	)
);

// input
$form->add_number(
	array(
		'name'        => 'number',
		'value'       => '',
		'placeholder' => 'number',
		'label'       => 'Number<span class="required">*</span> ',
		'icon_left'   => '<i class="fas fa-user"></i>', // icon html @link https://fontawesome.com/
		'autofocus'   => 'autofocus',
		'required'    => true,
	)
);

// input
$form->add_email(
	array(
		'name'        => 'email',
		'value'       => '',
		'placeholder' => 'email',
		'label'       => 'Email <span class="required">*</span>',
		'icon_left'   => '<i class="fas fa-envelope"></i>',
		'required'    => false,
	)
);

// password
$form->add_password(
	array(
		'name'        => 'password',
		'value'       => '',
		'label'       => 'Password <span class="required">*</span>',
		'placeholder' => 'password',
		'icon_left'   => '<i class="fas fa-unlock-alt"></i>',
		'required'    => false,
	)
);

// radio
$form->add_radio(
	array(
		'name'     => 'radio',
		'options'  => ['key1' => 'value1', 'key2' => 'value2'],
		'label'    => 'SEX',
		'required' => false,
		'checked'  => 'woman', //default checked value
	)
);

// html
$form->add_html('<div class="field is-horizontal"><div class="field-body">');

// select
$form->add_select(
	array(
		'name'     => 'select',
		'options'  => ['select1' => 'value1', 'select2' => 'value2'],
		'label'    => 'Dropdown',
		'required' => false,
		'checked'  => 'value2', //default checked value
	)
);

// checkbox
$form->add_checkbox(
	array(
		'name'    => 'checkbox[]',
		'options' => ['小' => '0.01', '中' => '10', '大' => '100'],
		'label'   => 'checkbox',
		'checked' => ['0.01', '100'], // checked
	)
);

$form->add_html('</div></div>');

/**
 *@since 2019.04.08 bulma拓展样式
 *https://wikiki.github.io/form/checkradio/
 */
$form->add_radio(
	array(
		'name'     => 'total_amount',
		'options'  => ['0.01' => '0.01', '10' => '10'],
		'required' => 'required',
		'checked'  => '0.01', //default checked value
		'class'    => 'is-checkradio is-danger',
	)
);

/**
 *@since 2019.04.08 bulma拓展样式
 *@link https://wikiki.github.io/form/switch/
 */
$form->add_checkbox(
	array(
		'name'    => '_usermeta_auto_play',
		'options' => ['首页自动播放' => '1'],
		'checked' => wnd_get_user_meta(get_current_user_id(), 'auto_play') ? 1 : 0, //default checked
		'id'      => 'auto_play',
		'class'   => 'switch is-danger',
	)
);

/**
 * @since 2019.12.13
 * 设置表单默认缩略图尺寸：非保存尺寸
 * 该尺寸可被具体图片上传字段中：$args['thumbnail_size']参数覆盖
 *
 * 如果表单中不同图片上传需要设置不同的缩略图，则重复调用该方法即可覆盖之前的设定
 */
$form->set_thumbnail_size(100, 100);

// upload image
$form->add_image_upload(
	array(
		// 'id' => 'image-upload',
		'name'           => 'file', // file input field name
		'label'          => 'Image upload',
		'thumbnail'      => 'https://www.baidu.com/img/baidu_jgylogo3.gif', // default thumbnail image url, maybe replace this after ajax uploaded
		'thumbnail_size' => ['width' => 100, 'height' => 100], //thumbnail image size
		'file_id'        => 10, //data-file-id on delete button，in some situation, you want delete the file
		'data'           => array( // some data on file input, maybe useful in ajax upload
			'meta_key'    => 'avatar',
			'save_width'  => '0',
			'save_height' => '0',
		),
		'delete_button'  => true,
		'required'       => 'required',
	)
);

// upload file
$form->add_file_upload(
	array(
		// 'id' => 'file-upload',
		'name'          => 'file', // file input field name
		'label'         => 'File upland',
		'file_name'     => 'file name',
		'file_id'       => 0, //data-file-id on delete button，in some situation, you want delete the file
		'data'          => ['meta_key' => 'file'], // some data on file input, maybe useful in ajax upload
		'delete_button' => true,
		'required'      => 'required',
	)
);

// textarea
$form->add_textarea(
	array(
		'name'        => 'content',
		'label'       => 'content',
		'placeholder' => 'placeholder content',
		'required'    => true,
	)
);

/**
 *@since 2019.08.23
 *新增HTML5 字段
 **/
$form->add_color(
	array(
		'name'  => 'color',
		'value' => '#990000',
	)
);

$form->add_date(
	array(
		'name' => 'date',
		'min'  => '2019-08-23',
		'max'  => '3019-08-31',
	)
);

$form->add_range(
	array(
		'name' => 'range',
		'min'  => '0',
		'max'  => '10',
		'step' => '0.1',
	)
);

$form->add_url(
	array(
		'name' => 'url',
	)
);

// 138-5200-1900
$form->add_tel(
	array(
		'name'    => 'tel',
		'label'   => '格式：xxx-xxxx-xxxx',
		'pattern' => '[0-9]{3}-[0-9]{4}-[0-9]{4}',
	)
);

$form->set_action('post', 'https://www.baidu.com');
$form->set_submit_button('Submit', 'is-primary');

$form->build();

echo $form->html;
