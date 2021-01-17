<?php
namespace Wnd\Route;

/**
 *@since 2019.08.30
 *生成二维码图像
 */
class Wnd_Test extends Wnd_Route {

	protected function do() {
		echo 'Files:' . count(get_included_files()) . '- Queries:' . get_num_queries() . ' - ' . timer_stop() . '-' . number_format(memory_get_peak_usage() / 1024 / 1024, 2);
	}
}
