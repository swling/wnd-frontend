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

	public function update_wnd_meta(string $meta_key, $meta_value): bool{
		$update_meta = [$meta_key => $meta_value];
		return $this->update_wnd_meta_array($update_meta);
	}

	public function get_wnd_meta(string $meta_key) {
		$meta = $this->get_wp_meta();
		return $meta[$meta_key] ?? '';
	}

	public function delete_wnd_meta(string $meta_key): bool{
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

	public function inc_wp_meta(string $meta_key, float $val, bool $min_zero): bool{
		$get_method    = '\get_' . $this->meta_type . '_meta';
		$update_method = '\update_' . $this->meta_type . '_meta';
		$old_value     = (float) $get_method($this->object_id, $meta_key, true);
		$new_value     = $old_value + $val;

		// 不为负数
		if ($min_zero and $new_value < 0) {
			$new_value = 0;
		}

		return $update_method($this->object_id, $meta_key, $new_value);
	}

	public function inc_wnd_meta(string $meta_key, float $val, bool $min_zero): bool{
		$old_value = (float) $this->get_wnd_meta($meta_key);
		$new_value = $old_value + $val;

		// 不为负数
		if ($min_zero and $new_value < 0) {
			$new_value = 0;
		}

		return $this->update_wnd_meta($meta_key, $new_value);
	}

	protected function update_wp_meta($meta) {
		return update_post_meta($this->object_id, static::META_KEY, $meta);
	}

	protected function get_wp_meta() {
		return get_post_meta($this->object_id, static::META_KEY, true);
	}

	protected function delete_wp_meta() {
		return delete_post_meta($this->object_id, static::META_KEY);
	}
}
