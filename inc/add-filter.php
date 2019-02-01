<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/*
通过WordPress filter重写一些WordPress原生功能
 */

/**
 *@since 2019.01.22
 *检测当前信息是否可以注册新用户
 */
// apply_filters('wnd_can_reg', array('status'=>1,'msg'=>'默认通过'));
add_filter('wnd_can_reg', 'wnd_filter_can_reg', 10, 1);
function wnd_filter_can_reg($can_array) {

	// 后台注册选项
	if(!get_option( 'users_can_register')){
		return array ('status'=>0,'msg'=>'站点已关闭注册！');
	}

	// 手机短信验证:手机 验证码 是否为注册
	if (function_exists('wnd_verify_sms')) {

		$code = $_POST['sms_code'];
		$phone = $_POST['sms_phone'];
		$sms_error = wnd_verify_sms($phone, $code, $is_reg = true);

		if ($sms_error['status'] === 0) {
			return $sms_error;
		}

	}

	return $can_array;

}

/**
 * 用户更新账户权限
 *@since 2019.01.22
 */
add_filter('wnd_can_update_account', 'wnd_filter_can_update_account', 10, 1);
function wnd_filter_can_update_account($can_array) {

	// return array('status'=>0,'msg'=>'更新账户，测试拒绝！');

	// 手机短信验证
	if (function_exists('wnd_verify_sms')) {

		$code = $_POST['sms_code'];
		$user_id = get_current_user_id();
		$phone = wnd_get_user_meta($user_id, 'phone');

		$sms_error = wnd_verify_sms($phone, $code, $is_reg = false);

		if ($sms_error['status'] === 0) {
			return $sms_error;
		}
	}

	return $can_array;

}

/**
 *@since 2019.01.16
 * 限制wp editor上传附件
 */
add_filter('wp_handle_upload_prefilter', 'wnd_filter_limit_upload');
function wnd_filter_limit_upload($file) {

	// 上传体积限制
	$image_size = $file['size'] / 1024;
	$limit = wnd_get_option('wndwp', 'wnd_max_upload') ?: 2048;

	if ($image_size > $limit) {
		$file['error'] = '上传文件不得超过' . $limit . 'KB';
	}

	// 检测文件的类型是否是图片
	$mimes = array('image/jpeg', 'image/png', 'image/gif');
	if (in_array($file['type'], $mimes)) {
		return $file;
	}

	// 非图片文件MD5重命名（用于付费下载加密）
	$info = pathinfo($file['name']);
	$ext = '.' . $info['extension'];

	// 对随机码再做md5加密
	$md5 = md5(wnd_random(6).time());
	$file['name'] = $md5 . $ext;

	return $file;

}

/**
*@since 2019.01.31 重写WordPress原生编辑链接到指定的页面(未完成)
*/
add_filter( 'get_edit_post_link', 'wnd_filter_edit_post_link', $priority = 10, $accepted_args = 3 );
function wnd_filter_edit_post_link($link,$post_id,$context){

	// return $context;

	// return 'https://www.baidu.com/?post_id='.$post_id;
	return $link;

}

/**
 *############################################################################ 付费内容
 *@since 2018.09.17
 *设置 文章自定义字段 price
 *使用WordPress经典编辑器插入 more标签 或者编辑源码插入 <!--more--> 以区分免费内容和付费内容
 */
