<?php

$table = new Wnd_Posts_Table($wp_query_args = array('author' => 1, 'posts_per_page' => 5),1,1);
$table->add_column(
	array(
		'post_field' => 'post_title_with_link',
		'title' => '标题1',
		'attr' => 'class="is-narrow"',
	)
);

$table->add_column(
	array(
		'post_field' => 'post_title',
		'title' => '日期',
	)
);

$table->add_column(
	array(
		'post_field' => 'post_date',
		'title' => '日期',
	)
);

$table->add_column(
	array(
		'post_field' => 'ID',
		'title' => 'ID',
	)
);

$table->add_column(
	array(
		'post_field' => false,
		'title' => '状态',
		'content' => '00',
	)
);

$table->build();

echo $table->html;