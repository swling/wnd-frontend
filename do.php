<?php
/**
 *@since 2019.01.21
 *引入WordPress响应外部请求
 *
 *@since 0.9.17
 *废弃原有 Action Hook，主要用于网站出现故障时应急处理，简化功能如下：
 * - 执行应急操作
 * - nonce 校验执行 Action
 * - 渲染 Module
 *
 *将其他接口交互功能交付 Rest API：wp-json/wnd/route/{$endpoint} 处理
 */
require '../../../wp-load.php';

use Wnd\Controller\Wnd_API;

$action = $_GET['action'] ?? '';
$module = $_GET['module'] ?? '';
$api    = $_GET['api'] ?? '';
$nonce  = $_GET['_wpnonce'] ?? '';

// Action
if ($action) {
	//@since 2019.03.04 刷新所有缓存（主要用于刷新对象缓存，静态缓存通常通过缓存插件本身删除）
	if ('wp_cache_flush' == $action and is_super_admin()) {
		return wp_cache_flush();
	}

	/**
	 *@since 0.8.66 清理失败的 WP 更新锁定
	 */
	if ('core_updater.lock' == $action and is_super_admin()) {
		return delete_option('core_updater.lock');
	}

	/**
	 *@since 0.9.0
	 *刷新固定连接缓存
	 */
	if ('flush_rules' == $action) {
		global $wp_rewrite;
		$wp_rewrite->flush_rules(false);
		return true;
	}

	//@since 2019.05.12 默认：校验nonce后执行action对应的控制类
	if (!$nonce or !wp_verify_nonce($nonce, $action)) {
		exit(__('Nonce 校验失败', 'wnd'));
	}

	$class = Wnd_API::parse_class($action, 'Action');
	if (!is_callable([$class, 'execute'])) {
		exit(__('未定义的 Action ', 'wnd') . $class);
	}

	$action = new $class();
	return $action->execute();
}

// Module
if ($module) {
	$class = Wnd_API::parse_class($module, 'Module');
	if (!is_callable([$class, 'render'])) {
		exit(__('未定义的 Module ', 'wnd') . $class);
	}

	if ($_GET['echo'] ?? false) {
		echo '<!DOCTYPE html>';
		echo '<head>';
		wp_head();
		echo '</head>';
		echo '<body>' . $class::render() . '</body>';
		return '';
	} else {
		return $class::render();
	}
}
