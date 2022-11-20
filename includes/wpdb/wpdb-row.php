<?php
namespace Wnd\WPDB;

use Exception;
use Wnd\WPDB\WPDB_Row_Cache;

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

	/**
	 * 需要缓存的字段
	 * - 注意被缓存的字段应该是唯一值，否则可能导致缓存混乱
	 * - 示例:['id', ['field_1', 'field_2']]
	 * @see $this->maybe_set_data_into_cache()
	 */
	protected $object_cache_fields = [];

	protected $wpdb;
	protected $table;

	protected $cache;

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

		// meta 类需要按类型 指定数据表
		if ($this->table_name) {
			$table_name  = $this->table_name;
			$this->table = $wpdb->$table_name;
			$this->cache = new WPDB_Row_Cache($this->object_cache_fields, $this->table_name);
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
			$this->cache->refresh_db_table_last_changed();

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
		foreach ($where as $field => $value) {
			if (is_null($value)) {
				$conditions .= "`$field` IS NULL";
			} else {
				$conditions .= "`$field` = " . "'{$value}'";
			}
		}
		$sql = "SELECT * FROM `$this->table` WHERE $conditions LIMIT 1";

		// object cache
		$data = $this->cache->get_data_from_cache($where);
		if (false !== $data) {
			return $data;
		}

		// get data form database success
		$data = $this->wpdb->get_row($sql);
		if ($data) {
			$this->cache->set_data_into_cache($where, $data);

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
			$this->cache->clean_row_cache($object_before);

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
			$this->cache->clean_row_cache($data);

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

}
