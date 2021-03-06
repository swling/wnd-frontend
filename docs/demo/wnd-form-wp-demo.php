<?php
use Wnd\View\Wnd_Form_WP;

/**
 *仅仅输出表单字段
 *@since 2019.03.10
 */
$form = new Wnd_Form_WP($is_ajax_submit = true);
$form->add_text(
	[
		'addon_right' => '<button type="button" class="send-code button is-primary">获取验证码</button>',
		'name'        => 'test',
	]
);
echo $form->get_input_fields();

/**
 *@since 2019.08.29
 *常规表单提交
 *
 *@since 2019.07.17
 *添加选项：$this->is_ajax_submit 设置表单提交方式
 *若为false，则生成的表单为常规表单、需要设置表单接收地址，及Methods等、文件上传也需要自行处理
 *
 *
 **/
$temp_form = new Wnd_Form_WP($is_ajax_submit);
$temp_form->add_text(
	[
		'name'        => 'wd',
		'label'       => 'content',
		'placeholder' => '搜索关键词',
		'required'    => true,
	]
);
$temp_form->set_submit_button('提交');
$temp_form->set_action('https://www.baidu.com/s', 'GET');
$temp_form->build();

echo $temp_form->html;

/**
 *@since 2019.03.10 表单filter举例
 *
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
 *@since 2019.03.10 ajax表单demo
 *提交表单数据至本插件定义的 Rest Api
 *主要区别： $form->set_action($url, $method) => $form->set_route($route, $endpoint);
 */
