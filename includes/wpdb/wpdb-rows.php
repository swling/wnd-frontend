<?php
namespace Wnd\WPDB;

/**
 * # Rows Handler
 * 同一张表中，具有共同属性的多行数据操作基类
 *
 * Rows 定义：具有共同属性的并列行数据典型如；
 * - wp_user_meta （共同属性为 user_id）、wp_post_meta（共同属性为 post_id）……
 *
 * 约定：共同属性值为 int 类型
 * 作用：主要用于统一读写方法降低代码重复，并统一设置内存缓存（object cache）
 *
 * @since 2022.06.11
 */
abstract class WPDB_Rows {

	// 单行数据属性
	protected $table_name;
	protected $object_name;
	protected $primary_id_column;
	protected $required_columns    = [];
	protected $object_cache_fields = [];

	// 共同属性 id 字段名
	protected $object_id_column;

	// 数据表基本属性（根据上述属性生成，故一般无需在子类额外配置）
	protected $wpdb;
	protected $table;

	// 单行操作实例（根据上述属性生成，故一般无需在子类额外配置）
	protected $wpdb_row;

	/**
	 * Constructer
	 * 设置为 protected 构造方法旨在方便子类以单例模式实例化
	 */
	protected function __construct() {
		$this->instance_wpdb();
		$this->instance_wpdb_row();
	}

	/**
	 * 定义数据表基本信息
	 */
	protected function instance_wpdb() {
		global $wpdb;
		$this->wpdb = $wpdb;

		if ($this->table_name) {
			$table_name  = $this->table_name;
			$this->table = $wpdb->prefix . $table_name;
		}
	}

	/**
	 * 实例化单行操作对象
	 */
	protected function instance_wpdb_row() {
		$args = [
			'table_name'          => $this->table_name,
			'object_name'         => $this->object_name,
			'primary_id_column'   => $this->primary_id_column,
			'required_columns'    => $this->required_columns,
			'object_cache_fields' => $this->object_cache_fields,
		];

		$this->wpdb_row = new WPDB_Row($args);
	}

	/**
	 * Retrieves all raws value for the specified object.
	 * @return array|false
	 */
	public function get_rows(int $object_id) {
		$data = $this->get_object_rows_cache($object_id);
		if (false !== $data) {
			return $data;
		}

		global $wpdb;
		$data = $wpdb->get_results(
			$wpdb->prepare("SELECT * FROM {$this->table} WHERE {$this->object_id_column} = %d ORDER BY %s ASC", $object_id, $this->primary_id_column)
		);

		if ($data) {
			$this->set_object_rows_cache($object_id, $data);
		}

		return $data;
	}

	/**
	 * delete specified object all rows
	 *
	 * @return int The number of rows updated
	 */
	public function delete_rows(int $object_id): int {
		global $wpdb;
		$action = $wpdb->delete($this->table, [$this->object_id_column => $object_id]);
		if (!$action) {
			return 0;
		}
		$this->delete_object_rows_cache($object_id);

		// 依次删除每一行数据对应的缓存
		$old_data = $this->get_rows($object_id);
		foreach ($old_data as $_data) {
			$this->wpdb_row->cache->clean_row_cache($_data);
		}

		return $action;
	}

	/**
	 * get single row data object by object ID and row key
	 *
	 * @param $object_id int
	 * @param $where     array  example: ['field1' => 'value1', 'field2' => 'value2',]
	 *
	 * @return object|false
	 */
	public function get_row(int $object_id, array $where) {
		$data = $this->get_rows($object_id);
		foreach ($data as $row) {

			// $where 数组中任一字段不匹配，则表明该行数据不匹配
			$match = true;
			foreach ($where as $field => $value) {
				// 查询字段中包含不再数据表的字段
				if (!isset($row->$field)) {
					return false;
				}

				if ($row->$field != $value) {
					$match = false;
					continue;
				}
			}

			if ($match) {
				return $row;
			}
		}

		return false;
	}

	/**
	 * add row data
	 * @return int row id
	 */
	public function add_row(int $object_id, array $data): int{
		$data[$this->object_id_column] = $object_id;
		$data                          = $this->check_insert_data($data);
		$id                            = $this->wpdb_row->insert($data);
		if ($id) {
			$this->delete_object_rows_cache($object_id);
		}
		return $id;
	}

	/**
	 * update row data
	 * If no value already exists for the specified object ID and rowdata key, the rowdata will be added.
	 * @return int row id
	 */
	public function update_row(int $object_id, array $data, array $where): int{
		$_data = $this->get_row($object_id, $where);
		if (!$_data) {
			return 0;
		}

		$data = array_merge((array) $_data, $data);
		$data = $this->check_update_data($data);
		$id   = $this->wpdb_row->update($data);
		if ($id) {
			$this->delete_object_rows_cache($object_id);
		}
		return $id;
	}

	/**
	 * delete row data
	 *
	 * @param $object_id int
	 * @param $where     array  example: ['field' => 'value']
	 *
	 * @return int row id
	 */
	public function delete_row(int $object_id, array $where): int{
		$data = $this->get_row($object_id, $where);
		if (!$data) {
			return 0;
		}

		$primary_id_column = $this->primary_id_column;
		$primary_id        = $data->$primary_id_column;

		$id = $this->wpdb_row->delete($primary_id);
		if ($id) {
			$this->delete_object_rows_cache($object_id);
		}
		return $id;
	}

	/**
	 * check insert data
	 */
	abstract protected function check_insert_data(array $data): array;

	/**
	 * check update data
	 */
	abstract protected function check_update_data(array $data): array;

	/**
	 * get cache of all rows data for specific object id
	 * @return  mixed
	 */
	private function get_object_rows_cache(int $object_id): mixed {
		return wp_cache_get($object_id, $this->table_name);
	}

	/**
	 * set rows data cache for specific object id
	 * @return  row id
	 */
	private function set_object_rows_cache(int $object_id, array $data): bool {
		return wp_cache_set($object_id, $data, $this->table_name);
	}

	/**
	 * set rows data cache for specific object id
	 * @return  row id
	 */
	private function delete_object_rows_cache(int $object_id): bool {
		return wp_cache_delete($object_id, $this->table_name);
	}

}
