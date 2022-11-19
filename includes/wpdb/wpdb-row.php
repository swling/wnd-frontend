<?php
namespace Wnd\WPDB;

use Exception;

/**
 * # 单行数据表操作基类
 * - “增删改”操作仅针对单行
 * - 仅支持单行查询，并统一缓存查询结果
 * - 在 wpdb 的基础上统一添加 Hook
 * - term_relationships 读取与其他表紧密关联，因此不适用于本类，对应为独立类 @see Term_Relationships_Handler();
 *
 * ### 数据检查
 * - 写入数据时，默认检查是否包含了必须字段（具体由子类定义）
 * - 更新数据时，默认检查是否设置了主键 ID，并核查 ID 是否有效
 * - 其他数据核查请在子类中实现 @see check_insert_data(); @see check_update_data()
 *
 * ### 备注
 * - 本类作为为单行数据操作的基础，应尽可能降低对外部函数的依赖，以提升代码内聚
 * - 反之外部封装函数应尽可能利用本类相关方法，以降低代码重复
 *
 * ### return
 * - Get : data（object） or false
 * - Insert/Update : ID（int） or 0
 * - Delete :  ID (int) or 0
 *
 */
class WPDB_Row {

	protected $table_name;
	protected $object_name;
	protected $primary_id_column;
	protected $required_columns = [];

	protected $wpdb;
	protected $table;

	/**
	 * Constructer
	 *
	 * Init
	 */
	public function __construct(array $args = []) {
		foreach ($args as $key => $value) {
			if (!property_exists($this, $key)) {
				continue;
			}

			$this->$key = $value;
		}

		global $wpdb;
		$this->wpdb = $wpdb;

		if ($this->table_name) {
			$table_name  = $this->table_name;
			$this->table = $wpdb->$table_name;
		}
	}

	/**
	 * insert data
	 *
	 * @return int primary id
	 */
	public function insert(array $data): int {
		// update
		if (isset($data[$this->primary_id_column]) and $data[$this->primary_id_column]) {
			return $this->update($data);
		}

		$data = apply_filters("insert_{$this->object_name}_data", $data);
		do_action("before_insert_{$this->object_name}", $data);

		$data   = $this->parse_insert_data($data);
		$insert = $this->wpdb->insert($this->table, $data);
		if ($insert) {
			$this->refresh_db_table_last_changed();

			do_action("after_{$this->object_name}_inserted", $this->wpdb->insert_id, $data);
		}

		return $this->wpdb->insert_id;
	}

	/**
	 * get data by primary id
	 *
	 * @return object|false
	 */
	public function get(int $ID) {
		return $this->get_by($this->primary_id_column, $ID);
	}

	/**
	 * get data by column
	 * @return object|false
	 */
	public function get_by(string $field, $value) {
		return $this->query([$field => $value]);
	}

	/**
	 * query single row  by columns
	 * @return object|false
	 */
	public function query(array $where) {
		$data = apply_filters("get_{$this->object_name}_data", false, $where);
		if (false !== $data) {
			return $data;
		}

		// sql 语句
		$conditions = '';
		ksort($where);
		foreach ($where as $field => $value) {
			if (is_null($value)) {
				$conditions .= "`$field` IS NULL";
			} else {
				$conditions .= "`$field` = " . "'{$value}'";
			}
		}
		$sql       = "SELECT * FROM `$this->table` WHERE $conditions LIMIT 1";
		$query_key = md5($sql);

		// object cache
		$data = $this->get_data_from_cache($query_key);
		if (false !== $data) {
			return $data;
		}

		// get data form database success
		$data = $this->wpdb->get_row($sql);
		if ($data) {
			$this->set_data_into_cache($query_key, $data);

			do_action("get_{$this->object_name}_data_success", $data, $where);
		}

		return $data ? (object) $data : false;
	}

	/**
	 * update data by primary id
	 *
	 * @return int The primary id on success. The value 0 on failure.
	 */
	public function update(array $data): int{
		$data = apply_filters("update_{$this->object_name}_data", $data);
		do_action("before_update_{$this->object_name}", $data);

		$ID            = $data[$this->primary_id_column] ?? 0;
		$object_before = $this->get($ID);
		$data          = array_merge((array) $object_before, $data);
		$data          = $this->parse_update_data($data);

		$where  = [$this->primary_id_column => $ID];
		$update = $this->wpdb->update($this->table, $data, $where);
		if ($update) {
			$this->clean_row_cache($object_before);

			$object_after = $this->get($ID);
			do_action("after_{$this->object_name}_updated", $ID, $object_after, $object_before);
		}

		return $update ? $ID : 0;
	}

