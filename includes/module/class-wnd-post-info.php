<?php
namespace Wnd\Module;

/**
 *@since 2019.02.15
 *获取文章信息
 */
class Wnd_Post_Info extends Wnd_Module {

	public static function build($post_id = 0) {
		$post = $post_id ? get_post($post_id) : false;
		if (!$post) {
			return 'ID无效';
		}

		// 站内信阅读后，更新为已读 @since 2019.02.25
		if ($post->post_type == 'mail' and $post->post_type !== 'private') {
			wp_update_post(['ID' => $post->ID, 'post_status' => 'private']);
		}

		$html = '<article class="message is-' . wnd_get_option('wnd', 'wnd_second_color') . '">';
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
}
