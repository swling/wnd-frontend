<?php

/**
 *获取指定taxonomy的分类列表并附带下属标签
 *@since 2018
 */
function _wnd_list_categories_with_tags($cat_taxonomy, $tag_taxonomy = 'any', $limit = 10, $show_count = false, $hide_empty = 1) {

	$args = array('hide_empty' => $hide_empty, 'orderby' => 'count', 'order' => 'DESC');
	$terms = get_terms($cat_taxonomy, $args);

	if (empty($terms) || is_wp_error($terms)) {
		return;
	}

	$html = '<div class="list-' . $cat_taxonomy . '-with-tags list-categories-with-tags">' . PHP_EOL;

	foreach ($terms as $term) {

		// 获取分类
		$html .= '<div id="category-' . $term->term_id . '" class="category-with-tags">' . PHP_EOL;
		$html .= '<h3><a href="' . get_term_link($term) . '">' . $term->name . '</a></h3>' . PHP_EOL;

		$tag_list = '<ul class="list-tags-under-' . $term->term_id . ' list-tags-under-category menu-list">' . PHP_EOL;

		$tags = wnd_get_tags_under_category($term->term_id, $tag_taxonomy, $limit);
		foreach ($tags as $tag) {

			$tag_id = $tag->tag_id;
			$tag_id = (int) $tag_id;
			$tag_taxonomy = $tag->tag_taxonomy;

			$tag = get_term($tag_id);
			//输出常规链接
			if ($show_count) {
				$tag_list .= '<li><a href="' . get_term_link($tag_id) . '" >' . $tag->name . '</a>（' . $tag->count . '）</li>' . PHP_EOL;
			} else {
				$tag_list .= '<li><a href="' . get_term_link($tag_id) . '" >' . $tag->name . '</a></li>' . PHP_EOL;
			}

		}
		unset($tag);

		$tag_list .= '</ul>';
		$html .= $tag_list;

		$html .= '</div>' . PHP_EOL;
	}
	unset($term);

	$html .= '</div>' . PHP_EOL;

	return $html;

}

/**
 *@since 2019.04.25
 *生成taxonomy 多重查询参数 GET key：_term_{$taxonomy} GET value: $term_id
 *@param $args 						array 		get_terms参数
 *@param $remove_query_arg 			array 		需要移除的参数
 *@param $title 					string 		标题label
 */
function _wnd_term_query_arg($args = array(), $remove_query_arg = array('page', 'pages'), $title = '全部') {

	if (!$args) {
		return false;
	}

	$terms = get_terms($args);
	if (!$terms) {
		return;
	}

	$key = '_term_' . $args['taxonomy'];
	$current_term_id = isset($_GET[$key]) ? $_GET[$key] : false;
	$all_class = !$current_term_id ? 'class="is-active"' : false;

	// 输出容器
	$html = '<div class="columns is-marginless is-vcentered ' . $args['taxonomy'] . '-tabs">';
	$html .= '<div class="column is-narrow ' . $args['taxonomy'] . '-label">' . get_taxonomy($args['taxonomy'])->label . '：</div>';
	$html .= '<div class="tabs column">';
	$html .= '<ul class="tab">';

	// 全部
	$html .= '<li ' . $all_class . '><a href="' . remove_query_arg(array_merge($remove_query_arg, array($key))) . '">' . $title . '</a></li>';

	foreach ($terms as $term) {

		$current = ($current_term_id == $term->term_id) ? 'class="is-active"' : null;
		$html .= '<li ' . $current . '><a href="' . add_query_arg($key, $term->term_id, remove_query_arg($remove_query_arg)) . '">' . $term->name . '</a></li>' . PHP_EOL;

	}
	unset($term);

	// 输出结束
	$html .= '</ul>';
	$html .= '</div>';
	$html .= '</div>';

	return $html;

}

/**
 *@since 2019.04.25
 *获取分类下关联标签，并生产查询参数 GET key：_term_{$taxonomy} GET value: $term_id
 *@param $cat_id 		int 	分类id
 *@param $tag_taxonomy 	string 	标签taxonomy
 *@param $post_limits 	int 	标签输出数量
 */
