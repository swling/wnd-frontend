<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Filter;

/**
 *@since 2019.02.19 封装前端当前用户站内信
 *@param $posts_per_page 每页列表数目
 */
class Wnd_Mail_Box extends Wnd_Module {

	public static function build(int $posts_per_page = 0) {
		if (!is_user_logged_in()) {
			return '<div class="message is-warning"><div class="message-body">请登录！</div></div>';
		}
		$posts_per_page = $posts_per_page ?: get_option('posts_per_page');

		$filter = new Wnd_Filter(true);
		$filter->add_post_type_filter(array('mail'));
		$filter->add_post_status_filter(array('全部' => 'any', '未读' => 'pending', '已读' => 'private'));
		$filter->add_query(array('author' => get_current_user_id()));
		$filter->set_posts_template('wnd_mail_posts_tpl');
		$filter->set_posts_per_page($posts_per_page);
		$filter->set_ajax_container('#user-mail-panel');
		$filter->query();
		return $filter->get_tabs() . '<div id="user-mail-panel">' . $filter->get_results() . '</div>';

	}
}
