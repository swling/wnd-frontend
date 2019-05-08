<?php
/**
 *@since 2019.05.05
 *gallery 相册展示
 *@param $post_id 			int 		相册所附属的文章ID，若为0，则查询当前用户字段
 *@param $thumbnail_width 	number 		缩略图宽度
 *@param $thumbnail_height 	number 		缩略图高度
 **/
function _wnd_gallery($post_id, $thumbnail_width = 160, $thumbnail_height = 120) {

	$images = $post_id ? wnd_get_post_meta($post_id, 'gallery') : wnd_get_user_meta(get_current_user_id(), 'gallery');
	if (!$images) {
		return '没有图集';
	}

	// 遍历输出图片集
	$html = '<div class="gallery columns is-vcentered has-text-centered">';
	foreach ($images as $key => $attachment_id) {

		$attachment_url = _wnd_get_thumbnail_url($attachment_id, $thumbnail_width, $thumbnail_height);
		if (!$attachment_url) {
			unset($images[$key]); // 在字段数据中取消已经被删除的图片
			continue;
		}

		$html .= '<div id="img' . $attachment_id . '" class="column is-narrow">';
		$html .= '<a><img class="thumbnail" src="' . $attachment_url . '" height="' . $thumbnail_height . '" width="' . $thumbnail_width . '"></a>';
		$html .= '</div>';

	}
	unset($key, $attachment_id);
	wnd_update_post_meta($post_id, 'gallery', $images); // 若字段中存在被删除的图片数据，此处更新
	$html .= '</div>';

	return $html;

}

/**
 *@since 2019.05.08 获取图像缩略图
 *需要将图像存储在阿里云oss，并利用filter对wp_get_attachment_url重写为阿里oss地址
 *阿里云的图片处理
 *@link https://help.aliyun.com/document_detail/44688.html
 */
function _wnd_get_thumbnail_url($attachment_id, $width = 160, $height = 120) {

	return wp_get_attachment_url($attachment_id) . '?x-oss-process=image/resize,m_fill,w_' . $width . ',h_' . $height;

}