function _wnd_related_tags_query_arg($cat_id, $tag_taxonomy, $limit = 10, $remove_query_arg = array('page', 'pages'), $title = '全部') {

	if (!taxonomy_exists($tag_taxonomy)) {
		return;
	}

	$key = '_term_' . $tag_taxonomy;
	$current_term_id = isset($_GET[$key]) ? $_GET[$key] : false;
	$all_class = !$current_term_id ? 'class="is-active"' : false;

	// 输出容器
	$html = '<div class="columns is-marginless is-vcentered ' . $tag_taxonomy . '-tabs">';
	$html .= '<div class="column is-narrow ' . $tag_taxonomy . '-label">' . get_taxonomy($tag_taxonomy)->label . '：</div>';
	$html .= '<div class="tabs column">';
	$html .= '<ul class="tab">';

	// 全部
	$html .= '<li ' . $all_class . '><a href="' . remove_query_arg(array_merge($remove_query_arg, array($key))) . '">' . $title . '</a></li>';

	$tags = wnd_get_tags_under_category($cat_id, $tag_taxonomy, $limit);
	foreach ($tags as $tag) {

		$tag_id = (int) $tag->tag_id;
		$term = get_term($tag_id);
		$current = ($current_term_id == $term->term_id) ? 'class="is-active"' : null;

		$html .= '<li ' . $current . '><a href="' . add_query_arg($key, $term->term_id, remove_query_arg($remove_query_arg)) . '">' . $term->name . '</a></li>';

	}
	unset($tag);

	// 输出结束
	$html .= '</ul>';
	$html .= '</div>';
	$html .= '</div>';

	return $html;
}

/**
 *@since 2019.04.25（未完成）
 *下拉菜单形式，生成taxonomy 多重查询参数 GET key：_term_{$taxonomy} GET value: $term_id
 *@param $args 						array 		get_terms参数
 *@param $remove_query_arg 			array 		需要移除的参数
 *@param $title 					string 		标题label
 */
function _wnd_term_select_query_arg() {
	?>
<div class="dropdown is-hoverable">
  <div class="dropdown-trigger">
    <button class="button" aria-haspopup="true" aria-controls="dropdown-menu2">
      <span><?php if (isset($_GET['local'])) {
		echo $_GET['local'];
	} else {
		echo '—所在地—';
	}
	?></span>
      <span class="icon is-small">
        <i class="fa fa-angle-down" aria-hidden="true"></i>
      </span>
    </button>
  </div>
  <div class="dropdown-menu" role="menu">
    <div class="dropdown-content">
    	<div class="dropdown-item" style="min-width: 50px;">
	    <?php
// 内容少于1000条时，不一定每个省份都有，采用查询发，反之，直接输出全部地域
	if ($term_count < 1000) {
		$terms = wndbiz_get_related_terms($term_id = $term_id, $terms_type = 'area');
	} else {
		$terms = get_terms('taxonomy=area&hide_empty=1');
	}
	foreach ($terms as $term) {
		?>
        <a href="?local=<?php echo $term->name; ?>" class="<?php if (isset($_GET['local']) && $_GET['local'] == $term->name) {
			echo 'on';
		}
		?>"><?php echo $term->name; ?></a>
        <?php
unset($term);
	}
	?>
        </div>
    </div>
  </div>
</div>
<?php
}

/**
 *term分类复选框（未完成）
 *@since 2019.04.25
 */
function _wnd_terms_checkbox($taxonomy, $value = 'slug', $name = '', $require = false) {

	if ($name == '') {
		$name = $taxonomy;
	}

	$args = array('hide_empty' => 0);
	$terms = get_terms($taxonomy, $args);

	if (!empty($terms) && !is_wp_error($terms)) {

		foreach ($terms as $term) {
			if ($value == 'slug') {
				echo '<input name="' . $name . '[]" type="checkbox" value="' . $term->slug . '"/>' . $term->name . PHP_EOL;
			} else {
				echo '<input name="' . $name . '[]" type="checkbox" value="' . $term->term_id . '"/>' . $term->name . PHP_EOL;
			}

		}
		unset($term);

	}
}
