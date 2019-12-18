<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Posts_Table;

/**
 *列表
 *
 *@since 2019.12.18
 */
class Wnd_Table_Tpl {

	/**
	 *构建表单
	 */
	public static function build_table(\WP_Query $query) {
		$method = $query->query_vars['post_type'] . '_table';
		if (method_exists(__CLASS__, $method)) {
			return self::$method($query);
		} else {
			return self::post_table($query);
		}
	}

	/**
	 *@since 2019.08.16
	 *常规文章列表
	 *@param 	object 	$query 	WP_Query 实例化结果
	 *@return 	string 	$html 	输出表单
	 **/
	protected static function post_table($query) {
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
	 *用户邮件列表
	 *@param 	object 	$query 	WP_Query 实例化结果
	 *@return 	string 	$html 	输出表单
	 **/
	protected static function mail_table($query) {
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
	 *@since 2019.03.14
	 *以表格形式输出用户充值记录
	 *
	 *@param 	object 	$query 	WP_Query 实例化结果
	 *@return 	string 	$html 	输出表单
	 */
	protected static function recharge_table($query) {
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
				'post_field' => 'post_title',
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

	/**
	 *@since 2019.03.14
	 *以表格形式输出用户消费记录
	 *
	 *@param 	object 	$query 	WP_Query 实例化结果
	 *@return 	string 	$html 	输出表单
	 */
	protected static function order_table($query) {
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
				'post_field' => 'post_parent_with_link',
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
