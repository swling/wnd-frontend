<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@see
 *自定义一些标准模块以便在页面或ajax请求中快速调用
 *函数均以echo直接输出返回
 *以_wnd_做前缀的函数可用于ajax请求，无需nonce校验，因此相关模板函数中不应有任何数据库操作，仅作为界面响应输出。
 */

/**
 *@since 2019.01.31 发布/编辑文章通用模板
 */
function _wnd_post_form($args = array()) {

	$defaults = array(
		'post_id' => 0,
		'post_type' => 'post',
		'post_parent' => 0,
		'is_free' => 1,
		'with_file' => 0,
		'with_excerpt' => 0,
		'with_thumbnail' => 0, //0 无缩略图，1、存储在wnd_meta _thumbnail_id字段: _wnd_the_post_thumbnail($width = 0, $height = 0)
		'thumbnail_size' => array('width' => 160, 'height' => 120),
		'with_gallery' => 0, //相册
		'gallery_label' => '', //相册默认提示信息
		'rich_media_editor' => 1,
	);
	$args = wp_parse_args($args, $defaults);
	$post_id = $args['post_id'];
	$post_type = $args['post_id'] ? get_post_type($args['post_id']) : $args['post_type'];
	$post_parent = $args['post_parent'];

	/**
	 *@since 2019.02.13 表单标题
	 **/
	if (!isset($args['form_title'])) {
		$args['form_title'] = $post_id ? 'ID: ' . $post_id : '';
	} elseif (!empty($args['form_title'])) {
		$args['form_title'] = '' . $args['form_title'];
	}

	/**
	 *@since 2019.02.01
	 *获取指定 post type的所有注册taxonomy
	 */
	$cat_taxonomies = array();
	$tag_taxonomies = array();
	$taxonomies = get_object_taxonomies($post_type, $output = 'object');

	if ($taxonomies) {
		foreach ($taxonomies as $taxonomy) {

			// 私有taxonomy 排除
			if (!$taxonomy->public) {
				continue;
			}

			if ($taxonomy->hierarchical) {
				array_push($cat_taxonomies, $taxonomy->name);
			} else {
				array_push($tag_taxonomies, $taxonomy->name);
			}
		}
		unset($taxonomy);
	}

	/**
	 *@since 2019.03.11 表单类
	 */
	$form = new Wnd_Post_Form($post_type, $post_id);

	$form->set_form_title($args['form_title']);

	$form->add_post_title();

	if ($args['with_excerpt']) {
		$form->add_post_excerpt();
	}

	// 遍历分类
	if ($cat_taxonomies) {
		$form->add_html('<div class="field is-horizontal"><div class="field-body">');
		foreach ($cat_taxonomies as $cat_taxonomy) {
			$form->add_post_term_select($cat_taxonomy);
		}
		unset($cat_taxonomy);
		$form->add_html('</div></div>');
	}

	// 遍历标签
	if ($tag_taxonomies) {
		foreach ($tag_taxonomies as $tag_taxonomy) {
			// 排除WordPress原生 文章格式类型
			if ($tag_taxonomy == 'post_format') {
				continue;
			}
			$form->add_post_tags($tag_taxonomy, '请用回车键区分多个标签');
			$form->add_html('<div class="message is-warning"><div class="message-body">请用回车键区分多个标签</div></div>');

		}
		unset($tag_taxonomy);
	}

	// 缩略图
	if ($args['with_thumbnail']) {
		$form->add_post_thumbnail($args['thumbnail_size']['width'], $args['thumbnail_size']['height']);
	}

	// 相册
	if ($args['with_gallery']) {
		$form->add_post_gallery_upload($args['thumbnail_size']['width'], $args['thumbnail_size']['height'], $args['gallery_label']);
	}

	if ($args['with_file'] or !$args['is_free']) {
		$form->add_post_file_upload($meta_key = 'file');
	}

	if (!$args['is_free']) {
		$form->add_post_price();
	}

	/**
	 *@since 2019.04 富媒体编辑器仅在非ajax请求中有效
	 */
	if ($args['rich_media_editor']) {
		$form->add_post_content(true);
	} else {
		$form->add_post_content(false);
	}

	$form->add_checkbox(
		array(
			'name' => '_post_post_status',
			'value' => 'draft',
			'label' => '存为草稿',
			'class' => 'switch is-' . Wnd_Post_Form::$second_color,
		)
	);

	$form->add_hidden('_post_post_parent', $post_parent);

	$form->set_submit_button('保存');

	// 以当前函数名设置filter hook
	$form->set_filter(__FUNCTION__ . '_' . $post_type);

	$form->build();

	return $form->html;

}

/**
 *@since 2019.02.15
 *ajax请求获取文章信息
 */
function _wnd_post_info($args) {

	$defaults = array('post_id' => 0, 'color' => 'is-primay');
	$args = wp_parse_args($args, $defaults);

	$post = get_post($args['post_id']);
	if (!$post) {
		return '<script>wnd_alert_msg("ID无效！")</script>';
	}

	// 站内信阅读后，更新为已读 @since 2019.02.25
	if ($post->post_type == 'mail' and $post->post_type !== 'private') {
		wp_update_post(array('ID' => $post->ID, 'post_status' => 'private'));
	}

	$html = '<article class="message ' . $args['color'] . '">';
	$html .= '<div class="message-body">';

	if (!wnd_get_post_price($post->ID)) {
		$html .= $post->post_content;
	} else {
		$html .= "付费文章不支持预览！";
	}
	$html .= '</div>';
	$html .= '</article>';

	return $html;
}

