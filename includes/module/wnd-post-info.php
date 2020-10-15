<?php
namespace Wnd\Module;

/**
 *@since 2019.02.15
 *获取文章信息
 */
class Wnd_Post_Info extends Wnd_Module {

	protected static function build($args = []): string{
		$post = $args['post_id'] ? get_post($args['post_id']) : false;
		if (!$post) {
			return __('ID无效', 'wnd');
		}

		// 站内信阅读后，更新为已读 @since 2019.02.25
		if ('mail' == $post->post_type and $post->post_type != 'read') {
			wp_update_post(['ID' => $post->ID, 'post_status' => 'read']);
		}

		if (wnd_get_post_price($post->ID)) {
			return static::build_message(__('付费文章不支持预览', 'wnd'));
		}

		// order recharge
		if (in_array($post->post_type, ['order', 'recharge'])) {
			$html = '<article>';
			$html .= '<h5>Title:' . $post->post_title . '</h5>';
			$html .= '<h5>ID:' . $post->ID . '</h5>';
			$html .= '<h5>Total Amount:' . $post->post_content . '</h5>';
			$html .= '<h5>Refund Count:' . (wnd_get_post_meta($args['post_id'], 'refund_count') ?: 0) . '</h5>';
			$html .= '<h5>payment method:' . $post->post_excerpt . '</h5>';

			$refund_records = wnd_get_post_meta($args['post_id'], 'refund_records');
			$refund_records = is_array($refund_records) ? $refund_records : [];
			foreach ($refund_records as $record) {
				$html .= '<li>' . $record['refund_amount'] . '-' . $record['time'] . '-' . $record['user_id'] . '</li>';
			}
			$html .= '</ul>';
			$html .= '</article>';
			return $html;
		}

		// content
		$html = '<article>';
		$html .= $post->post_content;
		$html .= '</article>';
		return $html;
	}
}