	/**
	 * delete data by primary id
	 *
	 * @return int The primary id on success. The value 0 on failure.
	 */
	public function delete(int $ID): int{
		$data = $this->get($ID);
		if (!$data) {
			return 0;
		}
		do_action("before_delete_{$this->object_name}", $data, $ID);

		$where  = [$this->primary_id_column => $ID];
		$delete = $this->wpdb->delete($this->table, $where);
		if ($delete) {
			$this->clean_row_cache($data);

			do_action("after_{$this->object_name}_deleted", $data, $ID);
		}

		return $delete ? $ID : 0;
	}

	/**
	 * check insert data
	 * @access private
	 */
	private function parse_insert_data(array $data): array{
		if (!$this->required_columns) {
			throw new Exception('Required columns have not been initialized');
		}

		foreach ($this->required_columns as $column) {
			if (!isset($data[$column]) or !$data[$column]) {
				throw new Exception('Required columns are empty : ' . $column);
			}
		}

		return $this->check_insert_data($data);
	}

	/**
	 * check insert data
	 */
	protected function check_insert_data(array $data): array{
		return $data;
	}

	/**
	 * check update data
	 * @access private
	 */
	private function parse_update_data(array $data): array{
		$ID = $data[$this->primary_id_column] ?? 0;
		if (!$ID) {
			throw new Exception('Primary ID column are empty on update: ' . $this->primary_id_column);
		}

		if (!$this->get($ID)) {
			throw new Exception('Primary ID is invalid');
		}

		return $this->check_update_data($data);
	}

	/**
	 * check update data
	 */
	protected function check_update_data(array $data): array{
		return $data;
	}

	/**
	 * get data from cache
	 */
	private function get_data_from_cache(string $query_key) {
		$cache_group = $this->generate_cache_group();
		return wp_cache_get($query_key, $cache_group);
	}

	/**
	 * set data into cache
	 */
	private function set_data_into_cache(string $query_key, $data) {
		// 缓存查询结果
		$cache_group = $this->generate_cache_group();
		$cache_data  = wp_cache_set($query_key, $data, $cache_group);
		if (!$cache_data) {
			return;
		}

		// 将查询缓存键作为值缓存，用于在数据删除时，删除当前行已经生成的所有查询缓存
		$primary_id       = $this->primary_id_column;
		$cache_keys_group = $this->generate_cache_keys_group();
		$cache_keys       = wp_cache_get($data->$primary_id, $cache_keys_group) ?: [];
		$cache_keys[]     = $query_key;
		$cache_keys       = array_unique($cache_keys);

		wp_cache_set($data->$primary_id, $cache_keys, $cache_keys_group);
	}

	/**
	 * clean table cache When a row is deleted or updated
	 */
	public function clean_row_cache(object $old_data) {
		$primary_id       = $this->primary_id_column;
		$cache_keys_group = $this->generate_cache_keys_group();
		$cache_keys       = wp_cache_get($old_data->$primary_id, $cache_keys_group) ?: [];

		// 删除已缓存的查询
		$cache_group = $this->generate_cache_group();
		foreach ($cache_keys as $query_key) {
			wp_cache_delete($query_key, $cache_group);
		}

		// 删除对应主键已缓存的【查询键】
		wp_cache_delete($old_data->$primary_id, $cache_keys_group);

		// 更新表单更改时间
		$this->refresh_db_table_last_changed();
	}

	/**
	 * Generate field cache group name
	 * @return string|false
	 */
	private function generate_cache_group(): string {
		return $this->table_name;
	}

	/**
	 * Generate field cache group name
	 * @return string|false
	 */
	private function generate_cache_keys_group(): string {
		return $this->table_name . '_cache_keys';
	}

	/**
	 * Refresh last changed date for DB Table
	 */
	protected function refresh_db_table_last_changed(): bool {
		return wp_cache_delete('last_changed', $this->table_name);
	}

	/**
	 * Gets last changed date for the current DB table.
	 *
	 * @param string $group Where the cache contents are grouped.
	 * @return string UNIX timestamp with microseconds representing when the group was last changed.
	 */
	public function get_current_db_table_last_changed(): string {
		return wp_cache_get_last_changed($this->table_name);
	}

}
