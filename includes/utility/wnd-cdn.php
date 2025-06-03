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
	protected $domain_path  = null; // 新增属性：无协议和端口后缀的域名路径

	private function __construct() {
		$config = static::get_default_config();

		$this->blog_url     = get_option('siteurl');
		$this->cdn_url      = $config['cdn_url'];
		$this->include_dirs = $config['cdn_dirs'];
		$this->excludes     = $config['cdn_excludes'];
		$this->domain_path  = preg_replace('#^https?://#', '', $this->blog_url);

		add_action('after_setup_theme', [$this, 'register_as_output_buffer']);
	}

	private static function get_default_config() {
		return [
			'cdn_url'      => wnd_get_config('cdn_url'),
			'cdn_dirs'     => wnd_get_config('cdn_dirs') ?: 'wp-content,wp-includes',
			'cdn_excludes' => array_map('trim', explode(',', wnd_get_config('cdn_excludes') ?: '.php')),
		];
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

	protected function rewrite($content) {
		// 构建正则表达式
		$dirs     = $this->include_dirs_to_pattern();
		$protocol = '(?:https?:)?'; // 匹配协议或无协议
		$domain   = '\/\/' . quotemeta($this->domain_path); // 匹配域名路径
		$path     = '/(?:((?:' . $dirs . ')[^\"\')]+)|([^/\"\']+\.[^/\"\')]+))'; // 匹配资源路径
		$regex    = '#(?<=[(\"\'])' . ($this->rootrelative ? '(?:' . quotemeta($this->blog_url) . ')?' : $protocol . $domain) . $path . '(?=[\"\')])#';

		return preg_replace_callback($regex, [$this, 'rewrite_single'], $content);
	}

	protected function include_dirs_to_pattern() {
		if (empty($this->include_dirs)) {
			return 'wp\-content|wp\-includes';
		}

		$input = array_filter(array_map('trim', explode(',', $this->include_dirs)));
		return $input ? implode('|', array_map('quotemeta', $input)) : 'wp\-content|wp\-includes';
	}

	protected function rewrite_single($match) {
		if ($this->exclude_single($match[0])) {
			return $match[0];
		}

		$path = $this->extract_path($match[0]);
		return $path ? rtrim($this->cdn_url, '/') . $path : $match[0];
	}

	protected function exclude_single($match) {
		return !empty(array_filter($this->excludes, function ($badword) use ($match) {
			return stristr($match, $badword) !== false;
		}));
	}

	private function extract_path($url) {
		if (!$this->rootrelative) {
			$pos = strpos($url, $this->domain_path);
			return $pos !== false ? substr($url, $pos + strlen($this->domain_path)) : null;
		}

		return strstr($url, $this->blog_url) ? str_replace($this->blog_url, '', $url) : $url;
	}

}
