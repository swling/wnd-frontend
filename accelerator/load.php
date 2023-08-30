<?php
/**
 * @since 0.9.60.2
 * 精简版版本匹配
 *
 * wp 内核每次更新都可能引起 wp-settings.php 改变。因此当版本更新后默认移除精简模式，直到核查无误后，更新本文件版本号
 *
 */

require ABSPATH . 'wp-includes/version.php';
global $wp_version;

// 升级或安装中
$installing = defined('WP_INSTALLING') && WP_INSTALLING;

if ('6.3.1' == $wp_version and !$installing) {
	require __DIR__ . '/wp-settings.php';
} else {
	require ABSPATH . 'wp-settings.php';
}
