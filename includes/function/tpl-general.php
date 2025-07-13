<?php

/**
 * Post List 表格列表
 * @since 2019.12.31
 *
 * @param WP_Query 实例化
 */
function wnd_list_table(WP_Query $query) {
	return Wnd\Template\Wnd_List_Table::render($query);
}

/**
 * 面包屑导航
 * @since 2019.05.23
 */
function wnd_breadcrumb($font_size = 'is-small', $hierarchical = true) {
	if (is_home() or is_author()) {
		return;
	}

	/**
	 * columns
	 *
	 */
	$html = '<div class="breadcrumb-wrap columns is-mobile">';

	/**
	 * 左侧导航
	 *
	 */
	$html .= '<div class="column">';
	$html .= '<nav class="breadcrumb ' . $font_size . '" aria-label="breadcrumbs">';
	$html .= '<ul>';
	$html .= '<li><a href="' . home_url() . '">' . __('首页', 'wnd') . '</a></li>';
	$queried_object = get_queried_object();

	// 内容页
	if (is_single()) {
		$html .= '<li><a href="' . get_post_type_archive_link($queried_object->post_type) . '">' . get_post_type_object($queried_object->post_type)->label . '</a></li>';

		$taxonomies = get_object_taxonomies($queried_object->post_type, $output = 'object');
		if ($taxonomies) {
			// 如果存在父级则调用父级的分类信息
			$post_id = $queried_object->post_parent ?: $queried_object->ID;

			foreach ($taxonomies as $taxonomy) {
				if ($hierarchical and !is_taxonomy_hierarchical($taxonomy->name) or !$taxonomy->public) {
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

		//页面
	} elseif (is_page()) {

		// 父级page
		if ($queried_object->post_parent) {
			$html .= '<li><a href="' . get_permalink($queried_object->post_parent) . '">' . get_the_title($queried_object->post_parent) . '</a></li>';
		}

		$html .= '<li class="is-active"><a>' . get_the_title() . '</a></li>';

		//post类型归档
	} elseif (is_post_type_archive()) {
		$html .= '<li class="is-active"><a>' . $queried_object->label . '</a></li>';

		//搜索
	} elseif (is_search()) {
		$html .= '<li class="is-active"><a>' . __('搜索', 'wnd') . '</a></li>';

		//其他归档页
	} elseif (is_archive()) {
		$args = ['taxonomy' => $queried_object->taxonomy, 'orderby' => 'name'];
		$html .= '<li>' . wnd_modal_link(get_taxonomy($queried_object->taxonomy)->label, 'common/wnd_terms_list', $args) . '</li>';
		$html .= '<li class="is-active"><a>' . $queried_object->name . '</a></li>';

	} else {
		$html .= '<li class="is-active"><a>' . wp_title('', false) . '</a></li>';
	}

	$html .= '</ul>';
	$html .= '</nav>';
	$html .= '</div>';

	/**
	 * 左侧导航
	 *
	 */
	$html .= '<div class="column is-narrow is-size-7 breadcrumb-right">';
	$breadcrumb_right = '';
	// 内页编辑
	if (is_singular()) {
		if (current_user_can('edit_post', $queried_object->ID)) {
			$breadcrumb_right .= '<a href="' . get_edit_post_link($queried_object->ID) . '">[' . __('编辑', 'wnd') . ']</a>';
			$breadcrumb_right .= '&nbsp;' . wnd_modal_link('[' . __('状态', 'wnd') . ']', 'post/wnd_post_status_form', ['post_id' => $queried_object->ID]);
		}
	}
	$html .= apply_filters('wnd_breadcrumb_right', $breadcrumb_right);
	$html .= '</div>';

	/**
	 * 容器结束
	 *
	 */
	$html .= '</div>';

	return $html;
}

/**
 * @since 2019.05.26 bulma 颜色下拉选择
 */
function wnd_dropdown_colors($name, $selected) {
	$colors = [
		'primary',
		'success',
		'info',
		'link',
		'warning',
		'danger',
		'dark',
		'black',
		'light',
	];

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
 * @since 2019.02.27 获取WndWP文章缩略图
 *
 * @param int $post_id 	文章ID
 * @param int $width   	缩略图宽度
 * @param int $height  	缩略图高度
 */
function wnd_post_thumbnail($post_id, $width, $height) {
	$post_id = $post_id ?: get_the_ID();
	if ($post_id) {
		$image_id = wnd_get_post_meta($post_id, '_thumbnail_id');
	}

	$url  = $image_id ? wnd_get_attachment_url($image_id) : WND_URL . '/static/images/default.jpg';
	$html = '<img class="thumbnail" src="' . $url . '" width="' . $width . '" height="' . $height . '" loading="lazy">';

	return apply_filters('wnd_post_thumbnail', $html, $post_id, $width, $height);
}

/**
 * 付费按钮
 * @since 2020.03.21
 */
function wnd_pay_button(WP_post $post, bool $with_paid_content, string $text = ''): string {
	try {
		$button = new Wnd\Template\Wnd_Pay_Button($post, $with_paid_content, $text);
		return $button->render();
	} catch (Exception $e) {
		return '<!-- ' . $e->getMessage() . ' -->';
	}
}

/**
 * 构建消息
 * @since 2020.03.22
 */
function wnd_message($message, $color = '', $is_centered = false): string {
	if (!$message) {
		return '';
	}

	$class = 'message content wnd-message';
	$class .= $color ? ' ' . $color : ' is-' . wnd_get_config('primary_color');
	$class .= $is_centered ? ' has-text-centered' : '';

	return '<div class="' . $class . '"><div class="message-body">' . $message . '</div></div>';
}

/**
 * 构建系统通知
 * @since 2020.04.23
 */
function wnd_notification($notification, $add_class = '', $delete = false): string {
	if (!$notification) {
		return '';
	}

	$class = 'notification is-light';
	$class .= $add_class ? ' ' . $add_class : ' is-' . wnd_get_config('primary_color');

	$html = '<div class="' . $class . '">';
	$html .= $delete ? '<button class="delete"></button>' : '';
	$html .= $notification;
	$html .= '</div>';

	return $html;
}

/**
 * 唤起 Modal
 * @since 2020.04.23
 *
 * @param $text      		按钮文字
 * @param $module    	点击弹窗
 * @param $param     	传输参数
 * @param $add_calss class
 */
function wnd_modal_button($text, $module, $param = [], $add_class = '') {
	$class = 'button';
	$class .= $add_class ? ' ' . $add_class : '';
	$param = json_encode(wp_parse_args($param));

	$html = '<button class="' . $class . '" type="button"';
	$html .= $module ? ' onclick=\'wnd_ajax_modal("' . $module . '", ' . $param . ')\'' : '';
	$html .= '>' . $text . '</button>';

	return $html;
}

/**
 * 唤起 Modal
 * @since 0.8.73
 *
 * @param $text      		按钮文字
 * @param $module    	点击弹窗
 * @param $param     	传输参数
 * @param $add_calss class
 */
function wnd_modal_link($text, $module, $param = [], $add_class = '') {
	$class = '';
	$class .= $add_class ? ' ' . $add_class : '';
	$param = json_encode(wp_parse_args($param));

	$html = '<a class="' . $class . '"';
	$html .= $module ? ' onclick=\'wnd_ajax_modal("' . $module . '", ' . $param . ')\'' : '';
	$html .= '>' . $text . '</a>';

	return $html;
}

/**
 * 嵌入 Module
 * @since 0.8.73
 *
 * @param $text      		按钮文字
 * @param $module    	点击弹窗
 * @param $param     	传输参数
 * @param $add_calss class
 */
function wnd_embed_link($container, $text, $module, $param = [], $add_class = '') {
	$class = '';
	$class .= $add_class ? ' ' . $add_class : '';
	$param = json_encode(wp_parse_args($param));

	$html = '<a class="' . $class . '"';
	$html .= $module ? ' onclick=\'wnd_ajax_embed("' . $container . '", "' . $module . '", ' . $param . ')\'' : '';
	$html .= '>' . $text . '</a>';

	return $html;
}
