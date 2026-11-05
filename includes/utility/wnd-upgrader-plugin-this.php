<?php

namespace Wnd\Utility;

use Exception;

/**
 * 自定义插件升级
 * @since 2020.07.06
 */
class Wnd_Upgrader_Plugin_This extends Wnd_Upgrader_Plugin {

	// 插件入口文件：WP将以此作为插件识别表示
	protected $plugin_file_or_theme_slug = WND_DIR_NAME . '/wnd-frontend.php';

	/**
	 * 获取更新包详细信息，至少需要完成如下下信息构造：
	 *
	 * $this->upgrade_info['url'];
	 * $this->upgrade_info['package'];
	 * $this->upgrade_info['new_version'];
	 */
	protected function get_remote_info() {
		try {
			$url      = 'https://api.github.com/repos/swling/wnd-frontend/releases/latest';
			$response = wnd_remote_get($url);
			$response = json_decode($response['body'], true);

			if (!$response || empty($response['tag_name'])) {
				return;
			}

			$this->upgrade_info['url']         = $response['html_url'];
			$this->upgrade_info['package']     = $response['zipball_url'];
			$this->upgrade_info['new_version'] = $response['tag_name'];
		} catch (Exception $e) {
			$this->upgrade_info['error'] = $e->getMessage();
		}
	}
}
