<?php

/**
 *@since 2019.04.01
 *独立的ajax图像上传
 */
function _wnd_image_upload($args) {

	$defaults = array(
		'id' => 'image-upload-' . uniqid(),
		'label' => '',
		'thumbnail' => WNDWP_URL . '/static/images/default.jpg', //默认缩略图
		'thumbnail_size' => array('height' => '100', 'width' => '100'),
		'data' => array(
			'post_parent' => 0,
			'meta_key' => 0,
			'save_width' => 0,
			'save_height' => 0,
		),
		'delete_button' => true,
	);
	$args = array_merge($defaults, $args);

	// 合并$data
	$data = array(
		'is_image' => '1',
		'thumbnail' => $args['thumbnail'],
		'upload_nonce' => wp_create_nonce('wnd_upload_file'),
		'delete_nonce' => wp_create_nonce('wnd_delete_file'),
		'post_parent' => 0,
		'meta_key' => 0,
	);
	$args['data'] = array_merge($data, $args['data']);

	// 根据user type 查找目标文件
	$file_id = $args['data']['post_parent'] ? wnd_get_post_meta($args['data']['post_parent'], $args['data']['meta_key']) : wnd_get_user_meta(get_current_user_id(), $args['data']['meta_key']);
	$file_url = $file_id ? wp_get_attachment_url($file_id) : '';

	// 如果字段存在，但文件已不存在，例如已被后台删除，删除对应meta key
	if ($file_id and !$file_url) {
		if ($args['data']['post_parent']) {
			wnd_delete_post_meta($args['data']['post_parent'], $args['data']['meta_key']);
		} else {
			wnd_delete_user_meta(get_current_user_id(), $args['data']['meta_key']);
		}
	}

	$args['name'] = 'file';
	$args['thumbnail'] = $file_url ?: $args['thumbnail'];
	$args['file_id'] = $file_id ?: 0;

	// 构建html
	$data = ' data-id="' . $args['id'] . '"';
	foreach ($args['data'] as $key => $value) {
		$data .= ' data-' . $key . '="' . $value . '" ';
	}
	unset($key, $value);

	$html = '<div id="' . $args['id'] . '" class="field upload-field">';
	if ($args['label']) {
		$html .= '<label class="label">' . $args['label'] . '</label>';
	}
	$html .= '<div class="field"><div class="ajax-msg"></div></div>';

	$html .= '<div class="field">';
	$html .= '<a><img class="thumbnail" src="' . $args['thumbnail'] . '" height="' . $args['thumbnail_size']['height'] . '" width="' . $args['thumbnail_size']['height'] . '"></a>';
	$html .= $args['delete_button'] ? '<a class="delete" data-id="' . $args['id'] . '" data-file_id="' . $args['file_id'] . '"></a>' : '';
	$html .= '<div class="file">';
	$html .= '<input type="file" class="file-input" name="' . $args['name'] . '[]' . '"' . $data . 'accept="image/*" >';
	$html .= '</div>';
	$html .= '</div>';

	$html .= '
		<script type="text/javascript">
			var fileupload = document.querySelector("#' . $args['id'] . ' input[type=\'file\']");
			var image = document.querySelector("#' . $args['id'] . ' .thumbnail");
			image.onclick = function () {
			    fileupload.click();
			};
		</script>';

	$html .= '</div>';
	return $html;
}

/**
 *@since 2019.04.01
 *独立的ajax文件上传
 */
function _wnd_file_upload($args) {

	$defaults = array(
		'id' => 'file-upload-' . uniqid(),
		'label' => 'File upload',
		'data' => array('post_parent' => 0, 'meta_key' => 0),
		'delete_button' => true,
	);
	$args = array_merge($defaults, $args);

	$data = array(
		'upload_nonce' => wp_create_nonce('wnd_upload_file'),
		'delete_nonce' => wp_create_nonce('wnd_delete_file'),
		'post_parent' => 0,
		'meta_key' => 0,
	);
	$args['data'] = array_merge($data, $args['data']);

	// 根据meta key 查找目标文件
	$file_id = $args['data']['post_parent'] ? wnd_get_post_meta($args['data']['post_parent'], $args['data']['meta_key']) : wnd_get_user_meta(get_current_user_id(), $args['data']['meta_key']);
	$file_url = $file_id ? wp_get_attachment_url($file_id) : '';

	// 如果字段存在，但文件已不存在，例如已被后台删除，删除对应meta key
	if ($file_id and !$file_url) {
		if ($args['data']['post_parent']) {
			wnd_delete_post_meta($args['data']['post_parent'], $args['data']['meta_key']);
		} else {
			wnd_delete_user_meta(get_current_user_id(), $args['data']['meta_key']);
		}
	}

	$args['name'] = 'file';
	$args['file_id'] = $file_id ?: 0;
	$args['file_name'] = $file_url ? '<a href="' . $file_url . '">查看文件</a>' : '……';

	// 构建html
	$data = ' data-id="' . $args['id'] . '"';
	foreach ($args['data'] as $key => $value) {
		$data .= ' data-' . $key . '="' . $value . '" ';
	}
	unset($key, $value);

	$html = '<div id="' . $args['id'] . '" class="field upload-field">';

	$html .= '<div class="field"><div class="ajax-msg"></div></div>';
	$html .= '<div class="columns is-mobile">';

	$html .= '<div class="column">';
	$html .= '<div class="file has-name is-fullwidth">';
	$html .= '<label class="file-label">';
	$html .= '<input type="file" class="file-input" name="' . $args['name'] . '[]' . '"' . $data . '>';
	$html .= '<span class="file-cta">';
	$html .= '<span class="file-icon"><i class="fa fa-upload"></i></span>';
	$html .= '<span class="file-label">' . $args['label'] . '</span>';
	$html .= '</span>';
	$html .= '<span class="file-name">' . $args['file_name'] . '</span>';
	$html .= '</label>';
	$html .= '</div>';
	$html .= '</div>';

	if ($args['delete_button']) {
		$html .= '<div class="column is-narrow">';
		$html .= '<a class="delete" data-id="' . $args['id'] . '" data-file_id="' . $args['file_id'] . '"></a>';
		$html .= '</div>';
	}

	$html .= '</div>';
	$html .= '</div>';
	return $html;
}