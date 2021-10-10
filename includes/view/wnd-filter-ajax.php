<?php
namespace Wnd\View;

use Wnd\Model\Wnd_Init;

/**
 * 多重筛选 Json API
 * @since 0.9.5
 */
class Wnd_Filter_Ajax extends Wnd_Filter_Abstract {

	private $before_html = '';

	private $after_html = '';

	// 筛选项数据
	private $tabs = [];

	// 筛选结果数据
	private $posts = [];

	/**
	 * 筛选器之前 Html
	 */
	public function add_before_html($html) {
		$this->before_html .= $html;
	}

	/**
	 * 筛选器之后 Html
	 */
	public function add_after_html($html) {
		$this->after_html .= $html;
	}

	// 搜索框（未完成）
	public function add_search_form($button = 'Search', $placeholder = '') {
		return [];
	}

	// 移除筛选结果正文
	public function remove_post_content() {
		$this->add_query_vars(['without_content' => 1]);
	}

	/**
	 * 构造 Ajax 筛选菜单数据
	 */
	protected function build_tabs(string $key, array $options, string $label, bool $any, array $remove_args = []): array{
		if (!$options) {
			return [];
		}

		// 筛选添加改变时，移除 Page 参数
		$remove_args[] = 'paged';

		if ($any) {
			$options = array_merge([__('全部', 'wnd') => ''], $options);
		}

		$tabs = [
			'key'         => $key,
			'label'       => $label,
			'options'     => $options,
			'remove_args' => $remove_args,
		];
		$this->tabs[] = $tabs;

		return $tabs;
	}

	/**
	 * 获取完整筛选 Tabs
	 */
	protected function get_tabs(): array{
		return $this->tabs;
	}

	/**
	 * 获取当前查询的主分类 Tabs
	 *
	 */
	private function get_category_tabs($args = []): array{
		$args['taxonomy'] = $this->category_taxonomy;
		$args['parent']   = $args['parent'] ?? 0;
		return $this->build_taxonomy_filter($args) ?: [];
	}

	/**
	 * 获取主分类关联标签筛选 Tabs
	 *
	 */
	private function get_tags_tabs($limit = 10): array{
		return $this->build_tags_filter($limit) ?: [];
	}

	/**
	 * 当前 tax query 所有子类筛选项
	 * - 子类查询需要根据当前tax query动态生成
	 * - 在ajax状态中，需要经由此方法，交付api响应动态生成
	 *
	 * @since 2019.08.09
	 * @since 0.9.38 Wnd_Filter_Abstract => Wnd_Filter_Ajax
	 *
	 * @return array $sub_tabs_array[$taxonomy] = [$sub_tabs];
	 */
	private function get_sub_taxonomy_tabs(): array{
		$sub_tabs_array = [];
		foreach ($this->get_tax_query() as $tax_query) {
			// WP_Query tax_query参数可能存在：'relation' => 'AND', 'relation' => 'OR',参数，需排除 @since 2019.06.14
			if (!isset($tax_query['terms'])) {
				continue;
			}

			// 当前分类的子类
			$args = [
				'taxonomy' => $tax_query['taxonomy'],
				'parent'   => $tax_query['terms'],
			];
			$sub_tabs[] = $this->build_taxonomy_filter($args);

			// 构造子类查询
			$sub_tabs_array[$tax_query['taxonomy']] = $sub_tabs;
		}
		unset($tax_query);

		return $sub_tabs_array;
	}

	/**
	 * 获取筛结果集
	 * @since 0.9.25
	 */
	protected function get_posts(): array{
		if (!$this->wp_query) {
			return __('未执行WP_Query', 'wnd');
		}

		foreach ($this->wp_query->get_posts() as $post) {
			if ($this->get_query_var('without_content')) {
				unset($post->post_content);
			}

			// 用户信息
			$author       = get_userdata($post->post_author);
			$author_name  = $author ? ($author->display_name ?? $author->user_login) : 'anonymous';
			$author_link  = $author ? get_author_posts_url($post->post_author) : '';
			$post->author = ['name' => $author_name, 'link' => $author_link];

			// Post Link
			$post->link = get_permalink($post);

			// 财务类 Post Content 为金额，需格式化
			if (in_array($post->post_type, Wnd_Init::get_fin_types())) {
				$post->post_content = number_format((float) $post->post_content, 2);
			}

			$this->posts[] = $post;
		}
		unset($post);

		// Filter
		return apply_filters('wnd_filter_posts', $this->posts);
	}

	/**
	 * 分页导航
	 */
	protected function get_pagination($show_page = 5): array{
		if (!$this->wp_query) {
			return __('未执行WP_Query', 'wnd');
		}

		return [
			'paged'         => $this->wp_query->query_vars['paged'] ?: 1,
			'max_num_pages' => $this->wp_query->max_num_pages,
			'per_page'      => $this->wp_query->query_vars['posts_per_page'],
			'current_count' => $this->wp_query->post_count,
			'show_page'     => $show_page,
		];
	}

	/**
	 * 获取完整的筛选数据结构：适用于初始化筛选器
	 * @since 0.9.25
	 *
	 * @param bool $with_post_content 是否包含正文内容
	 */
	public function get_filter(): array{
		return [
			'before_html'       => $this->before_html,
			'after_html'        => $this->after_html,
			'tabs'              => $this->get_tabs(),
			'posts'             => $this->get_posts(),

			/**
			 * 当前post type的主分类筛选项 约定：post(category) / 自定义类型 （$post_type . '_cat'）
			 * - 动态插入主分类的情况，通常用在用于一些封装的用户面板：如果用户内容管理面板
			 * - 常规筛选页面中，应通过add_taxonomy_filter方法添加
			 * @since 2019.08.10
			 */
			'category_tabs'     => $this->get_category_tabs(),
			'sub_taxonomy_tabs' => $this->get_sub_taxonomy_tabs(),
			'tags_tabs'         => $this->get_tags_tabs(),
			'pagination'        => $this->get_pagination(),
			// 'post_count'        => $this->wp_query->post_count,

			/**
			 * 当前post type支持的taxonomy
			 * 前端可据此修改页面行为
			 */
			'taxonomies'        => get_object_taxonomies($this->wp_query->query_vars['post_type'], 'names'),

			/**
			 * 当前post type的主分类taxonomy
			 * 约定：post(category) / 自定义类型 （$post_type . '_cat'）
			 * @since 2019.08.10
			 */
			'category_taxonomy' => $this->category_taxonomy,

			'add_query_vars'    => $this->get_add_query_vars(),
			'query_vars'        => $this->wp_query->query_vars,
		];
	}

	/**
	 * 获取查询结果集：适用于已经完成初始化的筛选器，后续筛选查询（出主分类和标签筛选项外，不含其他 Tabs ）
	 */
	public function get_results(): array{
		$structure = $this->get_filter();
		unset($structure['before_html']);
		unset($structure['tabs']);
		unset($structure['after_html']);
		return $structure;
	}
}
