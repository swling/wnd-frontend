```php
use Wnd\View\Wnd_Filter;

/**
 *@since 2019.01.10
 *多重筛选演示代码
 **/

$filter = new Wnd_Filter();

$filter->add_post_type_filter(['company', 'resource', 'post']);

$filter->add_post_status_filter(
	[
		'发布' => 'publish',
		'草稿' => 'draft',
	]
);

// 快速新增主分类查询
$filter->add_taxonomy_filter(['taxonomy' => $filter->category_taxonomy]);

// 分别查询
$filter->add_taxonomy_filter(
	['taxonomy' => 'category']
);

$filter->add_taxonomy_filter(
	['taxonomy' => 'company_cat']
);

$filter->add_taxonomy_filter(
	['taxonomy' => 'resource_cat']
);

$filter->add_taxonomy_filter(
	['taxonomy' => 'region']
);

// 相关性标签
$filter->add_tags_filter($limit = 10);

$filter->add_meta_filter(
	[
		'label'   => '文章价格',
		'key'     => 'price',
		'options' => [
			'包含' => 'exists',
		],
		'compare' => 'exists',
	]
);

$filter->add_orderby_filter(
	[
		'label'   => '排序',
		'options' => [
			'发布时间' => 'date', //常规排序 date title等
			'浏览量'  => [ // 需要多个参数的排序
				'orderby'  => 'meta_value_num',
				'meta_key' => 'views',
			],
		],
	]
);

$filter->add_order_filter(
	$args = [
		'降序' => 'DESC',
		'升序' => 'ASC',
	],
	$label = '排序'
);

// 当前查询条件
$filter->add_current_filter();

/**
 *配置wp_query其他参数
 */
$filter->set_posts_per_page($posts_per_page = 3);

// 设置输出结果列表样式，传递参数：$post对象
$filter->set_post_template('wnd_post_tpl');
// or 设置输出结果整体模板，传递参数：wp_query查询结果
$filter->set_posts_template('wnd_posts_tpl');

// 新增查询参数：单个或数组
$filter->add_query_vars($query = ['test_key' => 'test_value']);
$tax_query = [
	'relation' => 'AND',
	[
		'taxonomy' => 'category',
		'field'    => 'term_id',
		'terms'    => 1,
	],
];
$filter->add_query_vars(['tax_query' => $tax_query]);

echo $filter->get_tabs();

// 执行查询
$filter->query();
echo '<div class="box">';
echo '<div id="filter-container">' . $filter->get_results() . '</div>';
'</div>';
```