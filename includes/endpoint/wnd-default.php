<?php
namespace Wnd\Endpoint;

/**
 * 测试专用
 * @since 0.9.17
 */
class Wnd_Default extends Wnd_Endpoint {

	protected $content_type = 'html';

	protected function do() {
		echo 'Files: ' . count(get_included_files())
		. ' - Queries: ' . get_num_queries()
		. ' - Time: ' . timer_stop()
		. ' - Memory: ' . number_format(memory_get_peak_usage() / 1024 / 1024, 2)
		. ' - User: ' . get_current_user_id();
		echo '</br>' . $GLOBALS['wp_query']->request;
	}
}
