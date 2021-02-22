<?php
namespace Wnd\View;

use Wnd\View\Wnd_Pagination;

/**
 * @since 2019.07.30
 * 多重筛选类
 * 样式基于bulma css
 *
 * @param bool 	$independent 	是否为独立 WP Query
 */
class Wnd_Filter extends Wnd_Filter_Abstract {

	public $post_template;

	public $posts_template;

	// 筛选项HTML
	protected $tabs = '';

	// 筛选结果HTML
	protected $posts = '';

	// 分页导航HTML
	protected $pagination = '';

	/**
	 *@since 2019.08.02
	 *设置列表post模板函数，传递$post对象
	 *@param string $template post模板函数名
	 **/
	public function set_post_template($template) {
		$this->post_template = $template;
	}

	/**
	 *@since 2019.08.16
	 *文章列表页整体模板函数，传递wp_query查询结果
	 *设置模板后，$this->get_posts() 即为被该函数返回值
	 *@param string $template posts模板函数名
	 **/
	public function set_posts_template($template) {
		$this->posts_template = $template;
	}

	/**
	 *@since 2020.05.11
	 *搜索框
	 */
	public function add_search_form($button = 'Search', $placeholder = '') {
		$html = '<form class="wnd-filter-search" method="POST" action="" "onsubmit"="return false">';
		$html = '<form class="wnd-filter-search" method="GET" action="">';
		$html .= '<div class="field has-addons">';

		$html .= '<div class="control is-expanded">';
		$html .= '<input class="input" type="text" name="search" placeholder="' . $placeholder . '" required="required">';
		$html .= '</div>';
		$html .= '<div class="control">';
		$html .= '<button type="submit" class="button is-' . wnd_get_config('primary_color') . '">' . $button . '</button>';
		$html .= '</div>';

		$html .= '</div>';
		// 作用：在非ajax状态中，支持在指定post_type下搜索
		$html .= '<input type="hidden" name="type" value="' . ($_GET['type'] ?? '') . '">';
		$html .= '</form>';

		$this->tabs .= $html;
	}

	/**
	 *@since 0.9.25
	 *统一封装 Tabs 输出
	 *
	 */
	protected function build_tabs(string $key, array $options, string $label, bool $any, array $remove_args = []): string {
		if (!$options) {
			return '';
		}

		/**
		 * 定义当前筛选项基准 URL
		 *  - 如果传参为 ['all'] 则移除移除动态参数，但保留语言参数
		 */
		if ('all' == ($remove_args[0] ?? false)) {
			$base_url = strtok($this->wp_base_url, '?');
			$base_url = isset($_GET[WND_LANG_KEY]) ? add_query_arg(WND_LANG_KEY, $_GET[WND_LANG_KEY]) : $base_url;
		} else {
			$base_url = remove_query_arg($remove_args, $this->wp_base_url);
		}

		// 输出容器
		$tabs = '<div class="columns is-marginless is-vcentered ' . $key . '">';
		$tabs .= '<div class="column is-narrow">' . $label . '</div>';
		$tabs .= '<div class="tabs column">';
		$tabs .= '<ul class="tab">';

		// 【全部】选项
		if ($any) {
			$tabs .= '<li class="' . $this->get_tab_item_class($key, '') . '">';
			$tabs .= '<a href="' . remove_query_arg($key, $base_url) . '">' . __('全部', 'wnd') . '</a>';
			$tabs .= '</li>';
		}

		// 输出 Tab 选项
		foreach ($options as $name => $value) {
			$tabs .= '<li class="' . $this->get_tab_item_class($key, $value) . '">';
			$tabs .= '<a href="' . add_query_arg($key, $value, $base_url) . '">' . $name . '</a>';
			$tabs .= '</li>';
		}
		unset($key, $value);

		// 输出结束
		$tabs .= '</ul>';
		$tabs .= '</div>';
		$tabs .= '</div>';

		$this->tabs .= $tabs;

		return $tabs;
	}

	/**
	 *筛选项详情菜单 Class
	 */
	protected function get_tab_item_class($key, $value): string{
		$query_vars = $_GET[$key] ?? '';
		return ($query_vars == $value) ? 'item is-active' : 'item';
	}

	/**
	 *@since 2019.07.31
	 *获取筛选项HTML
	 *
	 *tabs筛选项由于参数繁杂，无法通过api动态生成，因此不包含在api请求响应中
	 *但已生成的相关筛选项会根据wp_query->query_var参数做动态修改
	 *
	 */
	public function get_tabs(): string {
		return '<div class="wnd-filter-tabs">' . $this->tabs . '</div>';
	}

