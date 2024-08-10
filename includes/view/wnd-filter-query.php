<?php
namespace Wnd\View;

use Wnd\View\Wnd_Pagination;

/**
 * 筛选类查询参数处理类
 * - 解析 URL 参数为查询参数
 * - 新增查询参数;
 * @since 0.9.32
 */
class Wnd_Filter_Query {

	/**
	 * URL请求参数
	 * @since 2019.10.26
	 */
	private static $request_query_vars;

	/**
	 * 现有方法之外，其他新增的查询参数
	 * 将在筛选容器，及分页容器上出现，以绑定点击事件，发送到api接口
	 * 以data-{key}="{value}"形式出现，ajax请求中，将转化为 url请求参数 ?{key}={value}
	 */
	private $add_query_vars = [];

	/**
	 * 根据配置生成的最终查询参数
	 */
	private $query_vars = [];

	// 初始化查询参数
	public static $defaults = [
		'orderby'                => '',
		'order'                  => '',
		'meta_query'             => [],
		'tax_query'              => [],
		'date_query'             => [],
		'meta_key'               => '',
		'meta_value'             => '',
		'post_type'              => '',
		'post_status'            => '',
		'author'                 => 0,
		'no_found_rows'          => true,
		'paged'                  => 1,
		'update_post_term_cache' => true,
		'update_post_meta_cache' => true,
		'without_content'        => false,
		'posts_per_page'         => 0, // 默认设置为无效值 0，实际取值为 get_option( 'posts_per_page' ); 传参则覆盖之
	];

	/**
	 * Constructor.
	 * 解析 GET 请求为查询参数，并与默认参数合并组成初始查询参数
	 */
	public function __construct(array $default_query_vars = []) {
		static::$request_query_vars = static::parse_query_vars();
		$this->query_vars           = array_merge(static::$defaults, $default_query_vars, static::$request_query_vars);
	}

	/**
	 * 从GET参数中解析wp_query参数
	 * type={post_type}
	 * status={post_status}
	 * post字段
	 * _post_{post_field}={value}
	 * meta查询
	 * _meta_{key}={$meta_value}
	 * _meta_{key}=exists
	 * 分类查询
	 * _term_{$taxonomy}={term_id}
	 * 其他查询（具体参考 wp_query）
	 * $args[$key] = $value;
	 *
	 * @see 解析规则：
	 * @since 2019.07.20
	 *
	 * @return 	array 	wp_query $args
	 */
	public static function parse_query_vars(): array {
		if (empty($_GET)) {
			return [];
		}

		$query_vars = [
			'meta_query' => [],
			'tax_query'  => [],
			'date_query' => [],
		];

		/**
		 * @since 0.9.59
		 * 新增 $allowed_keys = array_keys(static::$defaults);
		 * 若不设置 $allowed_keys 则 $_GET 参数将全部写入 WP_Query 可能引起错误的数据库查询
		 */
		$allowed_keys = array_keys(static::$defaults);
		foreach ($_GET as $key => $value) {
			// 将【字符串布尔值】转为实体布尔值
			if ('false' == $value) {
				$value = false;
			} elseif ('true' == $value) {
				$value = true;
			}

			/**
			 * post type tabs生成的GET参数为：type={$post_type}
			 * 直接用 post_type 作为参数会触发WordPress原生请求导致错误
			 */
			if ('type' === $key) {
				$query_vars['post_type'] = $value;
				continue;
			}

			/**
			 * post status tabs生成的GET参数为：status={$post_status}
			 */
			if ('status' === $key) {
				$query_vars['post_status'] = $value;
				continue;
			}

			/**
			 * 添加搜索框支持
			 * 直接使用s作为GET参数，会与WordPress原生请求冲突
			 * @since 2020.05.11
			 */
			if ('search' === $key and $value) {
				$query_vars['s'] = $value;
				continue;
			}

			/**
			 * ?_meta_price=1 则查询 price = 1的文章
			 * ?_meta_price=exists 则查询 存在price的文章
			 * @since 2019.3.07 自动匹配meta query
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
				 * @since 2019.04.21 当meta_query compare == exists 不能设置value
				 */
				if ('exists' == $compare) {
					unset($meta_query['value']);
				}

				$query_vars['meta_query'][] = $meta_query;
				continue;
			}

			/**
			 * categories tabs生成的GET参数为：'_term_' . $taxonomy，
			 * 直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
			 *
			 * @link https://developer.wordpress.org/reference/classes/wp_query/#taxonomy-parameters
			 * Possible values are ‘term_id’, ‘name’, ‘slug’ or ‘term_taxonomy_id’. Default value is ‘term_id’.
			 */
			if (0 === strpos($key, '_term_')) {
				$term_query = [
					'taxonomy' => str_replace('_term_', '', $key),
					'field'    => is_numeric($value) ? 'term_id' : 'name',
					'terms'    => $value,
				];
				$query_vars['tax_query'][] = $term_query;
				continue;
			}

			/**
			 * @since 2019.05.31 post field查询
			 */
			if (0 === strpos($key, '_post_')) {
				$query_vars[str_replace('_post_', '', $key)] = $value;
				continue;
			}

			/**
			 * 分页
			 * @since 2019.07.30
			 */
			if (Wnd_Pagination::$page_query_var == $key) {
				$query_vars['paged'] = $value ?: 1;
				continue;
			}

			// 其他：若在允许的查询键名范围内，按键名自动匹配
			if (!in_array($key, $allowed_keys)) {
				continue;
			}

			if (is_array($value)) {
				$query_vars = wp_parse_args($value, $query_vars);
			} else {
				$query_vars[$key] = $value;
			}
		}
		unset($key, $value);

