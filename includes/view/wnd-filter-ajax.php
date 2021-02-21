<?php

namespace Wnd\View;

/**
 * @since 0.9.5
 * 多重筛选 Json API
 */
class Wnd_Filter_Ajax extends Wnd_Filter {

	protected $before_html = '';

	protected $after_html = '';

	// 筛选项数据
	protected $tabs = [];

	// 筛选结果数据
	protected $posts = [];

	// 分页导航数据
	protected $pagination = [];

	/**
	 *筛选器之前 Html
	 */
	public function add_before_html($html) {
		$this->before_html .= $html;
	}

	/**
	 *筛选器之后 Html
	 */
	public function add_after_html($html) {
		$this->after_html .= $html;
	}

	// 搜索框（未完成）
	public function add_search_form($button = 'Search', $placeholder = '') {
		return [];
	}

	/**
	 *构造 Ajax 筛选菜单数据
	 */
	protected function build_tabs(string $key, array $options, string $title, bool $with_any_tab, array $remove_query_args = []): array{
		if (!$options) {
			return [];
		}

		// 筛选添加改变时，移除 Page 参数
		$remove_query_args[] = 'page';

		if ($with_any_tab) {
			$options = array_merge([__('全部', 'wnd') => ''], $options);
		}

		$tabs = [
			'key'               => $key,
			'title'             => $title,
			'options'           => $options,
			'remove_query_args' => $remove_query_args,
		];
		$this->tabs[] = $tabs;

		return $tabs;
	}

	/**
	 *分页导航
	 */
	protected function build_pagination($show_page = 5) {
		return [
			'paged'         => $this->wp_query->query_vars['paged'] ?: 1,
			'max_num_pages' => $this->wp_query->max_num_pages,
			'per_page'      => $this->wp_query->query_vars['posts_per_page'],
			'current_count' => $this->wp_query->post_count,
			'show_page'     => $show_page,
		];
	}

	/**
	 *获取完整筛选 Tabs
	 */
	public function get_tabs() {
		return $this->tabs;
	}

	/**
	 *获取当前查询的主分类 Tabs
	 *
	 */
	public function get_category_tabs($args = []) {
		$args['taxonomy'] = $this->category_taxonomy;
		$args['parent']   = $args['parent'] ?? 0;
		return $this->build_taxonomy_filter($args);
	}

	/**
	 *获取主分类关联标签筛选 Tabs
	 *
	 */
	public function get_tags_tabs($limit = 10) {
		return $this->build_tags_filter($limit);
	}

	/**
	 *获取筛结果集
	 *@since 0.9.25
	 *@param bool $with_post_content 是否包含正文内容
	 * 		-在很多情况下 Ajax 筛选用于各类管理面板，此时仅需要获取 post 列表，无需包含正文内容，以减少网络数据发送量
	 */
	public function get_posts(bool $with_post_content = true): array{
		if (!$this->wp_query) {
			return __('未执行WP_Query', 'wnd');
		}

		foreach ($this->wp_query->get_posts() as $post) {
			if (!$with_post_content) {
				unset($post->post_content);
			}
			$this->posts[] = $post;
		}
		unset($post);

		return $this->posts;
	}

	/**
	 *分页导航
	 */
	public function get_pagination($show_page = 5) {
		if (!$this->wp_query) {
			return __('未执行WP_Query', 'wnd');
		}

		$this->pagination = $this->build_pagination($show_page);
		return $this->pagination;
	}

	/**
	 *@since 0.9.25
	 *获取完整的筛选数据结构：适用于初始化筛选器
	 *
	 *@param bool $with_post_content 是否包含正文内容
	 * 		-在很多情况下 Ajax 筛选用于各类管理面板，此时仅需要获取 post 列表，无需包含正文内容，以减少网络数据发送量
	 */
	public function get_filter(bool $with_post_content = true): array{
		return [
			'before_html'       => $this->before_html,
			'after_html'        => $this->after_html,
			'tabs'              => $this->get_tabs(),
			'posts'             => $this->get_posts($with_post_content),

			/**
			 *@since 2019.08.10
			 *当前post type的主分类筛选项 约定：post(category) / 自定义类型 （$post_type . '_cat'）
			 *
			 *动态插入主分类的情况，通常用在用于一些封装的用户面板：如果用户内容管理面板
			 *常规筛选页面中，应通过add_taxonomy_filter方法添加
			 */
			'category_tabs'     => $this->get_category_tabs(),
			'sub_taxonomy_tabs' => $this->get_sub_taxonomy_tabs(),
			'tags_tabs'         => $this->get_tags_tabs(),
			'pagination'        => $this->get_pagination(),
			// 'post_count'        => $this->wp_query->post_count,

			/**
			 *当前post type支持的taxonomy
			 *前端可据此修改页面行为
			 */
			'taxonomies'        => get_object_taxonomies($this->wp_query->query_vars['post_type'], 'names'),

			/**
			 *@since 2019.08.10
			 *当前post type的主分类taxonomy
			 *约定：post(category) / 自定义类型 （$post_type . '_cat'）
			 */
			'category_taxonomy' => $this->category_taxonomy,

			'add_query_vars'    => $this->add_query_vars,
			'query_vars'        => $this->wp_query->query_vars,
		];
	}

	/**
	 *获取查询结果集：适用于已经完成初始化的筛选器，后续筛选查询（出主分类和标签筛选项外，不含其他 Tabs ）
	 */
	public function get_results(): array{
		$structure = $this->get_filter();
		unset($structure['before_html']);
		unset($structure['tabs']);
		unset($structure['after_html']);
		return $structure;
	}
}
