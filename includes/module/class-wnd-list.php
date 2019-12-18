<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Posts_Table;

/**
 *列表
 *
 *@since 2019.127.18
 */
class Wnd_List {

	/**
	 *@since 2019.08.16
	 *用户邮件列表
	 *@param 	object 	$query 	WP_Query 实例化结果
	 *@return 	string 	$html 	输出表单
	 **/
	public static function mail_table_tpl($query) {
		$table = new Wnd_Posts_Table($query, true, true);
		$table->add_column(
			[
				'post_field' => 'post_date',
				'title'      => '日期',
				'class'      => 'is-narrow is-hidden-mobile',
			]
		);
		$table->add_column(
			[
				'post_field' => 'post_title_with_link',
				'title'      => '标题',
			]
		);
		$table->build();
		$html = $table->html;
		return $html;
	}

	/**
	 *@since 2019.08.16
	 *常规文章列表
	 *@param 	object 	$query 	WP_Query 实例化结果
	 *@return 	string 	$html 	输出表单
	 **/
	public static function posts_table_tpl($query) {
		$table = new Wnd_Posts_Table($query, true, true);
		$table->add_column(
			[
				'post_field' => 'post_date',
				'title'      => '日期',
				'class'      => 'is-narrow is-hidden-mobile',
			]
		);
		$table->add_column(
			[
				'post_field' => 'post_title_with_link',
				'title'      => '标题',
			]
		);
		$table->add_column(
			[
				'post_field' => 'post_status',
				'title'      => '状态',
				'class'      => 'is-narrow',
			]
		);
		$table->build();
		$html = $table->html;
		return $html;
	}

	/**
	 *@since 2019.08.16
	 *常规单个文章模板 演示
	 *@param 	object 	$post 	post 对象
	 *@return 	string 	$html 	输出表单
	 **/
	public static function post_tpl($post) {
		$html = '<li><a href="' . get_permalink($post->ID) . '" target="_blank">' . $post->post_title . '</a></li>';
		return $html;
	}

	/**
	 *@since 2019.03.14
	 *以表格形式输出用户充值及消费记录
	 *
	 *@param 	object 	$query 	WP_Query 实例化结果
	 *@return 	string 	$html 	输出表单
	 */
	public static function finance_table_tpl($query) {
		$table = new Wnd_Posts_Table($query, true, true);
		$table->add_column(
			[
				'post_field' => 'post_date',
				'title'      => '日期',
				'class'      => 'is-narrow is-hidden-mobile',
			]
		);
		$table->add_column(
			[
				'post_field' => 'post_author',
				'title'      => '用户',
				'class'      => 'is-narrow',
			]
		);
		$table->add_column(
			[
				'post_field' => 'post_content',
				'title'      => '金额',
				'class'      => 'is-narrow',
			]
		);
		$table->add_column(
			[
				'post_field' => 'order' == $query->query_vars['post_type'] ? 'post_parent_with_link' : 'post_title',
				'title'      => '详情',
				'class'      => 'is-narrow',
			]
		);
		$table->add_column(
			[
				'post_field' => 'post_status',
				'title'      => '状态',
				'class'      => 'is-narrow is-hidden-mobile',
			]
		);
		$table->build();
		return $table->html;
	}
}
