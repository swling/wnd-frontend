<?php
namespace Wnd\Utility;

use Wnd\Utility\Wnd_Singleton_Trait;

/**
 * @since 0.9.29 静态资源 CDN
 */
class Wnd_CDN {

	use Wnd_Singleton_Trait;

	protected $blog_url     = null;
	protected $cdn_url      = null;
	protected $include_dirs = null;
	protected $excludes     = [];
	protected $rootrelative = false;

	private function __construct() {
		$this->blog_url     = get_option('siteurl');
		$this->cdn_url      = wnd_get_config('cdn_url');
		$this->include_dirs = wnd_get_config('cdn_dirs') ?: 'wp-content,wp-includes';

		$excludes       = wnd_get_config('cdn_excludes') ?: '.php';
		$this->excludes = array_map('trim', explode(',', $excludes));

		add_action('after_setup_theme', [$this, 'register_as_output_buffer']);
	}

	public function register_as_output_buffer() {
		/**
		 * @since 0.9.59.7
		 * 允许主题或其他插件对 cdn url 重写（通常适用于多区域多语言站点）
		 */
		$this->cdn_url = apply_filters('wnd_cdn_url', $this->cdn_url);

		if (!is_admin() and $this->blog_url != $this->cdn_url) {
			ob_start([$this, 'rewrite']);
		}
	}

	protected function exclude_single($match) {
		foreach ($this->excludes as $badword) {
			if (stristr($match, $badword) != false) {
				return true;
			}
		}
		return false;
	}

	protected function rewrite_single($match) {
		if ($this->exclude_single($match[0])) {
			return $match[0];
		} else {
			if (!$this->rootrelative || strstr($match[0], $this->blog_url)) {
				return str_replace($this->blog_url, $this->cdn_url, $match[0]);
			} else {
				return $this->cdn_url . $match[0];
			}
		}
	}

	protected function include_dirs_to_pattern() {
		$input = explode(',', $this->include_dirs);
		if ($this->include_dirs == '' || count($input) < 1) {
			return 'wp\-content|wp\-includes';
		} else {
			return implode('|', array_map('quotemeta', array_map('trim', $input)));
		}
	}

	protected function rewrite($content) {
		$dirs  = $this->include_dirs_to_pattern();
		$regex = '#(?<=[(\"\'])';
		$regex .= $this->rootrelative
		? ('(?:' . quotemeta($this->blog_url) . ')?')
		: quotemeta($this->blog_url);
		$regex .= '/(?:((?:' . $dirs . ')[^\"\')]+)|([^/\"\']+\.[^/\"\')]+))(?=[\"\')])#';
		return preg_replace_callback($regex, [$this, 'rewrite_single'], $content);
	}
}
