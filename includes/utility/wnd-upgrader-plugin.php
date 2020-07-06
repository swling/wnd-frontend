<?php
namespace Wnd\Utility;

use Exception;

/**
 *@since 2020.07.06
 *自定义插件升级API
 */
abstract class Wnd_Upgrader_Plugin extends Wnd_Upgrader {

	// 瞬态类型，主题或插件：pre_set_site_transient_update_plugins / pre_set_site_transient_update_themes
	protected $update_transient = 'pre_set_site_transient_update_plugins';

	/**
	 * Method called from the init hook to initiate the updater
	 */
	public function __construct() {
		// only for test
		// set_site_transient('update_plugins', null);

		if (!$this->plugin_file_or_theme_slug) {
			throw new Exception(__('未定义插件入口文件', 'wnd'));
		}

		if (!function_exists('get_plugin_data')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugin_data          = get_plugin_data(WP_PLUGIN_DIR . '/' . $this->plugin_file_or_theme_slug);
		$this->local_version  = $plugin_data['Version'];
		$this->directory_name = current(explode('/', $this->plugin_file_or_theme_slug));

		parent::__construct();
	}

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
	abstract protected function get_upgrade_info();
}
