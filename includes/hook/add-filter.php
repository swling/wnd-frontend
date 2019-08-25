<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@see wndwp/READEME.md
 * ############################################################################ 以下为WndWP插件过滤钩子
 */

/**
 *@since 2019.01.22
 *检测当前信息是否可以注册新用户
 */
// apply_filters('wnd_can_reg', array('status'=>1,'msg'=>'默认通过'));
add_filter('wnd_can_reg', 'wnd_filter_can_reg', 10, 1);
function wnd_filter_can_reg($can_array) {
	if (!get_option('users_can_register')) {
		return array('status' => 0, 'msg' => '站点已关闭注册！');
	}

	// 验证:手机或邮箱 验证码
	$auth_code = $_POST['auth_code'];
	$email_or_phone = $_POST['phone'] ?? $_POST['_user_user_email'] ?? '';
	try {
		$auth = new Wnd_Auth;
		$auth->set_type('register');
		$auth->set_auth_code($auth_code);
		$auth->set_email_or_phone($email_or_phone);
		$auth->verify();
		return $can_array;
	} catch (Exception $e) {
		return array('status' => 0, 'msg' => $e->getMessage());
	}

}

/**
 * 用户更新账户权限
 *@since 2019.01.22
 */
add_filter('wnd_can_update_account', 'wnd_filter_can_update_account', 10, 1);
function wnd_filter_can_update_account($can_array) {
	$auth_code = $_POST['auth_code'];
	$user = wp_get_current_user();
	$user_id = $user->ID;
	$email_or_phone = $_POST['phone'] ?? $_POST['_user_user_email'] ?? '';

	try {
		$auth = new Wnd_Auth;
		$auth->set_type('verify');
		$auth->set_auth_code($auth_code);
		$auth->set_email_or_phone($email_or_phone);
		$auth->verify();
		return $can_array;
	} catch (Exception $e) {
		return array('status' => 0, 'msg' => $e->getMessage());
	}
}

/**
 *@since 2019.02.13
 *文章状态过滤，允许前端表单设置为草稿状态（执行顺序10，因而会被其他顺序晚于10的filter覆盖）
 *如果 $update_id 为0 表示为新发布文章，否则为更新文章
 */
add_filter('wnd_insert_post_status', 'wnd_filter_post_status', 10, 3);
function wnd_filter_post_status($post_status, $post_type, $update_id) {

	// 允许用户设置为草稿
	if (isset($_POST['_post_post_status']) and $_POST['_post_post_status'] == 'draft') {
		return 'draft';
	}

	// 管理员通过
	if (wnd_is_manager()) {
		return 'publish';
	}

	// 已公开发布过的内容，再次编辑无需审核
	return (get_post_status($update_id) == 'publish') ? 'publish' : $post_status;
}

/**
 * ############################################################################ 以下为WordPress原生filter
 */

/**
 *@since 2019.01.16
 * 限制wp editor上传附件
 */
add_filter('wp_handle_upload_prefilter', 'wnd_filter_limit_upload');
function wnd_filter_limit_upload($file) {

	// 上传体积限制
	$image_size = $file['size'] / 1024;
	$limit = wnd_get_option('wnd', 'wnd_max_upload_size') ?: 2048;

	if ($image_size > $limit) {
		$file['error'] = '上传文件不得超过' . $limit . 'KB';
		return $file;
	}

	// 文件信息
	$info = pathinfo($file['name']);
	$ext = isset($info['extension']) ? '.' . $info['extension'] : null;
	if (!$ext) {
		$file['error'] = '未能获取到文件拓展名';
		return $file;
	}

	// 重命名文件名为随机码：用于美化附件slug，同时实现基本的文件路径加密
	$file['name'] = uniqid('file') . $ext;

	return $file;

}

/**
 *@since 2019.01.31 重写WordPress原生编辑链接到指定的页面
 */
add_filter('get_edit_post_link', 'wnd_filter_edit_post_link', 10, 3);
function wnd_filter_edit_post_link($link, $post_id, $context) {

	if (is_admin()) {
		return $link;
	}

	$edit_page = (int) wnd_get_option('wnd', 'wnd_edit_page');
	if ($edit_page) {
		return get_permalink($edit_page) . '?post_id=' . $post_id;
	}
	return $link;

}

/**
 *@since 2019.04.03
 *apply_filters( 'wp_insert_post_data', $data, $postarr )
 *防止插入相同标题文章时（功能型post），反复查询post name，故此设置为随机值
 */
