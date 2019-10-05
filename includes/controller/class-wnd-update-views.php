<?php
namespace Wnd\Controller;

/**
 *@since 2019.01.16
 *@param $_GET['post_id']
 *@param $_GET['useragent']
 */
class Wnd_Update_Views extends Wnd_Ajax_Controller {

	public static function execute() {
		$post_id = (int) $_GET['param'];
		if (!$post_id) {
			return;
		}
		$useragent    = $_GET['useragent'];
		$should_count = true;

		// 根据 useragent 排除搜索引擎
		$bots = array(
			'Google Bot' => 'google'
			, 'MSN' => 'msnbot'
			, 'Alex' => 'ia_archiver'
			, 'Lycos' => 'lycos'
			, 'Ask Jeeves' => 'jeeves'
			, 'Altavista' => 'scooter'
			, 'AllTheWeb' => 'fast-webcrawler'
			, 'Inktomi' => 'slurp@inktomi'
			, 'Turnitin.com' => 'turnitinbot'
			, 'Technorati' => 'technorati'
			, 'Yahoo' => 'yahoo'
			, 'Findexa' => 'findexa'
			, 'NextLinks' => 'findlinks'
			, 'Gais' => 'gaisbo'
			, 'WiseNut' => 'zyborg'
			, 'WhoisSource' => 'surveybot'
			, 'Bloglines' => 'bloglines'
			, 'BlogSearch' => 'blogsearch'
			, 'PubSub' => 'pubsub'
			, 'Syndic8' => 'syndic8'
			, 'RadioUserland' => 'userland'
			, 'Gigabot' => 'gigabot'
			, 'Become.com' => 'become.com'
			, 'Baidu' => 'baiduspider'
			, 'so.com' => '360spider'
			, 'Sogou' => 'spider'
			, 'soso.com' => 'sosospider'
			, 'Yandex' => 'yandex',
		);

		foreach ($bots as $name => $lookfor) {
			if (!empty($useragent) and (stristr($useragent, $lookfor) !== false)) {
				$should_count = false;
				break;
			}
		}

		// 统计
		if ($should_count) {
			if (wnd_inc_post_meta($post_id, 'views', 1)) {
				// 完成统计时附加动作
				do_action('wnd_update_views', $post_id);
				// 统计更新成功
				return array('status' => 1, 'msg' => time());

				//字段写入失败，清除对象缓存
			} else {
				wp_cache_delete($post_id, 'post_meta');
			}

		} else {
			// 未更新
			return array('status' => 0, 'msg' => time());
		}
	}

}