function wnd_demo_form() {

	$form = new Wnd_Form_WP($is_ajax_submit = true);

	$form->add_form_attr('data-test', 'test-value');
	$form->set_form_title('标题');

	// input
	$form->add_text(
		[
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
		]
	);

	// has addon and icon
	$form->add_text(
		[
			'icon_right'  => '<i class="fas fa-user"></i>', // icon html @link https://fontawesome.com/
			'icon_left'   => '<i class="fas fa-user"></i>', // icon html @link https://fontawesome.com/
			'addon_right' => '<button type="button" class="send-code button is-primary">获取验证码</button>',
			'addon_left'  => '<button type="button" class="send-code button is-primary">获取验证码</button>',
			'name'        => 'test',
			// 'label' => 'Input with addons',
			// 'disabled' => true,
		]
	);

	/**
	 *@since 2020.04.20
	 *当前字段可复制追加（需要对应前端js支持）
	 *
	 */
	$form->add_text(
		[
			'addon_right' => '<button type="button" class="button add-row">+</button>',
			'name'        => 'test[]',
		]
	);

	// input
	$form->add_number(
		[
			'name'        => 'number',
			'value'       => '',
			'placeholder' => 'number',
			'label'       => 'Number<span class="required">*</span> ',
			'icon_left'   => '<i class="fas fa-user"></i>', // icon html @link https://fontawesome.com/
			'autofocus'   => 'autofocus',
			'required'    => true,
		]
	);

	// input
	$form->add_email(
		[
			'name'        => 'email',
			'value'       => '',
			'placeholder' => 'email',
			'label'       => 'Email <span class="required">*</span>',
			'icon_left'   => '<i class="fas fa-envelope"></i>',
			'required'    => false,
		]
	);

	// password
	$form->add_password(
		[
			'name'        => 'password',
			'value'       => '',
			'label'       => 'Password <span class="required">*</span>',
			'placeholder' => 'password',
			'icon_left'   => '<i class="fas fa-unlock-alt"></i>',
			'required'    => false,
		]
	);

	// html
	$form->add_html('<div class="field is-horizontal"><div class="field-body">');

	// select
	$form->add_select(
		[
			'name'     => 'select1',
			'options'  => ['select1' => 'value1', 'select2' => 'value2'],
			'label'    => 'Dropdown1',
			'required' => false,
			'selected' => 'value2', //default selected value
		]
	);

	// select
	$form->add_select(
		[
			'name'     => 'select2',
			'options'  => ['select1' => 'value1', 'select2' => 'value2'],
			'label'    => 'Dropdown2',
			'required' => false,
			'selected' => 'value2', //default selected value
		]
	);

	$form->add_html('</div></div>');

	// radio
	$form->add_radio(
		[
			'name'     => 'radio',
			'options'  => ['key1' => 'value1', 'key2' => 'value2'],
			'label'    => 'SEX',
			'required' => false,
			'checked'  => 'woman', //default checked value
		]
	);

	// checkbox
	$form->add_checkbox(
		[
			'name'    => 'checkbox[]',
			'options' => ['小' => '0.01', '中' => '10', '大' => '100'],
			'label'   => 'checkbox',
			'checked' => ['0.01', '100'], // checked
		]
	);

	/**
	 *@since 2019.04.08
	 *https://wikiki.github.io/form/checkradio/
	 */
	$form->add_radio(
		[
			'name'     => 'total_amount',
			'options'  => ['0.01' => '0.01', '10' => '10'],
			'required' => 'required',
			'checked'  => '0.01', //default checked value
			'class'    => 'is-checkradio is-danger',
		]
	);

	/**
	 *@since 2019.04.08
	 *@link https://wikiki.github.io/form/switch/
	 */
	$form->add_checkbox(
		[
			'name'    => '_usermeta_auto_play',
			'options' => ['首页自动播放' => '1'],
			'checked' => wnd_get_user_meta(get_current_user_id(), 'auto_play') ? 1 : 0, //default checked
			'id'      => 'auto_play',
			'class'   => 'switch is-danger',
		]
	);

	/**
	 * @since 2019.12.13
	 * 设置表单默认缩略图尺寸：非保存尺寸
	 * 该尺寸可被具体图片上传字段中：$args['thumbnail_size']参数覆盖
	 *
	 * 如果表单中不同图片上传需要设置不同的缩略图，则重复调用该方法即可覆盖之前的设定
	 */
	$form->set_thumbnail_size(100, 100);

	/**
	 *图像上传
	 *
	 *@since 2020.04.13 支持上传附件存储至option
	 *_option_{$meta_key}则存储至option
	 *
	 *如果设置了data['post_parent'], 则上传的附件id将保留在对应的wnd_post_meta 否则保留为 wnd_user_meta
	 */
	$form->add_image_upload(
		[
			// 'id' => 'image-upload', //container id
			'name'           => 'demo', //由于采用了ajax上传，$_FILES['name']取决于js脚本定义，此处不会直接传向后端（可省略）
			'file_id'        => 0, //指定上传文件id，用于编辑；若未指定id，则根据 meta_key 与 post_parent 及当前用户id综合查询
			'label'          => 'Image upload',
			'thumbnail'      => 'https://www.baidu.com/img/baidu_jgylogo3.gif', // default thumbnail image url, maybe replace this after ajax uploaded
			'thumbnail_size' => ['width' => 100, 'height' => 100], //thumbnail image size
			'data'           => [ // some data on file input, maybe useful in ajax upload
				'meta_key'    => 'avatar',
				'save_width'  => '0', //图片文件存储最大宽度 0 为不限制
				'save_height' => '0', //图片文件存储最大过度 0 为不限制
				'post_parent' => 0, //如果设置了post parent, 则上传的附件id将保留在对应的wnd_post_meta 否则保留为 wnd_user_meta
				'user_id'     => get_current_user_id(), //如果未设置了post parent, 保留为指定用户的 wnd_user_meta
			],
			'delete_button'  => true,
			'disabled'       => false,
			'required'       => 'required',
		]
	);

	/**
	 *文件上传
	 *
	 *@since 2020.04.13 支持上传附件存储至option
	 *_option_{$meta_key}则存储至option
	 *
	 *如果设置了data['post_parent'], 则上传的附件id将保留在对应的wnd_post_meta 否则保留为 wnd_user_meta
	 */
	$form->add_file_upload(
		[
			// 'id' => 'file-upload', //container id
			'name'          => 'demo', //由于采用了ajax上传，$_FILES['name']取决于js脚本定义，此处不会直接传向后端（可省略）
			'file_id'       => 0, //指定上传文件id，用于编辑；若未指定id，则根据 meta_key 与 post_parent 及当前用户id综合查询
			'label'         => 'File upland',
			'data'          => [ // some data on file input, maybe useful in ajax upload
				'meta_key'    => 'file',
				'post_parent' => 0, //如果设置了post parent, 则上传的附件id将保留在对应的wnd_post_meta 否则保留为 wnd_user_meta
				'user_id'     => get_current_user_id(), //如果未设置了post parent, 保留为指定用户的 wnd_user_meta
			],
			'delete_button' => true,
			'disabled'      => false,
			'required'      => 'required',
		]
	);

	/**
	 *@since 2019.05.07 产品相册
	 */
	$form->add_gallery_upload(
		[
			'label'          => '产品相册',
			'thumbnail_size' => ['height' => '160', 'width' => '120'],
			'data'           => [
				'post_parent' => 1, //如果设置了post parent, 则上传的附件id将保留在对应的wnd_post_meta 否则保留为 wnd_user_meta
				'user_id'     => get_current_user_id(), //如果未设置了post parent, 保留为指定用户的 wnd_user_meta
				'save_width'  => 0, //图片文件存储最大宽度 0 为不限制
				'save_height' => 0, //图片文件存储最大过度 0 为不限制
			],
		]
	);

	/**
	 *@since 2019.05.10
	 *通过html方式新增的字段，无法直接通过表单name一致性验证
	 *需要对应新增一个同名name，告知表单校验，此处额外新增另一个name
	 */
	$form->add_html('<input type="text" name="demo" value="demo">');
	$form->add_input_name('demo');

	// textarea
	$form->add_textarea(
		[
			'name'        => 'content',
			'label'       => 'content',
			'placeholder' => 'placeholder content',
			'required'    => true,
		]
	);

	/**
	 *短信校验
	 *@param $verify_type 	string 'register' / 'reset_password' 为保留字段, 用途为：注册 / 重置密码
	 *注册时若当前手机已注册，则无法发送验证码
	 *找回密码时若当前手机未注册，则无法发送验证码
	 **/
	$form->add_phone_verification($verify_type = 'verify', $template = '', $style = 'is-primary');

	/**
	 *邮箱校验
	 *@param $verify_type 	string 'register' / 'reset_password' 为保留字段, 用途为：注册 / 重置密码
	 *注册时若当前邮箱已注册，则无法发送验证码
	 *找回密码时若当前邮箱未注册，则无法发送验证码
	 **/
	$form->add_email_verification($verify_type = 'verify', $template = '', $style = 'is-primary');

	/**
	 *@since 2019.08.23
	 *新增HTML5 字段
	 **/
	$form->add_color(
		[
			'name'  => 'color',
			'value' => '#990000',
		]
	);

	$form->add_date(
		[
			'name' => 'date',
			'min'  => '2019-08-23',
			'max'  => '3019-08-31',
		]
	);

	$form->add_range(
		[
			'name' => 'range',
			'min'  => '0',
			'max'  => '10',
			'step' => '0.1',
		]
	);

	$form->add_url(
		[
			'name' => 'url',
		]
	);

	// 138-5200-1900
	$form->add_tel(
		[
			'name'    => 'tel',
			'label'   => '格式：xxx-xxxx-xxxx',
			'pattern' => '[0-9]{3}-[0-9]{4}-[0-9]{4}',
		]
	);

	$form->set_route('action', 'wnd_insert_post');
	$form->set_submit_button('Submit', 'is-primary');

	/**
	 *@since 2019.03.10 设置表单结构filter，用法详见顶部代码*
	 */
	// 以当前函数名设置filter hook
	$form->set_filter(__FUNCTION__);
	$form->build();
	echo $form->html;
}