add_filter('wp_insert_post_data', 'wnd_filter_wp_insert_post_data', 10, 1);
function wnd_filter_wp_insert_post_data($data) {

	if (empty($data['post_name'])) {
		$data['post_name'] = uniqid();
	}

	return $data;
}

/**
 *@since 2019.07.18
 *$data = apply_filters( 'wp_insert_attachment_data', $data, $postarr );
 *自动给上传的附件依次设置 menu_order
 *
 *menu order值为当前附属的post上传附件总次数
 *@see wnd_action_add_attachment
 */
add_filter('wp_insert_attachment_data', 'wnd_filter_wp_insert_attachment_data', 10, 2);
function wnd_filter_wp_insert_attachment_data($data, $postarr) {

	// 如果已经指定了menu order
	if ($data['menu_order']) {
		return $data;
	}

	$menu_order = wnd_get_post_meta($data['post_parent'], 'attachment_records') ?: 0;
	$data['menu_order'] = ++$menu_order;

	return $data;

}

/**
 * 付费内容
 *@since 2018.09.17
 *设置 文章自定义字段 price
 *使用WordPress经典编辑器插入 more标签 或者编辑源码插入 <!--more--> 以区分免费内容和付费内容
 */
add_filter('the_content', 'wnd_filter_the_content', 10, 1);
function wnd_filter_the_content($content) {

	global $post;
	if (!$post) {
		return;
	}

	$price = wnd_get_post_price($post->ID);
	$file = wnd_get_post_meta($post->ID, 'file');

	// 价格为空且没有文件，免费文章
	if (!$price and !$file) {
		return $content;
	}

	$user_id = get_current_user_id();

	// 付费下载
	if ($file) {

		// 未登录用户
		if (!$user_id) {
			$content .= $price ? '<div class="message is-warning"><div class="message-body">付费下载：¥' . $price . '</div></div>' : '';
			$button_text = '请登录后下载';
			$button = '<div class="field is-grouped is-grouped-centered"><button class="button is-warning" onclick="wnd_ajax_modal(\'_wnd_user_center\',\'action=login\')">' . $button_text . '</button></div>';
			$content .= $button;
			return $content;
		}

		if (wnd_user_has_paid($user_id, $post->ID)) {

			$button_text = '您已购买点击下载';

		} elseif (get_current_user_id() == $post->post_author) {

			$button_text = '您发布的下载文件';

		} elseif ($price > 0) {

			$button_text = '付费下载 ¥' . $price;

		} else {
			$button_text = '免费下载';
		}

		$form = new Wnd_WP_Form;
		$form->add_hidden('post_id', $post->ID);
		$form->set_action('wnd_ajax_pay_for_download');
		$form->set_submit_button($button_text);
		$form->build();

		$content .= $form->html;

		//付费阅读
	} else {

		//查找是否有more标签，否则免费部分为空（全文付费）
		$content_array = explode('<!--more-->', $post->post_content, 2);
		if (count($content_array) == 1) {
			$content_array = array('', $post->post_content);
		}
		list($free_content, $paid_content) = $content_array;

		// 未登录用户
		if (!$user_id) {
			$content = '<div class="free-content">' . $free_content . '</div>';
			$content .= '<div class="paid-content"><div class="message is-warning"><div class="message-body">付费内容：¥' . $price . '</div></div></div>';
			$button = '<div class="field is-grouped is-grouped-centered"><button class="button is-warning" onclick="wnd_ajax_modal(\'_wnd_user_center\',\'action=login\')">请登录</button></div>';
			$content .= $button;
			return $content;
		}

		// 已支付
		if (wnd_user_has_paid($user_id, $post->ID)) {

			$content = '<div class="free-content">' . $free_content . '</div>';
			$content .= '<div class="paid-content">' . $paid_content . '</div>';
			$button_text = '您已付费';

			// 作者本人
		} elseif ($post->post_author == get_current_user_id()) {

			$content = '<div class="free-content">' . $free_content . '</div>';
			$content .= '<div class="paid-content">' . $paid_content . '</div>';
			$button_text = '您的付费文章';

			// 已登录未支付
		} else {

			$content = '<div class="free-content">' . $free_content . '</div>';
			$content .= '<div class="paid-content"><p class="ajax-message">以下为付费内容</p></div>';
			$button_text = '付费阅读： ¥' . wnd_get_post_price($post->ID);

		}

		$form = new Wnd_WP_Form;
		$form->add_hidden('post_id', $post->ID);
		$form->set_action('wnd_ajax_pay_for_reading');
		$form->set_submit_button($button_text);
		$form->build();

		$content .= $form->html;

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

	// 默认头像
	$avatar_url = wnd_get_option('wnd', 'wnd_default_avatar_url') ?: WND_URL . 'static/images/avatar.jpg';

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
			/**
			 *@since 2019.07.23
			 * 统一按阿里云oss裁剪缩略图
			 */
			$avatar_url = wnd_get_thumbnail_url($avatar_url, $size, $size);

		} elseif (wnd_get_user_meta($user_id, 'avatar_url')) {
			$avatar_url = wnd_get_user_meta($user_id, 'avatar_url') ?: $avatar_url;

		}

	}

	//头像
	$avatar = "<img alt='{$alt}' src='$avatar_url' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";

	//注册用户，添加链接
	if ($user_id and !is_admin()) {
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
if (wnd_get_option('wnd', 'wnd_disable_locale') == 1) {
	add_filter('locale', 'wnd_filter_locale');
	function wnd_filter_locale($locale) {
		$locale = (is_admin()) ? $locale : 'en_US';
		return $locale;
	}
}

/**
 *@since 2019.02.14 当仅允许用户在前端操作时，可注销一些字段，降低wp_usermeta数据库开销
 *@link https://developer.wordpress.org/reference/hooks/insert_user_meta/
 */
if (wnd_get_option('wnd', 'wnd_unset_user_meta') == 1) {
	add_filter('insert_user_meta', 'wnd_filter_unset_user_meta', 10, 2);
	function wnd_filter_unset_user_meta($meta, $user) {
		// 排除超级管理员
		if (is_super_admin($user->ID)) {
			return $meta;
		}

		unset($meta['nickname']);
		unset($meta['first_name']);
		unset($meta['last_name']);
		unset($meta['syntax_highlighting']);
		unset($meta['comment_shortcuts']); //评论快捷方式
		unset($meta['admin_color']);
		unset($meta['use_ssl']);
		unset($meta['show_admin_bar_front']);
		unset($meta['locale']);

		return $meta;
	}
}

/**
 * 禁止WordPress admin bar
 *@since 2019.03.01
 */
if (wnd_get_option('wnd', 'wnd_disable_admin_panel') == 1) {

	//禁用前台工具栏
	add_filter('show_admin_bar', '__return_false');
}

/**
 * 修改通知系统邮件发件人名称“WordPress”为博客名称
 *@since 2019.03.28
 */
function wnd_action_mail_from_name($email) {
	return get_option('blogname');
}
add_filter('wp_mail_from_name', 'wnd_action_mail_from_name');

/**
 *@since 2019.1.14 移除错误网址的智能重定向，智能重定向可能会导致百度收录及改版校验等出现问题
 */
add_filter('redirect_canonical', 'wnd_action_no_redirect_404');
function wnd_action_no_redirect_404($redirect_url) {
	if (is_404()) {
		return false;
	}
	return $redirect_url;
}

/**
 *@since 2019.06.13
 *将文章流量统计：views字段缓存在对象缓存中，降低数据库读写
 */
add_filter('update_post_metadata', 'wnd_filter_update_post_metadata', 10, 5);
function wnd_filter_update_post_metadata($check, $object_id, $meta_key, $meta_value, $prev_value) {

	//已经安装了 memcached 插件
	global $wp_object_cache;
	if (!isset($wp_object_cache->mc) or !$wp_object_cache->mc) {
		return $check;
	}

	if ($meta_key == 'views') {
		wp_cache_set($object_id, $meta_value, 'views');
		$cached_post_views = 10;
		if ($meta_value % $cached_post_views == 0 or $meta_value == 1) {
			//每增加 10 次浏览 或首次 写入数据库中去
			return $check;
		} else {
			return true;
		}
	} else {
		return $check;
	}
}

add_filter('get_post_metadata', 'wnd_filter_get_post_metadata', 10, 4);
function wnd_filter_get_post_metadata($check, $object_id, $meta_key, $single) {

	//已经安装了 memcached 插件
	global $wp_object_cache;
	if (!isset($wp_object_cache->mc) or !$wp_object_cache->mc) {
		return $check;
	}

	if ($single and $meta_key == 'views') {
		$views = wp_cache_get($object_id, 'views'); //显示的时候直接从内存中获取
		if ($views === false) {
			return $check;
		} else {
			return $views;
		}
	} else {
		return $check;
	}
}
