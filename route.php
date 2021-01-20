<?php
/**
 *@since 2019.01.21
 *引入 WordPress 响应外部请求
 *
 *@since 0.9.17
 *整合 Wnd_API Json API 及 Wnd_Router 路由器
 *
 */
require '../../../wp-load.php';

use Wnd\Controller\Wnd_API;
use Wnd\Controller\Wnd_Router;

$charset = get_option('blog_charset');
$request = ('POST' == $_SERVER['REQUEST_METHOD']) ? $_POST : $_GET;

// Wnd Router Endpoint
$endpoint = $_GET['endpoint'] ?? '';

/**
 * Rest API Action
 * - 仅允许 POST
 */
$action       = $_POST['action'] ?? '';
$module       = $request['module'] ?? '';
$jsonget      = $request['jsonget'] ?? '';
$filter_posts = $request['filter_posts'] ?? '';
$filter_users = $request['filter_users'] ?? '';

// 应急操作
$wp_action = $request['wp_action'] ?? '';

// Endpoint
if ($endpoint) {
	Wnd_Router::handle_endpoint($endpoint);
	return;
}

// Module
if ($module) {
	$response = Wnd_API::handle_module($request);

	// 返回
	if (!isset($request['render'])) {
		wp_send_json($response);
	}

	// 渲染
	$html = ($response['status'] > 0) ? $response['data'] : $response['msg'];
	echo '<!DOCTYPE html><head>' . wp_head() . '</head><body>' . $html . '</body>';
	return;
}

// Action
if ($action) {
	wp_send_json(Wnd_API::handle_action($request));
}

// JsonGet
if ($jsonget) {
	wp_send_json(Wnd_API::handle_jsonget($request));
}

// JsonGet
if ($filter_posts) {
	wp_send_json(Wnd_API::filter_posts($request));
}

// JsonGet
if ($filter_users) {
	wp_send_json(Wnd_API::filter_users($request));
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
