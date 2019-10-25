<?php
namespace Wnd\Controller;

use Exception;
use Wnd\View\Wnd_Filter;

/**
 *@since 2019.07.31
 *多重筛选API
 *
 *@since 2019.10.07 OOP改造
 *常规情况下，controller应将用户请求转为操作命令并调用model处理，但Wnd\View\Wnd_Filter是一个完全独立的功能类
 *Wnd\View\Wnd_Filter既包含了生成筛选链接的视图功能，也包含了根据请求参数执行对应WP_Query并返回查询结果的功能，且两者紧密相关不宜分割
 *可以理解为，Wnd\View\Wnd_Filter是通过生成一个筛选视图，发送用户请求，最终根据用户请求，生成新的视图的特殊类：
 *视图<->控制<->视图
 *
 * @see Wnd_Filter: parse_url_to_wp_query() 解析$_GET规则：
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
 * $wp_query_args[$key] = $value;
 *
 **/
class Wnd_Ajax_Filter extends Wnd_Ajax_Controller {

	public static function execute(): array{

		// 根据请求GET参数，获取wp_query查询参数
		try {
			$filter = new Wnd_Filter($is_ajax = true);
		} catch (Exception $e) {
			return array('status' => 0, 'msg' => $e->getMessage());
		}

		// 执行查询
		$filter->query();

		return array(
			'status' => 1,
			'data'   => array(
				'posts'             => $filter->get_posts(),

				/**
				 *@since 2019.08.10
				 *当前post type的主分类筛选项 约定：post(category) / 自定义类型 （$post_type . '_cat'）
				 *
				 *动态插入主分类的情况，通常用在用于一些封装的用户面板：如果用户内容管理面板
				 *常规筛选页面中，应通过add_taxonomy_filter方法添加
				 */
				'category_tabs'     => $filter->get_category_tabs(),
				'sub_taxonomy_tabs' => $filter->get_sub_taxonomy_tabs(),
				'related_tags_tabs' => $filter->get_related_tags_tabs(),
				'pagination'        => $filter->get_pagination(),
				'post_count'        => $filter->wp_query->post_count,

				/**
				 *当前post type支持的taxonomy
				 *前端可据此修改页面行为
				 */
				'taxonomies'        => get_object_taxonomies($filter->wp_query->query_vars['post_type'], 'names'),

				/**
				 *@since 2019.08.10
				 *当前post type的主分类taxonomy
				 *约定：post(category) / 自定义类型 （$post_type . '_cat'）
				 */
				'category_taxonomy' => $filter->category_taxonomy,

				/**
				 *在debug模式下，返回当前WP_Query查询参数
				 **/
				'query_vars'        => WP_DEBUG ? $filter->wp_query->query_vars : '请开启Debug',
			),
		);
	}
}
