<?php
namespace Wnd\View;

/**
 * 分页导航条
 * @since 2020.08.18
 */
class Wnd_Pagination {

	// 当前页码
	private $paged = 1;

	public static $page_query_var = 'pages';

	// 最大页码
	private $max_num_pages;

	// 每页项目数
	private $items_per_page;

	// 当前页项目数
	private $current_item_count;

	// 容器 class
	private $class;

	// data
	private $data;

	// 分页是否为非 WP 原生分页的独立查询
	private $independent;

	/**
	 * Constructor.
	 *
	 */
	public function __construct($independent = true) {
		$this->independent = $independent;
	}

	/**
	 * 当前页面
	 */
	public function set_paged($paged) {
		$this->paged = $paged;
	}

	/**
	 * 最大页面：若未设置此属性，则生成 《上一页 下一页》 类导航，在数据较多的站点，建议忽略此属性
	 */
	public function set_max_num_pages($max_num_pages) {
		$this->max_num_pages = $max_num_pages;
	}

	/**
	 * 每页项目数
	 */
	public function set_items_per_page($items_per_page) {
		$this->items_per_page = $items_per_page;
	}

	/**
	 * 当前页面项目数
	 */
	public function set_current_item_count($current_item_count) {
		$this->current_item_count = $current_item_count;
	}

	/**
	 * 常规分页中，展示的导航页面数量
	 */
	public function set_show_pages($show_pages) {
		$this->show_pages = $show_pages;
	}

	/**
	 * 其他数据，转为HTML data属性
	 */
	public function set_data(array $data) {
		$this->data = $data;
	}

	/**
	 * 容器HTML class 用空格隔开多个类
	 */
	public function add_class($class) {
		$this->class = $class;
	}

	/**
	 * 简单分页导航
	 * - 不查询总数的情况下，简单实现下一页翻页
	 * - 翻页参数键名page 不能设置为 paged 会与原生WordPress翻页机制产生冲突
	 * @since 2019.02.15
	 */
	public function build() {
		if (!$this->max_num_pages) {
			return $this->build_next_pagination();
		} else {
			return $this->build_general_pagination();
		}
	}

	/**
	 * 未查询文章总数，以上一页下一页的形式翻页(在数据较多的情况下，可以提升查询性能)
	 * 在ajax环境中，动态分页较为复杂，暂统一设定为上下页的形式，前端处理更容易
	 */
	private function build_next_pagination() {
		if ($this->independent) {
			$previous_link = add_query_arg(static::$page_query_var, $this->paged - 1);
			$next_link     = add_query_arg(static::$page_query_var, $this->paged + 1);
		} else {
			$previous_link = get_pagenum_link($this->paged - 1);
			$next_link     = get_pagenum_link($this->paged + 1);
		}

		$html = '<nav class="pagination is-centered ' . $this->class . '">';
		$html .= '<ul class="pagination-list">';
		if ($this->paged >= 2) {
			$html .= '<li><a data-key="paged" data-value="' . ($this->paged - 1) . '" class="pagination-previous" href="' . $previous_link . '">' . __('上一页', 'wnd') . '</a>';
		}
		if ($this->current_item_count >= $this->items_per_page) {
			$html .= '<li><a data-key="paged" data-value="' . ($this->paged + 1) . '" class="pagination-next" href="' . $next_link . '">' . __('下一页', 'wnd') . '</a>';
		}
		$html .= '</ul>';
		$html .= '</nav>';

		return $html;
	}

	/**
	 * 常规分页，需要查询文章总数
	 * 在数据量较大的站点，查询文章总数会较为费时
	 */
	private function build_general_pagination() {
		if ($this->independent) {
			$first_link    = remove_query_arg(static::$page_query_var);
			$previous_link = add_query_arg(static::$page_query_var, $this->paged - 1);
			$next_link     = add_query_arg(static::$page_query_var, $this->paged + 1);
			$last_link     = add_query_arg(static::$page_query_var, $this->max_num_pages);
		} else {
			$first_link    = get_pagenum_link(1);
			$previous_link = get_pagenum_link($this->paged - 1);
			$next_link     = get_pagenum_link($this->paged + 1);
			$last_link     = get_pagenum_link($this->max_num_pages);
		}

		$html = '<nav class="pagination is-centered ' . $this->class . '"' . $this->build_data_attr($this->data) . '>';
		if ($this->paged > 1) {
			$html .= '<a data-key="paged" data-value="' . ($this->paged - 1) . '" class="pagination-previous" href="' . $previous_link . '">' . __('上一页', 'wnd') . '</a>';
		}

		if ($this->paged < $this->max_num_pages) {
			$html .= '<a data-key="paged" data-value="' . ($this->paged + 1) . '" class="pagination-next" href="' . $next_link . '">' . __('下一页', 'wnd') . '</a>';
		}

		$html .= '<ul class="pagination-list">';
		$html .= '<li><a data-key="paged" data-value="" class="pagination-link" href="' . $first_link . '" >' . __('首页', 'wnd') . '</a></li>';
		for ($i = $this->paged - 1; $i <= $this->paged + $this->show_pages; $i++) {
			if ($i > 0 and $i <= $this->max_num_pages) {
				$page_link = $this->independent ? add_query_arg(static::$page_query_var, $i) : get_pagenum_link($i);
				if ($i == $this->paged) {
					$html .= '<li><a data-key="paged" data-value="' . $i . '" class="pagination-link is-current" href="' . $page_link . '"> <span>' . $i . '</span> </a></li>';
				} else {
					$html .= '<li><a data-key="paged" data-value="' . $i . '" class="pagination-link" href="' . $page_link . '"> <span>' . $i . '</span> </a></li>';
				}
			}
		}
		if ($this->paged < $this->max_num_pages - 3) {
			$html .= '<li><span class="pagination-ellipsis">&hellip;</span></li>';
		}
		$html .= '<li><a data-key="paged" data-value="' . $this->max_num_pages . '" class="pagination-link" href="' . $last_link . '">' . __('尾页', 'wnd') . '</a></li>';
		$html .= '</ul>';
		$html .= '</nav>';

		return $html;
	}

	/**
	 * 构造HTML data属性
	 */
	private function build_data_attr($data_array): string {
		if (!$data_array) {
			return '';
		}

		$data = ' ';
		foreach ($data_array as $key => $value) {
			$value = is_array($value) ? http_build_query($value) : $value;
			$data .= 'data-' . $key . '="' . $value . '" ';
		}

		return $data;
	}
}
