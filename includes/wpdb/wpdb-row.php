<?php
namespace Wnd\WPDB;

use Exception;
use Wnd\WPDB\WPDB_Row_Cache;

/**
 * # 独立数据表操作基类
 * - “增改”操作仅针对单行
 * - 统一缓存查询结果
 * - 在 wpdb 的基础上统一添加 Hook
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
			$this->table = $wpdb->prefix . $table_name;
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
		$sql = $this->build_sql($where, ['limit' => 1]);

		// object cache
		$data = $this->cache->get_data_from_cache($where);
		if (false !== $data) {
			do_action("get_{$this->object_name}_data_success", $data, $where);
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
	 * 获取指定条件的数据记录数组合集（多行）
	 * @since 0.9.67
	 *
	 * @return array
	 */
	public function get_results(array $where, array $options = []): array {
		// 按键名排序：用以统一 sql 语句及对应的缓存 key
		ksort($where);

		$sql     = $this->build_sql($where, $options);
		$results = $this->cache->get_results_from_cache($sql);
		if (false !== $results) {
			return $results ?: [];
		}

		$results = $this->wpdb->get_results($sql);
		$this->cache->set_results_into_cache($sql, $results);

		return $results ?: [];
	}

	/**
	 * @since 0.9.72
	 * sql防止注入安全过滤
	 *
	 *	$where = [
	 *	    'age'        => '10<age<50',
	 *	    'created_at' => '2024-01-01<created_at<2025-01-01',
	 *	    'status'     => '!=inactive'
	 *	];
	 */
	private function build_sql(array $where, array $options = []): string {
		// 默认参数
		$defaults = [
			'limit'    => 0,
			'offset'   => 0,
			'order_by' => $this->primary_id_column,
			'order'    => 'DESC',
		];
		$options = array_merge($defaults, $options);

		$conditions = ['1 = 1'];
		$params     = [];

		foreach ($where as $field => $value) {
			$value = trim($value);
			if ($value === 'any') {
				continue;
			}

			// 处理区间查询 '10<age<50' 或 '2024-01-01<created_at<2025-01-01'
			if (preg_match('/^(.+?)\s*<\s*([\w]+)\s*<\s*(.+?)$/', $value, $matches)) {
				$conditions[] = "`{$matches[2]}` BETWEEN %s AND %s";
				$params[]     = $matches[1];
				$params[]     = $matches[3];
				continue;
			}

			// 处理大于、小于、不等于
			if (preg_match('/^(>=?|<=?|!=)\s*(.+)$/', $value, $matches)) {
				$operator     = $matches[1];
				$val          = $matches[2];
				$conditions[] = "`$field` $operator %s";
				$params[]     = $val;
				continue;
			}

			// 处理 NULL 值
			if (is_null($value)) {
				$conditions[] = "`$field` IS NULL";
				continue;
			}

			// 默认等于查询
			$conditions[] = "`$field` = %s";
			$params[]     = $value;
		}

		// 验证排序字段（仅允许字母、数字、下划线）
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $options['order_by'])) {
			$options['order_by'] = $this->primary_id_column;
		}

		// 确保排序方式只能是 ASC 或 DESC
		$options['order'] = strtoupper($options['order']);
		if (!in_array($options['order'], ['ASC', 'DESC'])) {
			$options['order'] = 'DESC';
		}

		// 构造 SQL 语句
		$sql = "SELECT * FROM `{$this->table}` WHERE " . implode(' AND ', $conditions) . " ORDER BY `{$options['order_by']}` {$options['order']}";

		if ($options['limit']) {
			$sql .= ' LIMIT %d OFFSET %d';
			$params[] = $options['limit'];
			$params[] = $options['offset'];
		}

		return $this->wpdb->prepare($sql, ...$params);
	}

	/**
	 * update data by primary id, or $where if is give in
	 *
	 * @return int The primary id on success. The value 0 on failure.
	 */
	public function update(array $data, array $where = []): int {
		$data = apply_filters("update_{$this->object_name}_data", $data);
		do_action("before_update_{$this->object_name}", $data);

		$id_column = $this->primary_id_column;
		$ID        = $data[$id_column] ?? 0;
		$where     = $where ?: [$id_column => $ID];

		$object_before = $this->query($where);
		if (!$object_before) {
			return 0;
		}

		$ID   = $object_before->$id_column;
		$data = array_merge((array) $object_before, $data);
		$data = $this->parse_update_data($data, $where);

		$update = $this->wpdb->update($this->table, $data, $where);
		if ($update) {
			$this->cache->clean_row_cache($object_before);

			$object_after = $this->query($where);
			do_action("after_{$this->object_name}_updated", $ID, $object_after, $object_before);
		}

		return (false === $update) ? 0 : $ID;
	}

	// 较高并发情况下：update 存在数据不同步的问题。通过加减语法，来处理高并发数据
	public function inc(array $where, string $column, float $num) {
		return $this->inc_multiple($where, [$column => $num]);
	}

	// 较高并发情况下：update 存在数据不同步的问题。通过加减语法，来处理高并发数据
	public function inc_multiple(array $where, array $data) {
		$object_before = $this->query($where);
		if (!$object_before) {
			return false;
		}

		// 构建 SET 子句
		$setClauses = [];
		foreach ($data as $column => $value) {
			$setClauses[] = $this->wpdb->prepare("$column = $column + %.3f", [$value]);
		}
		$set_sql = implode(', ', $setClauses);

		$id_column = $this->primary_id_column;
		$sql       = $this->wpdb->prepare(
			"UPDATE $this->table SET $set_sql WHERE $this->primary_id_column = %d",
			[$object_before->$id_column]
		);

		$action = $this->wpdb->query($sql);
		if ($action) {
			$this->cache->clean_row_cache($object_before);
		}

		return $action;
	}

	/**
	 * delete data by primary id
	 *
	 * @return int The primary id on success. The value 0 on failure.
	 */
	public function delete(int $ID): int {
		return $this->delete_by($this->primary_id_column, $ID);
	}

	/**
	 * delete data by specified filed and value
	 *
	 * @return int The number of row which has been deleted.
	 */
	public function delete_by(string $field, $value): int {
		$where   = [$field => $value];
		$results = $this->get_results($where);
		if (!$results) {
			return 0;
		}

		foreach ($results as $data) {
			$primary_id_column = $this->primary_id_column;
			$ID                = $data->$primary_id_column;
			do_action("before_delete_{$this->object_name}", $data, $ID);

			$delete = $this->wpdb->delete($this->table, $where);
			if ($delete) {
				$this->cache->clean_row_cache($data);

				do_action("after_{$this->object_name}_deleted", $data, $ID);
			}
		}

		return count($results);
	}

	/**
	 * check insert data
	 * @access private
	 */
	private function parse_insert_data(array $data): array {
		if (!$this->required_columns) {
			throw new Exception('Required columns have not been initialized');
		}

		foreach ($this->required_columns as $column) {
			if (!isset($data[$column]) or (!$data[$column] and 0 !== $data[$column])) {
				throw new Exception('Required columns are empty : ' . $column);
			}
		}

		return $this->check_insert_data($data);
	}

	/**
	 * check insert data
	 */
	protected function check_insert_data(array $data): array {
		return $data;
	}

	/**
	 * check update data
	 * @access private
	 */
	private function parse_update_data(array $data, array $where): array {
		return $this->check_update_data($data);
	}

	/**
	 * check update data
	 */
	protected function check_update_data(array $data): array {
		return $data;
	}

}
