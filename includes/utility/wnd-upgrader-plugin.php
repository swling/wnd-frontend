<?php
namespace Wnd\Utility;

use Exception;

/**
 * 自定义插件升级API
 * 远程API响应Json举例：
 * 	{
 * 		"version" => "1.1",
 * 		"download_url" : "https://rudrastyh.com/misha-plugin.zip",
 * 		"requires" : "3.0",
 * 		"tested" : "4.8.1",
 * 		"requires_php" : "5.3",
 * 		"last_updated" : "2017-08-17 02:10:00",
 * 		"sections" : {
 * 			"description" : "This is the plugin to test your updater script",
 * 			"installation" : "Upload the plugin to your blog, Activate it, that's it!",
 * 			"changelog" : "<h4>1.1 –  January 17, 2020</h4><ul><li>Some bugs are fixed.</li><li>Release date.</li></ul>"
 * 		},
 * 		"banners" : {
 * 			"low" : "https://YOUR_WEBSITE/banner-772x250.jpg",
 * 			"high" : "https://YOUR_WEBSITE/banner-1544x500.jpg"
 * 		},
 * 		"screenshots" : "<ol><li><a href='IMG_URL' target='_blank'><img src='IMG_URL' alt='CAPTION' /></a><p>CAPTION</p></li></ol>"
 * 	}
 * @since 2020.07.06
 */
abstract class Wnd_Upgrader_Plugin extends Wnd_Upgrader {

	// 瞬态类型，主题或插件：pre_set_site_transient_update_plugins / pre_set_site_transient_update_themes
	protected $update_transient_name = 'pre_set_site_transient_update_plugins';

	// 插件的入口文件，实际插件项目中子类必须定义此属性
	protected $plugin_file_or_theme_slug;

	/**
	 * 获取本地主题或插件的基本信息，需要完成对如下信息的构造
	 *
	 * 	$this->directory_name
	 * 	$this->local_version
	 * 	$this->plugin_file_or_theme_slug
	 * 	$this->api_transient_name
	 */
	protected function get_local_info() {
		// only for test
		// set_site_transient('update_plugins', null);

		if (!$this->plugin_file_or_theme_slug) {
			throw new Exception(__('未定义插件入口文件', 'wnd'));
		}

		// 读取本地插件信息
		if (!function_exists('get_plugin_data')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $this->plugin_file_or_theme_slug);

		$this->local_version      = $plugin_data['Version'];
		$this->directory_name     = current(explode('/', $this->plugin_file_or_theme_slug));
		$this->api_transient_name = 'wnd_upgrade_plugin_' . $this->directory_name;
	}

	/**
	 * 获取更新包详细信息，至少需要完成如下下信息构造：
	 *
	 * 	$this->upgrade_info['url'];
	 * 	$this->upgrade_info['package'];
	 * 	$this->upgrade_info['new_version'];
	 */
	abstract protected function get_remote_info();
}
