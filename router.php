<?php
/**
 *@since 2019.01.21
 *引入 WordPress 响应外部请求
 *
 *@since 0.9.17
 *整合 Wnd_Controller 并提供相关应急操作
 *
 */
require '../../../wp-load.php';

use Wnd\Controller\Wnd_Controller;

$charset = get_option('blog_charset');
$request = ('POST' == $_SERVER['REQUEST_METHOD']) ? $_POST : $_GET;

/**
 * Rest API
 * - Action API 仅允许 POST
 */
$action       = $_POST['action'] ?? '';
$module       = $request['module'] ?? '';
$jsonget      = $request['jsonget'] ?? '';
$endpoint     = $request['endpoint'] ?? '';
$filter_posts = $request['filter_posts'] ?? '';
$filter_users = $request['filter_users'] ?? '';

// 应急操作
$wp_action = $request['wp_action'] ?? '';

// Endpoint
if ($endpoint) {
	Wnd_Controller::handle_endpoint($request);
	return;
}

// Module
if ($module) {
	$response = Wnd_Controller::handle_module($request);

	// 返回
	if (!isset($request['render'])) {
		wp_send_json($response);
	}

	// 渲染
	$html = ($response['status'] > 0 and 'html' == $response['data']['type']) ? $response['data']['structure'] : $response['msg'];
	echo '<!DOCTYPE html><head>' . wp_head() . '</head><body>' . $html . '</body>';
	return;
}

// Action
if ($action) {
	wp_send_json(Wnd_Controller::handle_action($request));
}

// JsonGet
if ($jsonget) {
	wp_send_json(Wnd_Controller::handle_jsonget($request));
}

// filter posts
if ($filter_posts) {
	wp_send_json(Wnd_Controller::filter_posts($request));
}

// filter users
if ($filter_users) {
	wp_send_json(Wnd_Controller::filter_users($request));
}

// WP Action 应急操作
if ($wp_action) {
	//@since 2019.03.04 刷新所有缓存（主要用于刷新对象缓存，静态缓存通常通过缓存插件本身删除）
	if ('wp_cache_flush' == $wp_action and is_super_admin()) {
		return wp_cache_flush();
	}

	/**
	 *@since 0.8.66 清理失败的 WP 更新锁定
	 */
	if ('core_updater.lock' == $wp_action and is_super_admin()) {
		return delete_option('core_updater.lock');
	}

	/**
	 *@since 0.9.0
	 *刷新固定连接缓存
	 */
	if ('flush_rules' == $wp_action) {
		global $wp_rewrite;
		$wp_rewrite->flush_rules(false);
		return true;
	}
}
