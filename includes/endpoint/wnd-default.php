<?php
namespace Wnd\Endpoint;

use Wnd\Controller\Wnd_Defender_Action;

/**
 * 测试专用
 * @since 0.9.17
 */
class Wnd_Default extends Wnd_Endpoint {

	// 注册用户需设置防抖，防止用户短期重复提交写入
	public $period      = 10;
	public $max_actions = 3;

	protected $content_type = 'json';

	protected function do() {
		$defender = new Wnd_Defender_Action($this);

		$info = [
			'Files'    => count(get_included_files()),
			'Queries'  => get_num_queries(),
			'Time'     => timer_stop(),
			'Mem'      => number_format(memory_get_peak_usage() / 1024 / 1024, 2),
			'User'     => get_current_user_id(),
			'IP'       => wnd_get_user_ip(),
			'request'  => $GLOBALS['wp_query']->request,
			'cookie'   => $_COOKIE,
			'headers'  => getallheaders(),
			'is_rest'  => wnd_is_rest_request(),
			'defender' => $defender->get_actions_log(),
		];

		echo json_encode($info);
	}
}
