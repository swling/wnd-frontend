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
	 *获取更新包详细信息：集成子类需要在本方法中，完成如下信息完成构建
	 *
	 *	$this->remote_version;
	 *
	 *	$this->upgrade_info['url'];
	 *	$this->upgrade_info['package'];
	 *	$this->upgrade_info['slug'];
	 *	$this->upgrade_info['plugin'];
	 *	$this->upgrade_info['new_version'];
	 */
	protected function get_upgrade_info() {
		// $url      = 'https://api.github.com/repos/swling/wnd-frontend/releases';
		// $response = wp_remote_get($url, ['headers' => ['Authorization' => 'token 2211ea551d8dc314ce715bae0b3ee5cd1de8d6b7']]);
		// if (is_wp_error($response)) {
		// 	return $response;
		// }

		// $response = json_decode($response['body'], true);
		// if (is_array($response)) {
		// 	$response = current($response);
		// }

		$response['tag_name']    = '0.01';
		$response['html_url']    = 'http://127.0.0.1/wordpress';
		$response['zipball_url'] = 'http://127.0.0.1/wordpress.zip';

		// 读取GitHub tag name
		$this->remote_version = $response['tag_name'];

		// 构造安装包信息
		$this->upgrade_info['url']         = $response['html_url'];
		$this->upgrade_info['package']     = $response['zipball_url'];
		$this->upgrade_info['slug']        = $this->directory_name;
		$this->upgrade_info['plugin']      = $this->directory_name;
		$this->upgrade_info['new_version'] = $this->remote_version;
	}
}
