<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *通过WordPress action 添加一些额外内容
 *@since 2018.09.07
 */

/**
 *ajax上传文件时，根据 meta_key 做后续处理
 *@since 2018
 */
add_action('wnd_upload_file', 'wnd_action_upload_file', $priority = 10, $accepted_args = 3);
function wnd_action_upload_file($attachment_id, $post_parent, $meta_key) {

	if (!$meta_key) {
		return;
	}

	//WordPress原生缩略图
	if ($meta_key == 'wpthumbnail') {
		set_post_thumbnail($post_parent, $attachment_id);
	}
	// 储存在文章字段
	elseif ($post_parent) {
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
 * ajax删除附件时
 *@since 2018
 */
add_action('wnd_delete_attachment', 'wnd_action_delete_attachment', 1, 3);
function wnd_action_delete_attachment($attach_id, $post_parent, $meta_key) {

	if (!$meta_key) {
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
 * 充值、支付
 *@since 2018.9.25
 */
add_action('wnd_do_action', 'wnd_action_pay', $priority = 10, $accepted_args = 1);
function wnd_action_pay() {

	//1.0 支付宝异步校验 支付宝发起post请求 匿名
	if (isset($_POST['app_id']) && $_POST['app_id'] == wnd_get_option('wndwp', 'wnd_alipay_appid')) {
		// WordPress 始终开启了魔法引号，因此需要对post 数据做还原处理
		$_POST = stripslashes_deep($_POST);
		require WNDWP_PATH . 'components/payment/alipay/notify_url.php';
		return;
	}

	//1.1 支付宝支付跳转返回
	if (isset($_GET['app_id']) && $_GET['app_id'] == wnd_get_option('wndwp', 'wnd_alipay_appid')) {
		// WordPress 始终开启了魔法引号，因此需要对post 数据做还原处理
		$_GET = stripslashes_deep($_GET);
		require WNDWP_PATH . 'components/payment/alipay/return_url.php';
		return;
	}

	//2.0其他自定义action
	$action = $_GET['action'] ?? '';
	switch ($action) {

	case 'recharge':
		if (is_user_logged_in()) {
			//充值
			check_admin_referer('wnd_recharge');
			require WNDWP_PATH . 'components/payment/alipay/pagepay/pagepay.php';
		} else {
			wp_die('请登录！', bloginfo('name'));
		}
		break;

	default:
		wp_die('无效的操作！', bloginfo('name'));
		break;

	}

}

/*######################################################################### 以下为WordPress原生 action*/

/**
 *@since 初始化 用户注册后
 *校验完成后，重置验证码数据
 */
add_action('user_register', 'wnd_reset_reg_code');
function wnd_reset_reg_code($user_id) {

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
add_action('delete_user', 'wnd_action_delete_user');
function wnd_action_delete_user($user_id) {

	// 删除手机注册记录
	global $wpdb;
	$wpdb->delete($wpdb->wnd_users, array('user_id' => $user_id));

}