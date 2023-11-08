<?php
/**
 * @since 0.9.60.2
 * 精简版版本匹配
 *
 * wp 内核每次更新都可能引起 wp-settings.php 改变。因此当版本更新后默认移除精简模式，直到核查无误后，更新本文件版本号
 *
 * @since 0.9.66
 * 忽略安全版本更新 ($wp_version / 10)
 *
 * 版本号规则
 * @see https://make.wordpress.org/core/handbook/about/release-cycle/version-numbering/
 *
 */

/**
 * 自动加载 wp-includes 根目录下的 class-wp-* 和 class-walker-* 类文件
 * @since 0.9.67
 *
 */
spl_autoload_register(function ($class) {
	$class = strtolower($class);
	if (!str_contains($class, 'wp_') and !str_contains($class, 'walker_')) {
		return;
	}

	$filename = 'class-' . str_replace('_', '-', $class) . '.php';
	$file     = ABSPATH . WPINC . DIRECTORY_SEPARATOR . $filename;
	if (file_exists($file)) {
		require $file;
	}
});

require ABSPATH . 'wp-includes/version.php';
global $wp_version;

// 升级或安装中
$installing = defined('WP_INSTALLING') && WP_INSTALLING;

if (6.4 == floatval($wp_version) and !$installing) {
	require __DIR__ . '/wp-settings.php';
} else {
	require ABSPATH . 'wp-settings.php';
}