add_filter('the_content', 'wnd_filter_the_content', $priority = 10, $accepted_args = 1);
function wnd_filter_the_content($content) {

	global $post;
	$price = get_post_meta($post->ID, 'price', true);
	// 价格为空直接返回
	if (!$price) {
		return $content;
	}

	$file = wnd_get_post_meta($post->ID, 'file');

	// 付费下载
	if ($file) {

		// 未登录用户
		if (!is_user_logged_in()) {
			$button_text = '请登录后付费下载： ¥' . wnd_get_post_price($post->ID);
			$form = '<p class="notice">' . $button_text . '</p>';
			$content .= $form;
			return $content;
		}

		if (wnd_user_has_paid($post->ID)) {

			$button_text = '您已购买点击下载';

		} elseif (get_current_user_id() == $post->post_author) {

			$button_text = '您发布的下载文件';

		} else {

			$button_text = '付费下载 ¥' . wnd_get_post_price($post->ID);

		}

		$form =
		'<form id="pay-for-download" action="' . admin_url('admin-ajax.php') . '" method="post" >'
		. '<div class="ajax-msg"></div>'
		. wp_nonce_field('wnd_pay_for_download', '_ajax_nonce') . '
			<input type="hidden" name="action"  value="wnd_action">
			<input type="hidden" name="action_name"  value="wnd_pay_for_download">
			<input type="hidden" name="post_id"  value="' . $post->ID . '">
			<input type="hidden" name="post_title"  value="' . $post->post_title . '">
			<button type="submit" class="button is-danger">' . $button_text . '</button>
			</form>';

		$content .= $form;

		//付费阅读
	} else {

		$content_array = explode('<p><!--more--></p>', $post->post_content);
		if (!isset($content_array[1])) {
			$content_array = explode('<!--more-->', $post->post_content);
		}

		// 已支付
		if (wnd_user_has_paid($post->ID)) {

			$content = '<div id="free-content">' . $content_array[0] . '</div>';
			$content .= '<div id="paid-content">' . $content_array[1] . '</div>';
			$button_text = '您已付费';

			// 作者本人
		} elseif ($post->post_author == get_current_user_id()) {

			$content = '<div id="free-content">' . $content_array[0] . '</div>';
			$content .= '<div id="paid-content">' . $content_array[1] . '</div>';
			$button_text = '您的付费文章';

			// 已登录未支付
		} elseif (is_user_logged_in()) {

			$content = '<div id="free-content">' . $content_array[0] . '</div>';
			$content .= '<div id="paid-content"><p class="notice">以下为付费内容</p></div>';
			$button_text = '付费阅读： ¥' . wnd_get_post_price($post->ID);

			// 未登录用户
		} else {
			$content = '<div id="free-content">' . $content_array[0] . '</div>';
			$content .= '<div id="paid-content"><p class="notice">以下为付费内容</p></div>';
			$button_text = '请登录后付费阅读： ¥' . wnd_get_post_price($post->ID);
			// $form = '<p class="notice">'.$button_text.'</p>';
			// $content = $form;
			// return $content;
		}

		$form =
		'<form id="my-insert-payment" action="" method="post" onsubmit="return false">'
		. wp_nonce_field('wnd_pay_for_reading', '_ajax_nonce') . '
		<input type="hidden" name="action"  value="wnd_action">
		<input type="hidden" name="action_name"  value="wnd_pay_for_reading">
		<input type="hidden" name="post_id"  value="' . $post->ID . '">
		<input type="hidden" name="post_title"  value="' . $post->post_title . '">
		<button type="submit" class="button is-danger" >' . $button_text . '</button>
		</form>';

		$content .= $form;

	}

	return $content;

}

/**
 *@since 2019.01.16
 *注册用户的评论链接到作者页面
 */
add_filter('get_comment_author_url', 'wnd_filter_comment_author_url', 1, 3);
function wnd_filter_comment_author_url($url, $id, $comment) {
	if ($comment->user_id) {
		return get_author_posts_url($comment->user_id);
	}
	return $url;
}

/**
 *@since 初始化
 * 调用用户字段 avatar存储的图像id，或者avatar_url存储的图像地址做自定义头像，并添加用户主页链接
 */
add_filter('get_avatar', 'wnd_filter_avatar', 1, 5);
function wnd_filter_avatar($avatar, $id_or_email, $size, $default, $alt) {

	// 后台，不添加链接
	if (is_admin()) {
		return $avatar;
	}

	// 默认头像
	$avatar_url = wnd_get_option('wndwp', 'wnd_default_avatar') ?: WNDWP_URL . '/static/images/avatar.jpg';

	// 获取用户 ID
	if (is_numeric($id_or_email)) {
		$user_id = (int) $id_or_email;
		//评论获取
	} elseif (is_object($id_or_email)) {
		$user_id = (int) $id_or_email->user_id ?? 0;
		// 邮箱获取
	} else {
		$user = get_user_by('email', $id_or_email);
		$user_id = $user ? $user->ID : 0;
	}
	$user_id = $user_id ?? 0;

	//已登录用户调用字段头像
	if ($user_id) {

		if (wnd_get_user_meta($user_id, 'avatar')) {
			$avatar_id = wnd_get_user_meta($user_id, 'avatar');
			$avatar_url = wp_get_attachment_url($avatar_id) ?: $avatar_url;
		} elseif (wnd_get_user_meta($user_id, 'avatar_url')) {
			$avatar_url = wnd_get_user_meta($user_id, 'avatar_url') ?: $avatar_url;
		}

	}

	//头像
	$avatar = "<img alt='{$alt}' src='$avatar_url' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";

	//注册用户，添加链接
	if ($user_id) {

		$author_url = get_author_posts_url($user_id);
		$avatar = sprintf(
			'<a href="%s" rel="external nofollow" class="url">%s</a>',
			$author_url,
			$avatar
		);
	}

	return $avatar;
}

/**
 *@since 2019.01.26 前端禁用语言包
 */
if (wnd_get_option('wndwp', 'wnd_disable_locale') == 1) {
	add_filter('locale', 'wnd_filter_locale');
	function wnd_filter_locale($locale) {
		$locale = (is_admin()) ? $locale : 'en_US';
		return $locale;
	}
}