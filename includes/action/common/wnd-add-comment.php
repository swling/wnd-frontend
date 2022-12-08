<?php
namespace Wnd\Action\Common;

use Wnd\Action\Wnd_Action;

/**
 * 新增评论
 * @since 0.9.59.1
 * 从 Rest API 移植入 Action 层
 *
 */
class Wnd_Add_Comment extends Wnd_Action {

	protected $verify_sign = false;

	protected function execute(): array{
		// 此处插件可能通过 comment 相关 hook 抛出异常，如验证码
		$comment = wp_handle_comment_submission(wp_unslash($this->wp_rest_request->get_params()));
		if (is_wp_error($comment)) {
			return ['status' => 0, 'msg' => $comment->get_error_message()];
		}

		$user = wp_get_current_user();
		do_action('set_comment_cookies', $comment, $user);
		$GLOBALS['comment'] = $comment;

		// 此结构可能随着WordPress wp_list_comments()输出结构变化而失效
		$html = '<li class="' . implode(' ', get_comment_class()) . '">';
		$html .= '<article class="comment-body">';
		$html .= '<footer class="comment-meta">';
		$html .= '<div class="comment-author vcard">';
		$html .= get_avatar($comment, '56');
		$html .= '<b class="fn">' . get_comment_author_link() . '</b>';
		$html .= '</div>';
		$html .= '<div class="comment-metadata">' . get_comment_date('', $comment) . ' ' . get_comment_time() . '</div>';
		$html .= '</footer>';
		$html .= '<div class="comment-content">' . get_comment_text() . '</div>';
		$html .= '</article>';
		$html .= '</li>';

		return ['status' => 1, 'msg' => '提交成功', 'data' => $html];
	}

}
