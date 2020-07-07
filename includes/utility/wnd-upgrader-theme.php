<?php

namespace Wnd\Utility;

use Wnd\Utility\Wnd_Upgrader;

/**
 *@since 2020.04.13
 *自定义主题升级API
 */
abstract class Wnd_Upgrader_Theme extends Wnd_Upgrader {

	// 瞬态类型，主题或插件：pre_set_site_transient_update_plugins / pre_set_site_transient_update_themes
	protected $update_transient_name = 'pre_set_site_transient_update_themes';

	/**
	 *获取本地主题或插件的基本信息，需要完成对如下信息的构造
	 *
	 *	$this->directory_name
	 *	$this->local_version
	 *	$this->plugin_file_or_theme_slug
	 *	$this->api_transient_name
	 */
	protected function get_local_info() {
		// only for test
		// set_site_transient('update_themes', null);

		$this->directory_name            = basename(get_template_directory());
		$this->local_version             = (wp_get_theme($this->directory_name))->get('Version');
		$this->plugin_file_or_theme_slug = $this->directory_name;
		$this->api_transient_name        = 'wnd_upgrade_theme_' . $this->directory_name;
	}

	/**
	 *获取更新包详细信息，至少需要完成如下下信息构造：
	 *
	 *	$this->upgrade_info['url'];
	 *	$this->upgrade_info['package'];
	 *	$this->upgrade_info['new_version'];
	 */
	abstract protected function get_remote_info();
}
