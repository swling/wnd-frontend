<?php

namespace Wnd\Utility;

/**
 *@since 2020.07.06
 *自定义插件升级
 */
class Wnd_Upgrader_Plugin_This extends Wnd_Upgrader_Plugin {

	// 插件入口文件：WP将以此作为插件识别表示
	protected $plugin_file_or_theme_slug = 'wnd-frontend/wnd-frontend.php';

	/**
	 *获取更新包详细信息，至少需要完成如下下信息构造：
	 *
	 *	$this->upgrade_info['url'];
	 *	$this->upgrade_info['package'];
	 *	$this->upgrade_info['new_version'];
	 */
	protected function get_remote_info() {
		$url      = 'https://wndwp.com/wp-json/wndt/project/157';
		$response = wp_remote_get($url, ['headers' => ['Authorization' => 'token xxx']]);
		if (is_wp_error($response)) {
			return $response;
		}
		$response = json_decode($response['body'], true);

		if ($response['status'] > 0) {
			$this->upgrade_info = array_merge($this->upgrade_info, $response['data']);
		}
	}
}
