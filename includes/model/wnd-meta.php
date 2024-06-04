<?php
namespace Wnd\Model;

/**
 * 自定义数组格式存储 Post Meta
 * @since 0.9.52
 */
class Wnd_Meta {

	protected const META_KEY = 'wnd_meta';

	protected $object_id;

	protected $meta_type = 'post';

	public function __construct(int $object_id) {
		$this->object_id = $object_id;
	}

	public function update_wnd_meta(string $meta_key, $meta_value): bool {
		$update_meta = [$meta_key => $meta_value];
		return $this->update_wnd_meta_array($update_meta);
	}

	public function get_wnd_meta(string $meta_key) {
		$meta = $this->get_wp_meta();
		return $meta[$meta_key] ?? '';
	}

	public function delete_wnd_meta(string $meta_key): bool {
		$meta = $this->get_wp_meta();
		if (!is_array($meta)) {
			return false;
		}

		unset($meta[$meta_key]);

		// $append = false 强制替换数据
		return $this->update_wnd_meta_array($meta, false);
	}

	public function update_wnd_meta_array(array $update_meta, bool $append = true): bool {
		/**
		 * - 追加合并
		 * - 完整替换
		 */
		if ($append) {
			$meta = $this->get_wp_meta() ?: [];
			if (!is_array($meta)) {
				return false;
			}

			$meta = array_merge($meta, $update_meta);
		} else {
			$meta = $update_meta;
		}

		/**
		 * - 移除空值，但保留 0 @see wnd_array_filter
		 * - 合并后的 meta有效：更新
		 * - 合并后的 meta为空：删除
		 */
		$meta = wnd_array_filter($meta);
		if ($meta) {
			return $this->update_wp_meta($meta);
		} else {
			return $this->delete_wp_meta();
		}
	}

	/**
	 * @since 0.9.72
	 * 重构使用：inc 语法 meta_value = meta_value + %.3f
	 * 较高并发情况下：update 存在数据不同步的问题。通过加减语法，来处理高并发数据
	 */
	public function inc_wp_meta(string $meta_key, float $val): bool {
		$table = _get_meta_table($this->meta_type);
		if (!$table) {
			return false;
		}

		// 从 update_metadata() 复制而来; 允许拦截数据更新操作
		$prev_value = '';
		$check      = apply_filters("update_{$this->meta_type}_metadata", null, $this->object_id, $meta_key, $val, $prev_value);
		if (null !== $check) {
			return (bool) $check;
		}

		$column    = sanitize_key($this->meta_type . '_id');
		$id_column = ('user' === $this->meta_type) ? 'umeta_id' : 'meta_id';

		global $wpdb;
		$mid = $wpdb->get_var(
			$wpdb->prepare("SELECT $id_column FROM $table WHERE meta_key = %s AND $column = %d LIMIT 1", [$meta_key, $this->object_id])
		);
		if (!$mid) {
			return add_metadata($this->meta_type, $this->object_id, $meta_key, $val);
		}

		$sql = $wpdb->prepare(
			"UPDATE $table SET meta_value = meta_value + %.3f WHERE $id_column = %s",
			[$val, $mid]
		);

		$action = $wpdb->query($sql);
		if (!$action) {
			return false;
		}

		// WP Object Cache @see 原生函数：update_metadata()
		wp_cache_delete($this->object_id, $this->meta_type . '_meta');

		return $action;
	}

	public function inc_wnd_meta(string $meta_key, float $val, bool $min_zero): bool {
		$old_value = (float) $this->get_wnd_meta($meta_key);
		$new_value = $old_value + $val;

		// 不为负数
		if ($min_zero and $new_value < 0) {
			$new_value = 0;
		}

		return $this->update_wnd_meta($meta_key, $new_value);
	}

	protected function update_wp_meta($meta) {
		// 此处不得使用 update_post_meta() 因为它无法给 revision 添加字段;
		return update_metadata('post', $this->object_id, static::META_KEY, $meta);
	}

	protected function get_wp_meta() {
		return get_post_meta($this->object_id, static::META_KEY, true);
	}

	protected function delete_wp_meta() {
		// 此处不得使用 delete_post_meta() 因为它无法作用于 revision;
		return delete_metadata( 'post', $this->object_id, static::META_KEY);
	}
}