	/**
	 *@since 2019.07.31
	 *获取筛结果HTML
	 */
	public function get_posts(): string {
		if (!$this->wp_query) {
			return __('未执行WP_Query', 'wnd');
		}

		// Posts list
		if ($this->posts_template) {
			$template    = $this->posts_template;
			$this->posts = $template($this->wp_query);
			return $this->posts;
		}

		// post list
		if ($this->post_template) {
			$template = $this->post_template;
			if ($this->wp_query->have_posts()) {
				while ($this->wp_query->have_posts()): $this->wp_query->the_post();
					global $post;
					$this->posts .= $template($post);
				endwhile;
				wp_reset_postdata(); //重置查询
			}

			return $this->posts;
		}

		// 未设置输出模板
		return __('未定义输出模板', 'wnd');
	}

	/**
	 *@since 2019.02.15
	 *分页导航
	 */
	public function get_pagination($show_page = 5): string {
		if (!$this->wp_query) {
			return __('未执行WP_Query', 'wnd');
		}

		$nav = new Wnd_Pagination($this->independent);
		$nav->set_paged($this->wp_query->query_vars['paged'] ?: 1);
		$nav->set_max_num_pages($this->wp_query->max_num_pages);
		$nav->set_items_per_page($this->wp_query->query_vars['posts_per_page']);
		$nav->set_current_item_count($this->wp_query->post_count);
		$nav->set_show_pages($show_page);
		return $nav->build();
	}

	/**
	 *@since 2019.07.31
	 *合并返回：文章列表及分页导航
	 */
	public function get_results(): string {
		return $this->get_posts() . $this->get_pagination();
	}

	/**
	 *@since 0.8.64
	 *
	 *多重筛选：解析 $_GET 获取 WP_Query 参数，写入查询
	 * - 排除无 $_GET 参数的查询
	 * - 排除后台
	 * - 排除 Ajax 请求
	 * - 排除内页
	 *
	 *@since 0.8.72
	 * - 排除 WP 内置功能型 Post Type 查询
	 *
	 * 在内页或 Ajax 请求中，应且只能执行独立的 WP Query
	 */
	public static function action_on_pre_get_posts($query) {
		if (empty($_GET) or is_admin() or wnd_doing_ajax() or $query->is_singular()) {
			return $query;
		}

		$post_type = $query->query_vars['post_type'] ?? false;
		if ($post_type) {
			if (is_array($post_type)) {
				foreach ($post_type as $single_post_type) {
					if (!in_array($single_post_type, static::get_supported_post_types())) {
						return $query;
					}
				}unset($single_post_type);
			}

			if (!in_array($post_type, static::get_supported_post_types())) {
				return $query;
			}
		}

		/**
		 *解析 $_GET 获取 WP_Query 参数
		 * - 排除分页：pre_get_posts 仅适用于非独立 wp query，此种情况下分页已在 URL 中确定
		 */
		$query_vars = static::parse_query_vars();
		if (!$query_vars) {
			return $query;
		}
		unset($query_vars['paged']);

		/**
		 *依次将 $_GET 解析参数写入
		 */
		foreach ($query_vars as $key => $value) {
			/**
			 * tax_query 需要额外处理：
			 * 当在 taxonomy 归档页添加其他分类多重查询时，会导致归档类型判断错乱。
			 * 为保证归档页类型不变，需要提前获取默认 tax_query 查询参数，并保证默认查询为查询数组首元素（WP 以第一条 taxonomy 为标准）。
			 * @see WP_Query->get_queried_object();
			 */
			if ('tax_query' == $key) {
				$default_tax_query = $query->tax_query->queries ?? [];
				$query->set($key, array_merge($default_tax_query, $value));
			} else {
				$query->set($key, $value);
			}
		}unset($key, $value);

		return $query;
	}

	/**
	 *@since 0.9.0
	 * 定义多重筛选支持的 Post Types
	 * - 排除 WP 内置功能型 Post Type 查询
	 *
	 */
	protected static function get_supported_post_types(): array{
		$custom_post_types = get_post_types(['_builtin' => false]);
		return array_merge($custom_post_types, ['post' => 'post', 'page' => 'page', 'attachment' => 'attachment']);
	}
}
