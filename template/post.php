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

	/**
	 *@since 2019.3.11 调用外部页面变量，后续更改为当前编辑的post，否则，wp_editor上传的文件将归属到页面，而非当前编辑的文章
	 */
	global $post;

	$defaults = array(
		'post_id' => 0,
		'post_type' => 'post',
		'post_parent' => 0,
		'free' => 1,
		'excerpt' => 0,
		'thumbnail' => 0, //0 无缩略图，1、存储在wnd_meta _thumbnail_id字段: _wnd_the_post_thumbnail($width = 0, $height = 0)
		'thumbnail_size' => array('width' => 150, 'height' => 150),
		'rich_media_editor' => 1,
	);
	$args = wp_parse_args($args, $defaults);
	$post_id = $args['post_id'];
	$post_type = $args['post_type'];

	// 未指定id，新建文章，否则为编辑
	if (!$post_id) {
		$action = wnd_get_draft_post($post_type = $post_type, $interval_time = 3600 * 24);
		$post_id = $action['status'] > 0 ? $action['msg'] : 0;
	}

	// 获取文章并定义数据
	$post = get_post($post_id);
	if ($post) {
		$post_type = $post->post_type;

		//新建文章失败
	} else {

		// 已知用户创建失败，说明产生了错误，退出
		if (is_user_logged_in()) {
			echo "<script>wnd_alert_msg('" . $action['msg'] . "')</script>";
			return;
		}
		// 匿名用户不允许创建草稿，上传，因而初始化一个空对象
		$post = new stdClass();
		$post->post_title = '';
		$post->post_excerpt = '';
		$post->post_content = '';
	}

	/**
	 *@since 2019.02.13 表单标题
	 **/
	if (!isset($args['form_title'])) {
		$args['form_title'] = $post_id ? '<span class="icon"><i class="fa fa-edit"></i></span>ID: ' . $post_id : '';
	} elseif (!empty($args['form_title'])) {
		$args['form_title'] = '<span class="icon"><i class="fa fa-edit"></i></span>' . $args['form_title'];
	}

	/**
	 *@since 2019.02.01
	 *获取指定 post type的所有注册taxonomy
	 */
	$cat_taxonomies = array();
	$tag_taxonomies = array();
	$taxonomies = get_object_taxonomies($post_type, $output = 'names');
	if ($taxonomies) {
		foreach ($taxonomies as $taxonomy) {
			if (is_taxonomy_hierarchical($taxonomy)) {
				array_push($cat_taxonomies, $taxonomy);
			} else {
				array_push($tag_taxonomies, $taxonomy);
			}
		}
		unset($taxonomy);
	}

	if (!wp_doing_ajax()) {
		wnd_tags_editor($maxTags = 3, $maxLength = 10, $placeholder = '标签', $taxonomy = $post_type . '_tag', $initialTags = '');
	}

	/**
	 *@since 2019.03.11 表单类
	 */
	$form = new Wnd_Post_Form();

	$form->set_form_attr('id="post-form-' . $post_id . '" onkeydown="if(event.keyCode==13){return false;}"');
	$form->set_form_title($args['form_title']);
	$form->add_post_title($post->post_title == 'Auto-draft' ? '' : $post->post_title);
	$form->add_post_excerpt($post->post_excerpt);

	// 遍历分类
	if ($cat_taxonomies) {
		$form->add_html('<div class="field is-horizontal"><div class="field-body">');
		foreach ($cat_taxonomies as $cat_taxonomy) {
			$form->add_post_category_select($cat_taxonomy, $post_id);
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
			$form->add_post_tag($tag_taxonomy, $post_id);

		}
		unset($tag_taxonomy);
	}

	if ($args['thumbnail']) {
		$form->add_post_thumbnail($post_id, $size = 200);
	}

	if (!$args['free']) {
		$form->add_post_file($post_id, $meta_key = 'file');
		$form->add_post_price($post_id);
	}

	/**
	 *@since 2019.03.11 wp_editor无法使用表单类创建，此处生成一个隐藏编辑器，再用js嵌入到指定DOM
	 */
	if ($args['rich_media_editor']) {
		echo '<div id="hidden-wp-editor" style="display: none;">';
		if (isset($post)) {
			$post = $post;
			wp_editor($post->post_content, '_post_post_content', 'media_buttons=1');
		} else {
			wp_editor($post->post_content, '_post_post_content', 'media_buttons=0');
		}
		echo '</div>';
		$form->add_post_content(1);
	} else {
		$form->add_post_content(0);
	}

	$form->add_checkbox(
		array(
			'name' => '_post_post_status',
			'value' => 'draft',
			'label' => '存为草稿',
		)
	);

	$form->set_action('wnd_insert_post');

	$form->add_hidden('_post_post_type', $post_type);
	$form->add_hidden('_post_post_id', $post_id);
	$form->add_hidden('_post_post_parent', $args['post_parent']);

	$form->set_submit_button('保存');

	// 以当前函数名设置filter hook
	$form->set_filter(__FUNCTION__);

	$form->build();

	echo $form->html;

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
		echo '<script>wnd_alert_msg("ID无效！")</script>';
		return;
	}

	// 站内信阅读后，更新为已读 @since 2019.02.25
	if ($post->post_type == 'mail' and $post->post_type !== 'private') {
		wp_update_post(array('ID' => $post->ID, 'post_status' => 'private'));
	}

	?>
<article class="message <?php echo $args['color']; ?>">
	<div class="message-body">
		<?php
if (!wnd_get_post_price($post->ID)) {
		echo $post->post_content;
	} else {
		echo "付费文章不支持预览！";
	}
	?>
	</div>
</article>
<?php

}

/**
 *@since 2019.01.20
 *快速编辑文章状态表单
 */
