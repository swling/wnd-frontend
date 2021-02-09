<?php
namespace Wnd\Module;

/**
 *@since 0.9.0
 *Post 详情模块
 *
 */
class Wnd_Post_Detail extends Wnd_Module_Html {

	protected static function build($args = []): string{
		/**
		 *订单基本信息 + 产品属性等参数
		 *移除表单签名参数
		 */
		$defaults = [
			'post_id' => 0,
		];
		$args = wp_parse_args($args, $defaults);

		// 根据 post type 转发
		$post_id   = $args['post_id'];
		$post      = get_post($post_id);
		$post_type = $post->post_type ?? '';
		if (!$post_type) {
			return wnd_notification(__('ID 无效', 'wnd'));
		}

		$class = __NAMESPACE__ . '\Wnd_Post_Detail_' . $post_type;
		if (class_exists($class)) {
			return $class::build(['post_id' => $post_id, 'post' => $post]);
		} else {
			return static::build_post_detail($post);
		}
	}

	/**
	 *默认文章详情
	 *
	 */
	private static function build_post_detail(\WP_Post $post): string {
		if (!$post) {
			return __('ID无效', 'wnd');
		}

		// 站内信阅读后，更新为已读 @since 2019.02.25
		if ('mail' == $post->post_type and $post->post_type != 'wnd-read') {
			wp_update_post(['ID' => $post->ID, 'post_status' => 'wnd-read']);
		}

		if (wnd_get_post_price($post->ID)) {
			return wnd_message(__('付费文章不支持预览', 'wnd'));
		}

		// order recharge
		if (in_array($post->post_type, ['order', 'recharge'])) {
			$html = '<article>';
			$html .= '<h5>Title:' . $post->post_title . '</h5>';
			$html .= '<h5>ID:' . $post->ID . '</h5>';
			$html .= '<h5>Total Amount:' . $post->post_content . '</h5>';
			$html .= '<h5>Refund Count:' . (wnd_get_post_meta($post->ID, 'refund_count') ?: 0) . '</h5>';
			$html .= '<h5>payment method:' . $post->post_excerpt . '</h5>';

			$refund_records = wnd_get_post_meta($post->ID, 'refund_records');
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
