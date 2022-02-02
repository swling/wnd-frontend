<?php
namespace Wnd\Admin;

use Wnd\Utility\Wnd_Singleton_Trait;

/**
 * 优化后台
 * @since 0.9.57.7
 */
class Wnd_Admin_Optimization {

	use Wnd_Singleton_Trait;

	private function __construct() {
		// 下拉用户：（切换内容作者）$query_args = apply_filters( 'wp_dropdown_users_args', $query_args, $parsed_args );
		add_filter('wp_dropdown_users_args', function (array $query_args): array{
			$query_args['include']            = [0];
			$query_args['capability']         = '';
			$query_args['capability__in']     = [];
			$query_args['capability__not_in'] = [];

			return $query_args;
		}, 10, 1);

		// 自定义字段：字段名提示查询 $keys = apply_filters( 'postmeta_form_keys', null, $post );
		add_filter('postmeta_form_keys', function (): array{
			return [];
		}, 10, 1);

		// 用户列表总数统计 $pre = apply_filters( 'pre_count_users', null, $strategy, $site_id );
		add_filter('pre_count_users', function (): array{
			$result['total_users'] = 0;
			$result['avail_roles'] = 0;
			return $result;
		}, 10, 1);
	}
}
