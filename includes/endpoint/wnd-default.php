<?php
namespace Wnd\Endpoint;

/**
 * 测试专用
 * @since 0.9.17
 */
class Wnd_Default extends Wnd_Endpoint {

	protected $content_type = 'json';

	protected function do() {
		$info = [
			'Files'   => count(get_included_files()),
			'Queries' => get_num_queries(),
			'Time'    => timer_stop(),
			'Mem'     => number_format(memory_get_peak_usage() / 1024 / 1024, 2),
			'User'    => get_current_user_id(),
			'request' => $GLOBALS['wp_query']->request,
			'cookie'  => $_COOKIE,
			'headers' => getallheaders(),
			'is_rest' => wnd_is_rest_request(),
		];

		echo json_encode($info);
	}
}