function _wnd_post_status_form($post_id) {

	$post_status = get_post_status($post_id);
	switch ($post_status) {

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
		$status_text = $post_status;
		break;
	}

	?>
<form id="post-status" action=""  data-submit-type="ajax" method="post" onsubmit="return false">
	<div class="field">
		<div class="ajax-msg"></div>
	</div>
	<div class="field text-centered">
		<label class="radio">
			<input type="radio" required="required" class="radio" name="post_status" value="publish" <?php if ($post_status == 'publish') {
		echo ' checked="checked" ';
	}
	?>>
			发布
		</label>

		<label class="radio">
			<input type="radio" required="required" class="radio" name="post_status" value="draft" <?php if ($post_status == 'draft') {
		echo ' checked="checked" ';
	}
	?>>
			草稿
		</label>

		<label class="radio">
			<input type="radio" required="required" class="radio" name="post_status" value="delete">
			<span class="is-danger">删除</span>
		</label>
	</div>
	<?php if (wnd_is_manager()) {?>
	<div class="field">
		<textarea name="remarks" class="textarea" placeholder="备注（可选）"></textarea>
	</div>
	<?php }?>
	<input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
	<?php wp_nonce_field('wnd_update_post_status', '_ajax_nonce');?>
	<input type="hidden" name="action" value="wnd_action">
	<input type="hidden" name="action_name" value="wnd_update_post_status">
	<div class="field is-grouped is-grouped-centered">
		<button type="button" name="submit" class="button" onclick="wnd_ajax_submit('#post-status')">确认</button>
	</div>
</form>
<script>
wnd_ajax_msg("<?php echo '当前： ' . $status_text; ?>", "is-danger", "#post-status")
</script>
<?php

}

/**
 *@since 2019.01.30 付费内容表单字段
 */
function _wnd_paid_post_field($post_parent) {

	$args = array(
		'id' => 'upload-file-' . $post_parent,
		'meta_key' => 'file',
		'thumbnail' => 0,
		'post_parent' => $post_parent,
	);
	_wnd_upload_field($args);

	?>
<div class="field">
	<div class="control has-icons-left">
		<input type="number" min="0" step="0.01" class="input" value="<?php echo get_post_meta($post_parent, 'price', 1) ?>" name="_wpmeta_price" placeholder="价格">
		<span class="icon is-left">
			<i class="fas fa-yen-sign"></i>
		</span>
	</div>
</div>
<?php
}

/**
 *@since 2019.02.27 获取WndWP文章缩略图
 */
function _wnd_the_post_thumbnail($width = 0, $height = 0) {

	global $post;

	if ($post->ID) {
		$image_id = wnd_get_post_meta($post->ID, '_thumbnail_id');
	}

	if ($image_id) {
		if ($width and $height) {
			echo '<img src="' . wp_get_attachment_url($image_id) . '" width="' . $width . '" height="' . $height . '"  >';
		} else {
			echo '<img src="' . wp_get_attachment_url($image_id) . '" >';
		}
	}
}

/**
 *@since ≈2018.07
 *###################################################### 表单设置：标签编辑器
 */
function wnd_tags_editor($maxTags = 3, $maxLength = 10, $placeholder = '标签', $taxonomy = '', $initialTags = '') {

	?>
<!--jquery标签编辑器 Begin-->
<script src="<?php echo WNDWP_URL . 'static/js/jquery.tag-editor.js' ?>"></script>
<script src="<?php echo WNDWP_URL . 'static/js/jquery.caret.min.js' ?>"></script>
<link rel="stylesheet" href="<?php echo WNDWP_URL . 'static/css/jquery.tag-editor.css' ?>">
<script>
jQuery(document).ready(function($) {
	$('#tags').tagEditor({
		//自动提示
		autocomplete: {
			delay: 0,
			position: {
				collision: 'flip'
			},
			source: [<?php if ($taxonomy) {
		wnd_terms_text($taxonomy, 100);
	}
	?>]
			// source: ['ActionScript', 'AppleScript', 'Asp', 'BASIC']  //demo
		},
		forceLowercase: false,
		placeholder: '<?php echo $placeholder; ?>',
		maxTags: '<?php echo $maxTags; ?>', //最多标签个数
		maxLength: '<?php echo $maxLength; ?>', //单个标签最长字数
		onChange: function(field, editor, tags) {
			// alert("变了");
		},
		// 预设标签
		initialTags: [<?php if ($initialTags) {
		echo $initialTags;
	}
	?>],
		// initialTags: ['ActionScript', 'AppleScript', 'Asp', 'BASIC'], //demo
	});
});
</script>
<?php

}

// ###################################################################################
// 以文本方式输出当前文章标签、分类名称 主要用于前端编辑器输出形式： tag1, tag2, tag3
function wnd_post_terms_text($post_id, $taxonomy) {
	$terms = wp_get_object_terms($post_id, $taxonomy);
	if (!empty($terms)) {
		if (!is_wp_error($terms)) {
			$terms_list = '';
			foreach ($terms as $term) {
				$terms_list .= $term->name . ',';
			}

			// 移除末尾的逗号
			echo rtrim($terms_list, ",");
		}
	}
}

//###################################################################################
// 以文本方式列出热门标签，分类名称 用于标签编辑器，自动提示文字： 'tag1', 'tag2', 'tag3'
function wnd_terms_text($taxonomy, $number) {

	$terms = get_terms($taxonomy, 'orderby=count&order=DESC&hide_empty=0&number=' . $number);
	if (!empty($terms)) {
		if (!is_wp_error($terms)) {
			$terms_list = '';
			foreach ($terms as $term) {
				$terms_list .= '\'' . $term->name . '\',';
			}

			// 移除末尾的逗号
			echo rtrim($terms_list, ",");
		}
	}

}