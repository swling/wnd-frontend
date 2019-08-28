<?php

$query = new WP_Query(array('author' => 1, 'posts_per_page' => 5));

$table = new Wnd_Posts_Table($query, $show_edit = true, $show_preview = true);
$table->add_column(
	array(
		'post_field' => 'post_title_with_link',
		'title'      => '标题1',
		'class'      => 'is-narrow',
	)
);

$table->add_column(
	array(
		'post_field' => 'post_parent_with_link',
		'title'      => '详情',
	)
);

$table->add_column(
	array(
		'post_field' => 'post_title',
		'title'      => '日期',
	)
);

$table->add_column(
	array(
		'post_field' => 'post_date',
		'title'      => '日期',
	)
);

$table->add_column(
	array(
		'post_field' => 'ID',
		'title'      => 'ID',
	)
);

$table->add_column(
	array(
		'title'   => '状态',
		'content' => '00',
	)
);

$table->build();

echo $table->html;