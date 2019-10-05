<?php
/**
 *@since 2019.05.23
 *面包屑导航
 **/
function wnd_breadcrumb() {
	if (is_home() or is_author()) {
		return;
	}

	/**
	 *columns
	 **/
	$html = '<div class="breadcrumb-wrap columns is-mobile">';

	/**
	 *左侧导航
	 **/
	$html .= '<div class="column">';
	$html .= '<nav class="breadcrumb is-small" aria-label="breadcrumbs">';
	$html .= '<ul>';
	$html .= '<li><a href="' . home_url() . '">首页</a></li>';
	$queried_object = get_queried_object();

	// 内容页
	if (is_single()) {
		$html .= '<li><a href="' . get_post_type_archive_link($queried_object->post_type) . '">' . get_post_type_object($queried_object->post_type)->label . '</a></li>';

		$taxonomies = get_object_taxonomies($queried_object->post_type, $output = 'object');
		if ($taxonomies) {
			// 如果存在父级则调用父级的分类信息
			$post_id = $queried_object->post_parent ?: $queried_object->ID;

			foreach ($taxonomies as $taxonomy) {
				if (!is_taxonomy_hierarchical($taxonomy->name) or !$taxonomy->public) {
					continue;
				}

				$html .= get_the_term_list($queried_object->ID, $taxonomy->name, '<li>', '', '</li>');
			}
			unset($taxonomy);
		}

		// 父级post
		if ($queried_object->post_parent) {
			$html .= '<li><a href="' . get_permalink($queried_object->post_parent) . '">' . get_the_title($queried_object->post_parent) . '</a></li>';
		}

		$html .= '<li class="is-active"><a>详情</a></li>';

		//页面
	} elseif (is_page()) {

		// 父级page
		if ($queried_object->post_parent) {
			$html .= '<li><a href="' . get_permalink($queried_object->post_parent) . '">' . get_the_title($queried_object->post_parent) . '</a></li>';
		}

		$html .= '<li class="is-active"><a>' . $queried_object->post_title . '</a></li>';

		//post类型归档
	} elseif (is_post_type_archive()) {
		$html .= '<li class="is-active"><a>' . $queried_object->label . '</a></li>';

		//其他归档页
	} elseif (is_archive()) {
		$args = http_build_query(array('taxonomy' => $queried_object->taxonomy, 'orderby' => 'name'));
		$html .= '<li><a onclick="wnd_ajax_modal(\'wnd_terms_list\',\'' . $args . '\')">' . get_taxonomy($queried_object->taxonomy)->label . '</a></li>';
		$html .= '<li class="is-active"><a>' . $queried_object->name . '</a></li>';

	} else {
		$html .= '<li class="is-active"><a>' . wp_title('', false) . '</a></li>';
	}

	$html .= '</ul>';
	$html .= '</nav>';
	$html .= '</div>';

	/**
	 *左侧导航
	 **/
	$html .= '<div class="column is-narrow is-size-7 breadcrumb-right">';
	$breadcrumb_right = null;
	// 内页编辑
	if (is_single()) {
		if (current_user_can('edit_post', $queried_object->ID)) {
			$breadcrumb_right .= '<a href="' . get_edit_post_link($queried_object->ID) . '">[编辑]</a>';
			$breadcrumb_right .= '&nbsp;<a onclick="wnd_ajax_modal(\'Wnd_Post_Status_Form\',\'' . $queried_object->ID . '\')">[管理]</a>';
		}
	}
	$html .= apply_filters('wnd_breadcrumb_right', $breadcrumb_right);
	$html .= '</div>';

	/**
	 *容器结束
	 **/
	$html .= '</div>';

	return $html;
}

/**
 *@since 2019.05.26 bulma 颜色下拉选择
 */
function wnd_dropdown_colors($name, $selected) {
	$colors = array(
		'primary',
		'success',
		'info',
		'link',
		'warning',
		'danger',
		'dark',
		'black',
		'light',
	);

	$html = '<select name="' . $name . '">';
	foreach ($colors as $color) {
		if ($selected == $color) {
			$html .= '<option selected="selected" value="' . $color . '">' . $color . '</option>';
		} else {
			$html .= '<option value="' . $color . '">' . $color . '</option>';
		}
	}
	unset($color);
	$html .= '</select>';

	return $html;
}

/**
 *@since 2019.07.16
 *创建订单链接
 *@param int $post_id 产品/文章ID
 */
function wnd_order_link($post_id) {
	return wnd_get_do_url() . '?action=payment&post_id=' . $post_id . '&_wpnonce=' . wnd_create_nonce('payment');
}

/**
 *@since 2019.05.05
 *gallery 相册展示
 *@param $post_id 			int 		相册所附属的文章ID，若为0，则查询当前用户字段
 *@param $thumbnail_width 	number 		缩略图宽度
 *@param $thumbnail_height 	number 		缩略图高度
 **/
function wnd_gallery($post_id, $thumbnail_width = 160, $thumbnail_height = 120) {
	$images = $post_id ? wnd_get_post_meta($post_id, 'gallery') : wnd_get_user_meta(get_current_user_id(), 'gallery');
	if (!$images) {
		return false;
	}

	// 遍历输出图片集
	$html = '<div class="gallery columns is-vcentered is-multiline has-text-centered">';
	foreach ($images as $key => $attachment_id) {
		$attachment_url = wp_get_attachment_url($attachment_id);
		$thumbnail_url  = wnd_get_thumbnail_url($attachment_url, $thumbnail_width, $thumbnail_height);
		if (!$attachment_url) {
			unset($images[$key]); // 在字段数据中取消已经被删除的图片
			continue;
		}

		$html .= '<div class="attachment-' . $attachment_id . '" class="column is-narrow">';
		$html .= '<a><img class="thumbnail" src="' . $thumbnail_url . '" data-url="' . $attachment_url . '"height="' . $thumbnail_height . '" width="' . $thumbnail_width . '"></a>';
		$html .= '</div>';
	}
	unset($key, $attachment_id);
	wnd_update_post_meta($post_id, 'gallery', $images); // 若字段中存在被删除的图片数据，此处更新
	$html .= '</div>';

	return $html;
}

/**
 *@since 2019.02.27 获取WndWP文章缩略图
 *@param int $post_id 	文章ID
 *@param int $width 	缩略图宽度
 *@param int $height 	缩略图高度
 */
function wnd_post_thumbnail($post_id, $width, $height) {
	$post_id = $post_id ?: get_the_ID();
	if ($post_id) {
		$image_id = wnd_get_post_meta($post_id, '_thumbnail_id');
	}

	$url  = $image_id ? wnd_get_thumbnail_url($image_id, $width, $height) : WND_URL . '/static/images/default.jpg';
	$html = '<img class="thumbnail" src="' . $url . '" width="' . $width . '" height="' . $height . '">';

	return apply_filters('wnd_post_thumbnail', $html, $post_id, $width, $height);
}
