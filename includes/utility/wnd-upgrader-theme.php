<?php

namespace Wnd\Utility;

use Wnd\Utility\Wnd_Upgrader;

/**
 *@since 2020.04.13
 *自定义主题升级API
 */
abstract class Wnd_Upgrader_Theme extends Wnd_Upgrader {

	// 瞬态类型，主题或插件：pre_set_site_transient_update_plugins / pre_set_site_transient_update_themes
	protected $update_transient = 'pre_set_site_transient_update_themes';

	/**
	 * Method called from the init hook to initiate the updater
	 */
	public function __construct() {
		// only for test
		// set_site_transient('update_themes', null);

		$this->directory_name            = basename(get_template_directory());
		$this->local_version             = (wp_get_theme($this->directory_name))->get('Version');
		$this->plugin_file_or_theme_slug = $this->directory_name;

		parent::__construct();
	}

	/**
	 *获取更新包详细信息，需要完成如下下信息构造：
	 *
	 *	远程版本号
	 *	$this->remote_version;
	 *
	 *	$this->upgrade_info['url']';
	 *	$this->upgrade_info['package'];
	 *	$this->upgrade_info['theme'];
	 *	$this->upgrade_info['new_version'];
	 */
	abstract protected function get_upgrade_info();
}
