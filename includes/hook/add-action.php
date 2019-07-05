<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *通过WordPress action 添加一些额外内容
 *@since 2018.09.07
 */

/*######################################################################### 1、以下为WndWP action*/
/**
 *ajax上传文件时，根据 meta_key 做后续处理
 *@since 2018
 */
add_action('wnd_upload_file', 'wnd_action_upload_file', 10, 3);
function wnd_action_upload_file($attachment_id, $post_parent, $meta_key) {

	if (!$meta_key) {
		return;
	}

	//WordPress原生缩略图
	if ($meta_key == '_wpthumbnail_id') {
		set_post_thumbnail($post_parent, $attachment_id);

		// 储存在文章字段
	} elseif ($post_parent) {
		$old_meta = wnd_get_post_meta($post_parent, $meta_key);
		if ($old_meta) {
			wp_delete_attachment($old_meta);
		}
		wnd_update_post_meta($post_parent, $meta_key, $attachment_id);

		//储存在用户字段
	} else {
		$user_id = get_current_user_id();
		$old_user_meta = wnd_get_user_meta($user_id, $meta_key);
		if ($old_user_meta) {
			wp_delete_attachment($old_user_meta);
		}
		wnd_update_user_meta($user_id, $meta_key, $attachment_id);
	}

}

/**
 *@since 2019.05.05 相册
 *do_action('wnd_upload_gallery', $return_array, $post_parent);
 **/
add_action('wnd_upload_gallery', 'wnd_action_upload_gallery', 10, 2);
function wnd_action_upload_gallery($image_array, $post_parent) {

	if (empty($image_array)) {
		return;
	}

	$images = array();
	foreach ($image_array as $image_info) {

		// 上传失败的图片跳出
		if ($image_info['status'] === 0) {
			continue;
		}

		// 将 img+附件id 作为键名（整型直接做数组键名会存在有效范围，超过整型范围后会出现负数，0等错乱）
		$images['img' . $image_info['data']['id']] = $image_info['data']['id'];
	}
	unset($image_array, $image_info);

	$old_images = wnd_get_post_meta($post_parent, 'gallery');
	$old_images = is_array($old_images) ? $old_images : array();

	// 合并数组，注意新旧数据顺序 array_merge($images, $old_images) 表示将旧数据合并到新数据，因而新上传的在顶部，反之在尾部
	$new_images = array_merge($images, $old_images);

	wnd_update_post_meta($post_parent, 'gallery', $new_images);

}

/**
 * ajax删除附件时
 *@since 2018
 */
add_action('wnd_delete_file', 'wnd_action_delete_file', 10, 3);
function wnd_action_delete_file($attach_id, $post_parent, $meta_key) {

	if (!$meta_key) {
		return;
	}

	/**
	 *@since 2019.05.06 相册编辑
	 */
	if ($meta_key == 'gallery' and $post_parent) {

		// 从相册数组中删除当前图片
		$images = wnd_get_post_meta($post_parent, 'gallery');
		$images = is_array($images) ? $images : array();
		unset($images['img' . $attach_id]);

		wnd_update_post_meta($post_parent, 'gallery', $images);

		return;
	}

	// 删除文章字段
	if ($post_parent) {
		wnd_delete_post_meta($post_parent, $meta_key);
		//删除用户字段
	} else {
		wnd_delete_user_meta(get_current_user_id(), $meta_key);
	}

}

/**
 *do action
 *在没有任何html输出的WordPress环境中执行的相关操作
 *@since 2018.9.25
 */
