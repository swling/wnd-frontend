<?php
namespace Wnd\Endpoint;

/**
 *@since 0.9.17
 *测试专用
 */
class Wnd_Default extends Wnd_Endpoint {

	protected $content_type = 'html';

	protected function do() {
		echo 'Files: ' . count(get_included_files())
		. '- Queries: ' . get_num_queries()
		. ' - Time: ' . timer_stop()
		. ' - Memory: ' . number_format(memory_get_peak_usage() / 1024 / 1024, 2);
		echo '</br>' . $GLOBALS['wp_query']->request;
	}
}
