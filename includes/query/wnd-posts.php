<?php
namespace Wnd\Query;

use Wnd\View\Wnd_Filter_Ajax;

/**
 * posts 查询接口
 * @since 0.9.59.1 从独立 rest api 接口移植入 Wnd_Query
 *
 * 多重筛选 API
 * 常规情况下，controller 应将用户请求转为操作命令并调用 model 处理，但 Wnd\View\Wnd_Filter 是一个完全独立的功能类
 * Wnd\View\Wnd_Filter 既包含了生成筛选链接的视图功能，也包含了根据请求参数执行对应 WP_Query 并返回查询结果的功能，且两者紧密相关不宜分割
 * 可以理解为，Wnd\View\Wnd_Filter 是通过生成一个筛选视图，发送用户请求，最终根据用户请求，生成新的视图的特殊类：视图<->控制<->视图
 * @since 2019.07.31
 * @since 2019.10.07 OOP改造
 *
 * @param $request
 */
class Wnd_Posts extends Wnd_Query {

	protected static function query($args = []): array{
		$filter = new Wnd_Filter_Ajax(true);
		$filter->query();

		return $filter->get_results();
	}

}