		return $query_vars;
	}

	/**
	 * 添加新的请求参数
	 * - 仅在独立 WP Query （true == $this->independent）时，可在外部直接调用
	 * - 添加的参数，将覆盖之前的设定，并将在所有请求中有效，直到被新的设定覆盖
	 * - 在非 ajax 环境中，直接将写入$query_vars[key] = value;
	 * - 在 ajax 环境中，应在接口响应数据中包含本数据，供前端处理
	 *
	 *
	 * 依赖型多重筛选中此参数无效，依赖型查询中，仅 URL 参数可合并写入查询参数
	 * - 依赖型多重筛选，通过 $_GET 参数及 pre_get_posts 钩子实现，无法直接在多重筛选中添加参数
	 *
	 * @since 2019.07.31
	 * @since 0.8.64
	 *
	 * @param array $query [key=>value]
	 */
	public function add_query_vars(array $query = []) {
		foreach ($query as $key => $value) {
			// 记录参数
			$this->add_query_vars[$key] = $value;

			/**
			 * 数组参数，合并元素
			 * 非数组参数，赋值 （php array_merge：相同键名覆盖，未定义键名或以整数做键名，则新增)
			 * $_GET参数优先，无法重新设置
			 */
			if (is_array($this->query_vars[$key] ?? false) and is_array($value)) {
				$this->query_vars[$key] = array_merge($this->query_vars[$key], $value, (static::$request_query_vars[$key] ?? []));
			} else {
				$this->query_vars[$key] = (static::$request_query_vars[$key] ?? false) ?: $value;
			}
		}
		unset($key, $value);
	}

	/**
	 * 设定指定查询参数
	 *
	 * 本方法与 $this->add_query_vars() 区别在于：
	 * - 本方法主要用于系统设定，即设定初始化时的参数
	 * - 相关设定参数不会对外界暴露
	 * - 典型用途：在完成对外部查询参数的解析后，强制添加或修改某些参数
	 *
	 * @since 0.9.32
	 */
	public function set_query_var(string $var, $value) {
		return $this->query_vars[$var] = $value;
	}

	/**
	 * 读取指定查询参数值
	 * @since 0.9.32
	 */
	public function get_query_var(string $var) {
		return $this->query_vars[$var] ?? '';
	}

	/**
	 * 读取全部查询参数数组
	 * @since 0.9.32
	 */
	public function get_query_vars(): array {
		return $this->query_vars;
	}

	/**
	 * 获取新增的查询参数
	 * @since 0.9.32
	 */
	public function get_add_query_vars(): array {
		return $this->add_query_vars;
	}

	/**
	 * 多重筛选：解析 $_GET 获取 WP_Query 参数，写入查询
	 * - 排除无 $_GET 参数的查询
	 * - 排除后台
	 * - 排除 Ajax 请求
	 * - 排除内页
	 * - 排除 WP 内置功能型 Post Type 查询
	 * 在内页或 Ajax 请求中，应且只能执行独立的 WP Query
	 *
	 * @since 0.8.64
	 * @since 0.8.72
	 */
	public static function action_on_pre_get_posts($query) {
		if (empty($_GET) or is_admin() or wnd_is_rest_request() or $query->is_singular()) {
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
		 * 解析 $_GET 获取 WP_Query 参数
		 * - 排除分页：pre_get_posts 仅适用于非独立 wp query，此种情况下分页已在 URL 中确定
		 */
		$query_vars = static::parse_query_vars();
		if (!$query_vars) {
			return $query;
		}
		unset($query_vars['paged']);

		/**
		 * 依次将 $_GET 解析参数写入
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
	 * 定义多重筛选支持的 Post Types
	 * - 排除 WP 内置功能型 Post Type 查询
	 * @since 0.9.0
	 */
	private static function get_supported_post_types(): array {
		$custom_post_types = get_post_types(['_builtin' => false]);
		return array_merge($custom_post_types, ['post' => 'post', 'page' => 'page', 'attachment' => 'attachment']);
	}

}
