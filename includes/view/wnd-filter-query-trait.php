<?php
namespace Wnd\View;

use Wnd\View\Wnd_Pagination;

/**
 * @since 0.9.25
 * 筛选类查询参数公共特性
 * - 解析 URL 参数为查询参数
 * - 新增查询参数;
 */
trait Wnd_Filter_Query_Trait {
	/**
	 *@since 2019.10.26
	 *URL请求参数
	 */
	protected static $request_query_vars;

	/**
	 * 现有方法之外，其他新增的查询参数
	 * 将在筛选容器，及分页容器上出现，以绑定点击事件，发送到api接口
	 * 以data-{key}="{value}"形式出现，ajax请求中，将转化为 url请求参数 ?{key}={value}
	 */
	protected $add_query_vars = [];

	/**
	 *根据配置生成的最终查询参数
	 */
	protected $query_args = [];

	/**
	 * @since 2019.07.20
	 * 从GET参数中解析wp_query参数
	 *
	 * @return 	array 	wp_query $args
	 *
	 * @see 解析规则：
	 * type={post_type}
	 * status={post_status}
	 *
	 * post字段
	 * _post_{post_field}={value}
	 *
	 *meta查询
	 * _meta_{key}={$meta_value}
	 * _meta_{key}=exists
	 *
	 *分类查询
	 * _term_{$taxonomy}={term_id}
	 *
	 * 其他查询（具体参考 wp_query）
	 * $args[$key] = $value;
	 **/
	public static function parse_query_vars() {
		if (empty($_GET)) {
			return [];
		}

		$query_vars = [
			'meta_query' => [],
			'tax_query'  => [],
			'date_query' => [],
		];

		foreach ($_GET as $key => $value) {
			/**
			 *post type tabs生成的GET参数为：type={$post_type}
			 *直接用 post_type 作为参数会触发WordPress原生请求导致错误
			 */
			if ('type' === $key) {
				$query_vars['post_type'] = $value;
				continue;
			}

			/**
			 *post status tabs生成的GET参数为：status={$post_status}
			 */
			if ('status' === $key) {
				$query_vars['post_status'] = $value;
				continue;
			}

			/**
			 *@since 2020.05.11
			 *
			 *添加搜索框支持
			 *直接使用s作为GET参数，会与WordPress原生请求冲突
			 */
			if ('search' === $key) {
				$query_vars['s'] = $value;
				continue;
			}

			/**
			 *@since 2019.3.07 自动匹配meta query
			 *?_meta_price=1 则查询 price = 1的文章
			 *?_meta_price=exists 则查询 存在price的文章
			 */
			if (0 === strpos($key, '_meta_')) {
				$key        = str_replace('_meta_', '', $key);
				$compare    = 'exists' == $value ? 'exists' : '=';
				$meta_query = [
					'key'     => $key,
					'value'   => $value,
					'compare' => $compare,
				];

				/**
				 *@since 2019.04.21 当meta_query compare == exists 不能设置value
				 */
				if ('exists' == $compare) {
					unset($meta_query['value']);
				}

				$query_vars['meta_query'][] = $meta_query;
				continue;
			}

			/**
			 *categories tabs生成的GET参数为：'_term_' . $taxonomy，
			 *直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
			 */
			if (0 === strpos($key, '_term_')) {
				$term_query = [
					'taxonomy' => str_replace('_term_', '', $key),
					'field'    => 'term_id',
					'terms'    => $value,
				];
				$query_vars['tax_query'][] = $term_query;
				continue;
			}

			/**
			 *@since 2019.05.31 post field查询
			 */
			if (0 === strpos($key, '_post_')) {
				$query_vars[str_replace('_post_', '', $key)] = $value;
				continue;
			}

			/**
			 *@since 2019.07.30
			 *分页
			 */
			if (Wnd_Pagination::$page_query_var == $key) {
				$query_vars['paged'] = $value ?: 1;
				continue;
			}

			// 其他：按键名自动匹配
			if (is_array($value)) {
				$query_vars = wp_parse_args($value, $query_vars);
			} else {
				$query_vars[$key] = $value;
			}
			continue;
		}
		unset($key, $value);

		/**
		 *定义如何过滤HTTP请求
		 *此处定义：过滤空值，但保留0
		 *@since 2019.10.26
		 **/
		$query_vars = array_filter($query_vars, function ($value) {
			return $value or 0 == $value;
		});

		return $query_vars;
	}

	/**
	 *@since 2019.07.31
	 *添加新的请求参数
	 *添加的参数，将覆盖之前的设定，并将在所有请求中有效，直到被新的设定覆盖
	 *
	 *@param array $query [key=>value]
	 *
	 *在非 ajax 环境中，直接将写入$query_args[key] = value;
	 *在 ajax 环境中，应在接口响应数据中包含本数据，供前端处理
	 *
	 *@since 0.8.64
	 *仅在独立 WP Query （true == $this->independent）时，可在外部直接调用
	 *依赖型多重筛选中此参数无效，依赖型查询中，仅 URL 参数可合并写入查询参数
	 */
	public function add_query_vars(array $query = []) {
		foreach ($query as $key => $value) {
			// 记录参数
			$this->add_query_vars[$key] = $value;

			/**
			 *数组参数，合并元素
			 *非数组参数，赋值 （php array_merge：相同键名覆盖，未定义键名或以整数做键名，则新增)
			 *$_GET参数优先，无法重新设置
			 */
			if (is_array($this->query_args[$key] ?? false) and is_array($value)) {
				$this->query_args[$key] = array_merge($this->query_args[$key], $value, (static::$request_query_vars[$key] ?? []));
			} else {
				$this->query_args[$key] = (static::$request_query_vars[$key] ?? false) ?: $value;
			}
		}
		unset($key, $value);
	}
}
