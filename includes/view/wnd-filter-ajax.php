<?php
namespace Wnd\View;

/**
 * 多重筛选 Json API
 * @since 0.9.5
 */
class Wnd_Filter_Ajax extends Wnd_Filter_Abstract {

	protected $before_html = '';

	protected $after_html = '';

	// 筛选项数据
	protected $tabs = [];

	// 筛选结果数据
	protected $posts = [];

	// 分页导航数据
	protected $pagination = [];

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
	protected function get_category_tabs($args = []): array{
		$args['taxonomy'] = $this->category_taxonomy;
		$args['parent']   = $args['parent'] ?? 0;
		return $this->build_taxonomy_filter($args) ?: [];
	}

	/**
	 * 获取主分类关联标签筛选 Tabs
	 *
	 */
	protected function get_tags_tabs($limit = 10): array{
		return $this->build_tags_filter($limit) ?: [];
	}

	/**
	 * 获取筛结果集
	 * - 在很多情况下 Ajax 筛选用于各类管理面板，此时仅需要获取 post 列表，无需包含正文内容，以减少网络数据发送量
	 * @since 0.9.25
	 *
	 * @param bool $with_post_content 是否包含正文内容
	 */
	protected function get_posts(bool $with_post_content = true): array{
		if (!$this->wp_query) {
			return __('未执行WP_Query', 'wnd');
		}

		foreach ($this->wp_query->get_posts() as $post) {
			if (!$with_post_content) {
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
			if (in_array($post->post_type, \Wnd\Model\Wnd_Init::FIN_TYPS)) {
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
	 * - 在很多情况下 Ajax 筛选用于各类管理面板，此时仅需要获取 post 列表，无需包含正文内容，以减少网络数据发送量
	 * @since 0.9.25
	 *
	 * @param bool $with_post_content 是否包含正文内容
	 */
	public function get_filter(bool $with_post_content = true): array{
		return [
			'before_html'       => $this->before_html,
			'after_html'        => $this->after_html,
			'tabs'              => $this->get_tabs(),
			'posts'             => $this->get_posts($with_post_content),

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

			'add_query_vars'    => $this->query->get_add_query_vars(),
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
