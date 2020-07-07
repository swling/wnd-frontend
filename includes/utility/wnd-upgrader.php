<?php
namespace Wnd\Utility;

use WP_Error;

/**
 *@since 2020.07.06
 *自定义插件或主题更新API
 */
abstract class Wnd_Upgrader {

	// 主题或插件的文件目录名称
	protected $directory_name;

	// 插件入口文件或者主题文件夹，WP将以此作为更新识别
	protected $plugin_file_or_theme_slug;

	// 本地版本
	protected $local_version;

	// 瞬态类型，插件：pre_set_site_transient_update_plugins / 主题：pre_set_site_transient_update_themes
	protected $update_transient_name;

	// API 瞬态缓存 key
	protected $api_transient_name;

	// 更新包信息
	protected $upgrade_info = [
		'slug'        => '', //plugin slug
		'plugin'      => '', //plugin name
		'theme'       => '', //theme name
		'new_version' => '', //版本号
		'url'         => '', //介绍页面URL
		'package'     => '', //下载地址
	];

	/**
	 * Method called from the init hook to initiate the updater
	 */
	public function __construct() {
		// 读取本地主题或插件信息
		$this->get_local_info();
		$this->upgrade_info['slug']   = $this->directory_name;
		$this->upgrade_info['plugin'] = $this->directory_name;
		$this->upgrade_info['theme']  = $this->directory_name;

		add_filter($this->update_transient_name, [$this, 'check_for_update'], 10, 1);
		add_filter('upgrader_source_selection', [$this, 'fix_directory_name'], 10, 4);
	}

	/**
	 *检测更新信息
	 **/
	public function check_for_update($transient): object{
		/**
		 *自定义 API 本身是对WP瞬态的过滤器
		 *故此，单独重新定义一个瞬态缓存当前插件或主题的远程 API 信息
		 */
		$upgrade_info = get_transient($this->api_transient_name);
		if (false == $upgrade_info) {
			$this->get_remote_info();
			set_transient($this->api_transient_name, $this->upgrade_info, 43200); // 12 hours cache
		} else {
			$this->upgrade_info = $upgrade_info;
		}

		// 版本检测
		if (version_compare($this->upgrade_info['new_version'], $this->local_version, '<=')) {
			unset($transient->response[$this->plugin_file_or_theme_slug]);
			return $transient;
		}

		/**
		 *写入更新信息
		 *插件需要返回对象型数据
		 *主题则需返回数组型数据
		 */
		if ('pre_set_site_transient_update_plugins' == $this->update_transient_name) {
			$transient->response[$this->plugin_file_or_theme_slug] = (object) $this->upgrade_info;

		} elseif ('pre_set_site_transient_update_themes' == $this->update_transient_name) {
			$transient->response[$this->plugin_file_or_theme_slug] = (array) $this->upgrade_info;
		}

		return $transient;
	}

	/**
	 *获取本地主题或插件的基本信息，需要完成对如下信息的构造
	 *
	 *	$this->directory_name
	 *	$this->local_version
	 *	$this->plugin_file_or_theme_slug
	 *	$this->api_transient_name
	 */
	abstract protected function get_local_info();

	/**
	 *获取更新包详细信息，至少需要完成如下下信息构造：
	 *
	 *	$this->upgrade_info['url'];
	 *	$this->upgrade_info['package'];
	 *	$this->upgrade_info['new_version'];
	 */
	abstract protected function get_remote_info();

	/* -------------------------------------------------------------------
		 * Fix directory name when installing updates
		 * -------------------------------------------------------------------
	*/

	/**
	 * Rename the update directory to match the existing plugin/theme directory.
	 *
	 * When WordPress installs a plugin or theme update, it assumes that the ZIP file will contain
	 * exactly one directory, and that the directory name will be the same as the directory where
	 * the plugin or theme is currently installed.
	 *
	 * GitHub and other repositories provide ZIP downloads, but they often use directory names like
	 * "project-branch" or "project-tag-hash". We need to change the name to the actual plugin folder.
	 *
	 * This is a hook callback. Don't call it from a plugin.
	 *
	 * @access protected
	 *
	 * @param string $source The directory to copy to /wp-content/plugins or /wp-content/themes. Usually a subdirectory of $remoteSource.
	 * @param string $remoteSource WordPress has extracted the update to this directory.
	 * @param WP_Upgrader $upgrader
	 * @return string|WP_Error
	 */
	public function fix_directory_name($source, $remoteSource, $upgrader) {
		global $wp_filesystem;
		/** @var WP_Filesystem_Base $wp_filesystem */

		//Basic sanity checks.
		if (!isset($source, $remoteSource, $upgrader, $upgrader->skin, $wp_filesystem)) {
			return $source;
		}

		// 仅针对当前插件或主题
		if (false === stristr(basename($source), $this->directory_name)) {
			return $source;
		}

		//Rename the source to match the existing directory.
		$correctedSource = trailingslashit($remoteSource) . $this->directory_name . '/';
		if ($source == $correctedSource) {
			return $source;
		}

		//The update archive should contain a single directory that contains the rest of plugin/theme files.
		//Otherwise, WordPress will try to copy the entire working directory ($source == $remoteSource).
		//We can't rename $remoteSource because that would break WordPress code that cleans up temporary files
		//after update.
		if (static::is_bad_directory_structure($remoteSource)) {
			return new WP_Error(
				'wnd-incorrect-directory-structure',
				sprintf(
					'The directory structure of the update is incorrect. All files should be inside ' .
					'a directory named <span class="code">%s</span>, not at the root of the ZIP archive.',
					htmlentities($this->directory_name)
				)
			);
		}

		/** @var WP_Upgrader_Skin $upgrader ->skin */
		$upgrader->skin->feedback(sprintf(
			'Renaming %s to %s&#8230;',
			'<span class="code">' . basename($source) . '</span>',
			'<span class="code">' . $this->directory_name . '</span>'
		));

		if ($wp_filesystem->move($source, $correctedSource, true)) {
			$upgrader->skin->feedback('Directory successfully renamed.');
			return $correctedSource;
		} else {
			return new WP_Error(
				'wnd-rename-failed',
				'Unable to rename the update to match the existing directory.'
			);
		}

		return $source;
	}

	/**
	 * Check for incorrect update directory structure. An update must contain a single directory,
	 * all other files should be inside that directory.
	 *
	 * @param string $remoteSource Directory path.
	 * @return bool
	 */
	protected static function is_bad_directory_structure($remoteSource): bool {
		global $wp_filesystem;
		/** @var WP_Filesystem_Base $wp_filesystem */

		$sourceFiles = $wp_filesystem->dirlist($remoteSource);
		if (is_array($sourceFiles)) {
			$sourceFiles   = array_keys($sourceFiles);
			$firstFilePath = trailingslashit($remoteSource) . $sourceFiles[0];
			return (count($sourceFiles) > 1) || (!$wp_filesystem->is_dir($firstFilePath));
		}

		//Assume it's fine.
		return false;
	}
}
