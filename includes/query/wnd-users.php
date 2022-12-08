<?php
namespace Wnd\Query;

use Wnd\View\Wnd_Filter_User;

/**
 * User 筛选 API
 * @since 2020.05.05
 * @since 0.9.59.1 从独立 rest api 接口移植入 Wnd_Query
 *
 * @param $request
 */
class Wnd_Users extends Wnd_Query {

	protected static function query($args = []): array{
		$filter = new Wnd_Filter_User(true);
		$filter->query();
		return $filter->get_results();
	}

}
