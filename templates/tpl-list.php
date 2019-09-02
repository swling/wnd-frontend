<?php
/**
 *@since 2019.09.02
 *列表模板
 */

/**
 *@since 2019.08.16
 *用户邮件列表
 *@param 	object 	$query 	WP_Query 实例化结果
 *@return 	string 	$html 	输出表单
 **/
function _wnd_mail_posts_tpl($query) {
	$table = new Wnd_Posts_Table($query, true, true);
	$table->add_column(
		array(
			'post_field' => 'post_date',
			'title'      => '日期',
			'class'      => 'is-narrow is-hidden-mobile',
		)
	);
	$table->add_column(
		array(
			'post_field' => 'post_title_with_link',
			'title'      => '标题',
		)
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
function _wnd_posts_tpl($query) {
	$table = new Wnd_Posts_Table($query, true, true);
	$table->add_column(
		array(
			'post_field' => 'post_date',
			'title'      => '日期',
			'class'      => 'is-narrow is-hidden-mobile',
		)
	);
	$table->add_column(
		array(
			'post_field' => 'post_title_with_link',
			'title'      => '标题',
		)
	);
	$table->add_column(
		array(
			'post_field' => 'post_status',
			'title'      => '状态',
			'class'      => 'is-narrow',
		)
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
function _wnd_post_tpl($post) {
	$html = '<li><a href="' . get_permalink($post->ID) . '" target="_blank">' . $post->post_title . '</a></li>';
	return $html;
}

/**
 *@since 2019.03.14
 *以表格形式输出用户充值及消费记录
 */
function _wnd_user_fin_posts_tpl($query) {
	$table = new Wnd_Posts_Table($query, true, true);
	$table->add_column(
		array(
			'post_field' => 'post_date',
			'title'      => '日期',
			'class'      => 'is-narrow is-hidden-mobile',
		)
	);
	$table->add_column(
		array(
			'post_field' => 'post_author',
			'title'      => '用户',
			'class'      => 'is-narrow is-hidden-mobile',
		)
	);
	$table->add_column(
		array(
			'post_field' => 'post_content',
			'title'      => '金额',
			'class'      => 'is-narrow',
		)
	);
	$table->add_column(
		array(
			'post_field' => 'order' == $query->query_vars['post_type'] ? 'post_parent_with_link' : 'post_title',
			'title'      => '详情',
		)
	);
	$table->add_column(
		array(
			'post_field' => 'post_status',
			'title'      => '状态',
			'class'      => 'is-narrow',
		)
	);
	$table->build();
	return $table->html;
}
