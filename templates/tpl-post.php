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

	$form = new Wnd_WP_Form();
	$form->add_html('<div class="field is-grouped is-grouped-centered">');
	$form->add_html('<script>wnd_ajax_msg(\'当前： ' . $status_text . '\', \'is-danger\', \'#post-status\')</script>');
	$form->add_radio(
		array(
			'name' => 'post_status',
			'options' => array(
				'发布' => 'publish',
				'待审' => 'pending',
				'关闭' => 'close',
				'草稿' => 'draft',
				'删除' => 'delete',
			),
			'required' => 'required',
			'checked' => $post->post_status,
			'class' => 'is-checkradio is-danger',
		)
	);
	$form->add_html('</div>');

	// 管理员权限
	if (wnd_is_manager()) {

		// 公开的post type可设置置顶
		if (in_array($post->post_type, get_post_types(array('public' => true)))) {
			$form->add_html('<div class="field is-grouped is-grouped-centered">');
			$form->add_radio(
				array(
					'name' => 'stick_post',
					'options' => array(
						'置顶' => 'stick',
						'取消' => 'unstick',
					),
					'checked' => (array_search($post->ID, wnd_get_sticky_posts($post->post_type)) === false) ? '' : 'stick',
					'class' => 'is-checkradio is-danger',
				)
			);
			$form->add_html('</div>');
		}

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
 *@since 2019.07.16
 *上传或编辑附件信息
 *指定$args['attachment_id'] 表示为编辑
 *
 *原理：
 *基于 post parent创建文件上传字段，ajax上传附件并附属到指定post parent
 *attachment post在上传文件后，由WordPress创建
 *后端将附件文件attachment post信息返回
 *@see php: wnd_ajax_upload_file()
 *
 *创建父级文件上传字段的同时，创建空白的attachment post form（实际表单是通过这两个表单的字段重新形成）
 *利用JavaScript捕获上传文件后返回的attachment post信息
 *JavaScript捕获新上传的attachment post信息后，首先判断当前表单对应字段是否已有信息，若有值，则不作修改。ID除外。
 *完成对表单字段信息的动态替换后，自动提交一次
 *若需修改信息，则编辑对应字段，手动提交一次
 *@see JavaScript: wnd_ajax_upload_file()
 *
 *文件替换：
 *指定attachment_id，并调用本函数，为防止上传附件后忘记删除原有文件（操作直观上，这是一次替换），此时文件字段为禁用状态
 *删除原有文件后，前端恢复上传
 *选择新的文件，则重复上述ajax文件上传过程，即此时表单已经动态更改为编辑最新上传的attachment post
 *通过保留相同的post_name(别名)、及menu_order（排序）可实现用户端的无缝替换文件。
 *本质上，替换文件，是删除后的新建，是全新的attachment post
 *
 */
function _wnd_attachment_form($args) {

	$defaults = array(
		'attachment_id' => 0,
		'post_parent' => 0,
		'meta_key' => null,
	);
	$args = wp_parse_args($args, $defaults);

	$attachment_id = $args['attachment_id'];
	$post_parent = $attachment_id ? get_post($attachment_id)->post_parent : $args['post_parent'];

	/**
	 * 构建父级表单字段，以供文件ajax上传归属到父级post
	 */
	$parent_post_form = new Wnd_WP_Form();

	// 文件上传字段可能被前端设置disabled属性，导致无法通过表单一致性校验，故此设置同名固定隐藏字段
	$parent_post_form->add_hidden('wnd_file', '');
	$parent_post_form->add_file_upload(
		array(
			'label' => '附件上传',
			'disabled' => $attachment_id ? 'disabled' : false,
			'file_id' => $attachment_id,

			/**
			 *如果设置了meta_key及post parent, 则上传的附件id将保留在对应的wnd_post_meta
			 *若仅设置了meta_key否则保留为 wnd_user_meta
			 *若未设置meta_key、则不在meta中保留附件信息，仅能通过指定id方式查询
			 */
			'data' => array(
				'meta_key' => $args['meta_key'],
				'post_parent' => $post_parent,
			),
		)
	);

	/**
	 *上传媒体信息表单字段。attachment 无法也不应创建草稿
	 *此处的attachment post_ID将根据上传文件后，ajax返回值获取
	 */
	$attachment_post_form = new Wnd_Post_Form('attachment', $attachment_id, false);
	if ($attachment_id) {
		$attachment_post_form->set_message('<div class="message is-' . Wnd_WP_Form::$second_color . '"><div class="message-body">如需更改文件，请先删除后重新选择文件！</div></div>');
	}
	$attachment_post_form->add_post_title('文件名称');
	$attachment_post_form->add_html('<div class="field is-horizontal"><div class="field-body">');
	$attachment_post_form->add_post_menu_order('排序', "输入排序");
	$attachment_post_form->add_text(
		array(
			'label' => '文件ID',
			'name' => '_post_ID',
			'value' => $attachment_id,
			'disabled' => true,
		)
	);
	$attachment_post_form->add_html('</div></div>');
	$attachment_post_form->add_post_name('链接别名', '附件的固定链接别名');
	$attachment_post_form->add_post_content(true, false, '简介 *');
	$attachment_post_form->set_submit_button("保存");

	// 将上述两个表单字段，合并组成一个表单字段
	$input_values = array_merge($parent_post_form->get_input_values(), $attachment_post_form->get_input_values());
	$attachment_post_form->set_input_values($input_values);
	$attachment_post_form->build();

	return $attachment_post_form->html;

}
