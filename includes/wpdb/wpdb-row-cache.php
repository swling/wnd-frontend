<?php
namespace Wnd\WPDB;

/**
 * # 单行数据表对象缓存
 * @since 0.9.59.1
 */
class WPDB_Row_Cache {

	/**
	 * 需要缓存的字段
	 * - 注意被缓存的字段应该是唯一值，否则可能导致缓存混乱
	 * - 单字段填写字段名即可，多字段填写对应字段数组
	 * - 当查询条件完整匹配上缓存字段时（多字段无关顺序）则命中缓存 @see $this->maybe_cache()
	 * - 更新或删除行数据时，会根据本属性及行数据，自动清除改行可能存在的所有缓存 @see $this->get_all_cache_keys()
	 * - 示例:['id', ['field_1', 'field_2']]
	 * @see $this->maybe_set_data_into_cache()
	 */
	protected $object_cache_fields = [];

	protected $cache_group = '';

	/**
	 * Constructer
	 *
	 * Init
	 */
	public function __construct(array $object_cache_fields, string $cache_group) {
		// 整理缓存键排序：多字段排序，用于多字段查询时的缓存匹配
		foreach ($object_cache_fields as $key => $value) {
			if (is_array($value)) {
				sort($value);
				$object_cache_fields[$key] = $value;
			}
		}

		$this->object_cache_fields = $object_cache_fields;
		$this->cache_group         = $cache_group;
	}

	/**
	 * get data from cache
	 */
	public function get_data_from_cache(array $where): mixed{
		$key = $this->generate_cache_key($where);
		return wp_cache_get($key, $this->cache_group);
	}

	/**
	 * set data into cache
	 */
	public function set_data_into_cache(array $where, object $data): bool {
		if (!$this->maybe_cache($where)) {
			return false;
		}

		// 缓存查询结果
		$key = $this->generate_cache_key($where);
		return wp_cache_set($key, $data, $this->cache_group);
	}

	/**
	 * clean table cache When a row is deleted or updated
	 */
	public function clean_row_cache(object $old_data) {
		$cache_keys = $this->get_all_cache_keys($old_data);
		foreach ($cache_keys as $cache_key) {
			wp_cache_delete($cache_key, $this->cache_group);
		}

		// 更新表单更改时间
		$this->refresh_db_table_last_changed();
	}

	/**
	 * 根据查询条件与缓存字段的匹配情况，生成缓存键名
	 * 查询条件未匹配缓存字段，则返回 false
	 */
	private function maybe_cache(array $where): bool{
		/**
		 * 整理查询排序：
		 * - 若查询多个字段，提取字段数组
		 * - 查询单个字段，提取字段名字符串
		 */
		ksort($where);
		$query_keys = count($where) > 1 ? array_keys($where) : array_key_first($where);

		return in_array($query_keys, $this->object_cache_fields);
	}

	/**
	 * 基于缓存字段和数据表结构
	 * 生成本行数据所有可能存在的缓存键名
	 * 用于更新或删除单行数据时，更新或删除本行数据对应的所有缓存
	 *
	 */
	private function get_all_cache_keys(object $data): array{
		$cache_keys = [];
		foreach ($this->object_cache_fields as $cache_key) {
			$where = [];
			if (is_array($cache_key)) {
				foreach ($cache_key as $_cache_key) {
					$where[$_cache_key] = $data->$_cache_key;
				}
			} else {
				$where[$cache_key] = $data->$cache_key;
			}

			$cache_keys[] = $this->generate_cache_key($where);
			// $cache_keys[] = $where;
		}

		return $cache_keys;
	}

	/**
	 * 生成缓存键名
	 * - 将所有查询条件值统一转为【字符串类型】并生成对应的缓存键值
	 */
	private function generate_cache_key(array $where): string{
		$where = array_map(function ($value) {
			return (string) $value;
		}, $where);

		return md5(json_encode($where));
	}

	/**
	 * Refresh last changed date for DB Table
	 */
	public function refresh_db_table_last_changed(): bool {
		return wp_cache_delete('last_changed', $this->cache_group);
	}

	/**
	 * Gets last changed date for the current DB table.
	 *
	 * @param string $group Where the cache contents are grouped.
	 * @return string UNIX timestamp with microseconds representing when the group was last changed.
	 */
	public function get_current_db_table_last_changed(): string {
		return wp_cache_get_last_changed($this->cache_group);
	}

}
