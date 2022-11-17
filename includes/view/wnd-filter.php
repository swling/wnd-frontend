<?php
namespace Wnd\View;

use Wnd\View\Wnd_Pagination;

/**
 * 多重筛选类
 * 样式基于bulma css
 * @since 2019.07.30
 *
 * @param bool 	$independent 	是否为独立 WP Query
 */
class Wnd_Filter extends Wnd_Filter_Abstract {

	private $post_template;

	private $posts_template;

	// 筛选项HTML
	private $tabs = '';

	// 筛选结果HTML
	private $posts = '';

	/**
	 * 设置列表post模板函数，传递$post对象
	 * @since 2019.08.02
	 *
	 * @param string $template post模板函数名
	 */
	public function set_post_template($template) {
		$this->post_template = $template;
	}

	/**
	 * 文章列表页整体模板函数，传递wp_query查询结果
	 * 设置模板后，$this->get_posts() 即为被该函数返回值
	 * @since 2019.08.16
	 *
	 * @param string $template posts模板函数名
	 */
	public function set_posts_template($template) {
		$this->posts_template = $template;
	}

	/**
	 * 搜索框
	 * @since 2020.05.11
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
	 * 统一封装 Tabs 输出
	 * @since 0.9.25
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
			$all_label = (in_array($key, ['orderby', 'order'])) ? __('默认', 'wnd') : __('全部', 'wnd');
			$tabs .= '<li class="' . $this->get_tab_item_class($key, '', $options, $any) . '">';
			$tabs .= '<a href="' . remove_query_arg($key, $base_url) . '">' . $all_label . '</a>';
			$tabs .= '</li>';
		}

		// 输出 Tab 选项
		foreach ($options as $name => $value) {
			$tabs .= '<li class="' . $this->get_tab_item_class($key, $value, $options, $any) . '">';
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
	 * 筛选项详情菜单 Class
	 */
	private function get_tab_item_class($key, $value, $options, $any): string{
		$query_vars = $_GET[$key] ?? '';

		// post_type 及 post_status 默认首项
		if (!$any and !$query_vars and in_array($key, ['status', 'type'])) {
			if ($value == reset($options)) {
				return 'item is-active';
			}
		}

		return ($query_vars == $value) ? 'item is-active' : 'item';
	}

	/**
	 * 获取筛选项HTML
	 * tabs筛选项由于参数繁杂，无法通过api动态生成，因此不包含在api请求响应中
	 * 但已生成的相关筛选项会根据wp_query->query_var参数做动态修改
	 * @since 2019.07.31
	 */
	public function get_tabs() {
		$tabs = apply_filters('wnd_filter_tabs', $this->tabs, $this->get_query_vars());
		return '<div class="wnd-filter-tabs">' . $tabs . '</div>';
	}

	/**
	 * 获取筛结果HTML
	 * @since 2019.07.31
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
	 * 分页导航
	 * @since 2019.02.15
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
	 * 合并返回：文章列表及分页导航
	 * @since 2019.07.31
	 */
	public function get_results(): string {
		return $this->get_posts() . $this->get_pagination();
	}

}