add_action('wnd_do_action', 'wnd_action_do_action', 10, 1);
function wnd_action_do_action() {

	//1.0 支付宝异步校验 支付宝发起post请求 匿名
	if (isset($_POST['app_id']) && $_POST['app_id'] == wnd_get_option('wnd', 'wnd_alipay_appid')) {
		// WordPress 始终开启了魔法引号，因此需要对post 数据做还原处理
		$_POST = stripslashes_deep($_POST);
		require WND_PATH . 'components/alipay/url-notify.php';
		return;
	}

	//1.1 支付宝支付跳转返回
	if (isset($_GET['app_id']) && $_GET['app_id'] == wnd_get_option('wnd', 'wnd_alipay_appid')) {
		// WordPress 始终开启了魔法引号，因此需要对post 数据做还原处理
		$_GET = stripslashes_deep($_GET);
		require WND_PATH . 'components/alipay/url-return.php';
		return;
	}

	//2.0其他自定义action
	$action = $_GET['action'] ?? '';
	switch ($action) {

	//创建支付
	case 'payment':
		if (is_user_logged_in()) {
			if (wnd_verify_nonce($_REQUEST['_wpnonce'], 'payment')) {
				require WND_PATH . 'components/alipay/pay.php';
			}
		} else {
			wp_die('请登录！', bloginfo('name'));
		}
		break;

	//@since 2019.03.04 刷新所有缓存（主要用于刷新对象缓存，静态缓存通常通过缓存插件本身删除）
	case 'wp_cache_flush':
		if (is_super_admin()) {
			wp_cache_flush();
		}
		break;

	//@since 2019.05.12 默认：校验nonce后执行action对应函数
	default:
		if (wnd_verify_nonce($_REQUEST['_wpnonce'] ?? '', $action)) {
			return $action();
		} else {
			exit;
		}
		break;

	}

}

/*#########################################################################2、以下为WordPress原生 action*/
/**
 *@since 初始化 用户注册后
 */
add_action('user_register', 'wnd_action_user_register', 10, 1);
function wnd_action_user_register($user_id) {

	// 注册类，将注册用户id写入对应数据表
	$email_or_phone = $_POST['phone'] ?? $_POST['_user_user_email'];
	wnd_reset_code($email_or_phone, $user_id);

	// 手机注册，写入用户meta
	if (isset($_POST['phone'])) {
		wnd_update_user_meta($user_id, 'phone', $_POST['phone']);
	}
}

/**
 *删除用户的附加操作
 *@since 2018
 */
add_action('deleted_user', 'wnd_action_delete_user', 10, 1);
function wnd_action_delete_user($user_id) {

	// 删除手机注册记录
	global $wpdb;
	$wpdb->delete($wpdb->wnd_users, array('user_id' => $user_id));

}

/**
 *@since 2019.03.28
 *删除文章时附件操作
 */
add_action('deleted_post', 'wnd_action_deleted_post', 10, 1);
function wnd_action_deleted_post($post_id) {

	$delete_post = get_post($post_id);

	/**
	 *删除附属文件
	 */
	$args = array(
		'posts_per_page' => -1,
		'post_type' => get_post_types(), //此处需要删除所有子文章，如果设置为 any，自定义类型中设置public为false的仍然无法包含，故获取全部注册类型
		'post_status' => 'any',
		'post_parent' => $post_id,
	);

	// 获取并删除
	foreach (get_posts($args) as $child) {
		wp_delete_post($child->ID, true);
	}
	unset($child);

	/**
	 *@since 2019.06.04 删除订单时，扣除订单统计字段
	 *@since 2019.07.03 删除订单时，删除user_has_paid缓存
	 */
	if ($delete_post->post_type == 'order') {
		wnd_inc_wnd_post_meta($delete_post->post_parent, 'order_count', -1, true);
		wp_cache_delete($delete_post->post_author . $delete_post->post_parent, 'user_has_paid');
	}

}

/**
 *@since 2019.06.05
 *文章更新
 */
add_action('post_updated', 'wnd_action_post_updated', 10, 3);
function wnd_action_post_updated($post_ID, $post_after, $post_before) {

	/**
	 * @since 2019.06.05 邮件状态改变时删除邮件查询对象缓存
	 */
	if ($post_after->post_type == 'mail') {
		wp_cache_delete($post_after->post_author, 'wnd_mail_count');
	}

}

/**
 * 禁止WordPress原生登录和注册
 *@since 2019.03.01
 */
if (wnd_get_option('wnd', 'wnd_disable_admin_panel') == 1) {

	// 禁止非管理员登录后台
	add_action('admin_init', 'wnd_action_redirect_non_admin_users');
	function wnd_action_redirect_non_admin_users() {
		if (!is_super_admin() and false === strpos($_SERVER['PHP_SELF'], 'admin-ajax.php')) {
			wp_redirect(home_url('?from=wp-admin'));
			exit;
		}
	}

	// 移除原生登录注册
	add_action('login_head', 'wnd_action_redirect_login_form_register');
	function wnd_action_redirect_login_form_register() {
		wp_redirect(home_url('?from=wp-admin'));
		exit(); // always call `exit()` after `wp_redirect`
	}

}

/**
 *@since 2019.04.16
 *访问后台时候，触发执行清理动作
 */
add_action('admin_init', 'wnd_clean_up');