/**
 *@since 2019.01.20 输出中文文章状态
 */
function _wnd_post_status($post_id) {

	if (!$post_id) {
		global $post;
	} else {
		$post = get_post($post_id);
	}

	if (!$post) {
		return "获取状态失败！";
	}

	switch ($post->post_status) {

	case 'publish':
		return "公开";
		break;

	case 'pending':
		return "待审";
		break;

	case 'draft':
		return "草稿";
		break;

	default:
		return $post->post_status;
		break;
	}

}

/**
 *@since 2019.01.20
 *快速编辑文章状态表单
 */
function _wnd_post_status_form($post_id) {

	$post = get_post($post_id);
	if (!$post) {
		return 'ID无效！';
	}

	switch ($post->post_status) {

	case 'publish':
		$status_text = '已发布';
		break;

	case 'pending':
		$status_text = '待审核';
		break;

	case 'draft':
		$status_text = '草稿';
		break;

	case false:
		$status_text = '已删除';
		break;

	default:
		$status_text = $post->post_status;
		break;
	}

	$form = new Wnd_Ajax_Form();
	$form->add_html('<div class="field is-grouped is-grouped-centered">');
	$form->add_html('<script>wnd_ajax_msg(\'当前： ' . $status_text . '\', \'is-danger\', \'#post-status\')</script>');
	$form->add_radio(
		array(
			'name' => 'post_status',
			'options' => array(
				'发布' => 'publish',
				'待审' => 'pending',
				'草稿' => 'draft',
				'删除' => 'delete',
			),
			'required' => 'required',
			'checked' => $post->post_status,
			'class' => 'is-checkradio is-danger',
		)
	);
	$form->add_html('</div>');

	if (wnd_is_manager()) {
		$form->add_textarea(
			array(
				'name' => 'remarks',
				'placeholder' => '备注（可选）',
			)
		);
	}

	if ($post->post_type == 'order') {
		$form->add_html('<div class="message is-danger"><div class="message-body">删除订单记录，不可退款，请谨慎操作！</div></div>');
	}

	$form->add_hidden('post_id', $post_id);
	$form->set_action('wnd_ajax_update_post_status');
	$form->set_form_attr('id="post-status"');
	$form->set_submit_button('提交');
	$form->build();
	return $form->html;

}

/**
 *@since 2019.02.27 获取WndWP文章缩略图
 */
function _wnd_post_thumbnail($post_id, $width, $height) {

	$post_id = $post_id ?: get_the_ID();

	if ($post_id) {
		$image_id = wnd_get_post_meta($post_id, '_thumbnail_id');
	}

	if ($image_id) {
		if ($width and $height) {
			return '<img class="thumbnail" src="' . wnd_get_thumbnail_url($image_id, $width, $height) . '" width="' . $width . '" height="' . $height . '">';
		} else {
			return '<img class="thumbnail" src="' . wp_get_attachment_url($image_id) . '">';
		}
	}

	return false;
}

/**
 *@since ≈2018.07
 *###################################################### 表单设置：标签编辑器
 */
function _wnd_get_tags_editor_script($maxTags = 3, $maxLength = 10, $placeholder = '标签', $taxonomy = '') {

	$html = '<script src="https://cdn.jsdelivr.net/npm/jquery-ui-dist@1.12.1/jquery-ui.min.js"></script>';
	$html .= '<script src="' . WND_URL . 'static/js/jquery.tag-editor.min.js"></script>';
	$html .= '<script src="' . WND_URL . 'static/js/jquery.caret.min.js"></script>';
	$html .= '<link rel="stylesheet" href="' . WND_URL . 'static/css/jquery.tag-editor.min.css">';
	$html .= '
<script>
jQuery(document).ready(function($) {
	$("#tags").tagEditor({
		//自动提示
		autocomplete: {
			delay: 0,
			position: {
				collision: "flip"
			},
			source: [' . _wnd_get_terms_text($taxonomy, 200) . ']
		},
		forceLowercase: false,
		placeholder: "' . $placeholder . '",
		maxTags: "' . $maxTags . '", //最多标签个数
		maxLength: "' . $maxLength . '", //单个标签最长字数
		onChange: function(field, editor, tags) {

		},
	});
});
</script>';

	return $html;

}

//###################################################################################
// 以文本方式列出热门标签，分类名称 用于标签编辑器，自动提示文字： 'tag1', 'tag2', 'tag3'
function _wnd_get_terms_text($taxonomy, $number) {

	$terms = get_terms($taxonomy, 'orderby=count&order=DESC&hide_empty=0&number=' . $number);
	if (!empty($terms)) {
		if (!is_wp_error($terms)) {
			$terms_list = '';
			foreach ($terms as $term) {
				$terms_list .= '\'' . $term->name . '\',';
			}

			// 移除末尾的逗号
			return rtrim($terms_list, ",");
		}
	}

}
