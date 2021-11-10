<?php
namespace Wnd\Model;

/**
 * 自定义数组格式存储 Post/User Meta
 * @since 0.9.52
 */
class Wnd_Meta {

	const META_KEY = 'wnd_meta';

	//############################################################################ wnd user meta
	public static function update_user_meta(int $user_id, string $meta_key, $meta_value) {
		return static::update_wnd_meta('user', $user_id, $meta_key, $meta_value);
	}

	public static function update_user_meta_array($user_id, $update_meta) {
		static::update_wnd_meta_array('user', $user_id, $update_meta);
	}

	public static function get_user_meta(int $user_id, string $meta_key) {
		return static::get_wnd_meta('user', $user_id, $meta_key);
	}

	public static function delete_user_meta(int $user_id, string $meta_key) {
		return static::delete_wnd_meta('user', $user_id, $meta_key);
	}

	//############################################################################ wnd post meta
	public static function update_post_meta(int $post_id, string $meta_key, $meta_value) {
		return static::update_wnd_meta('post', $post_id, $meta_key, $meta_value);
	}

	public static function update_post_meta_array(int $post_id, array $update_meta) {
		static::update_wnd_meta_array('post', $post_id, $update_meta);
	}

	public static function get_post_meta(int $post_id, string $meta_key) {
		return static::get_wnd_meta('post', $post_id, $meta_key);
	}

	public static function delete_post_meta(int $post_id, string $meta_key) {
		return static::delete_wnd_meta('post', $post_id, $meta_key);
	}

	//############################################################################ user meta 增量
	public static function inc_user_meta(int $user_id, string $meta_key, float $val = 1, bool $min_zero = false): bool {
		return static::inc_wp_meta('user', $user_id, $meta_key, $val, $min_zero);
	}

	public static function inc_wnd_user_meta(int $user_id, string $meta_key, float $val = 1, bool $min_zero = false): bool {
		return static::inc_wnd_meta('user', $user_id, $meta_key, $val, $min_zero);
	}

	//############################################################################ post meta 增量
	public static function inc_post_meta(int $post_id, string $meta_key, float $val = 1, bool $min_zero = false): bool {
		return static::inc_wp_meta('post', $post_id, $meta_key, $val, $min_zero);
	}

	public static function inc_wnd_post_meta(int $post_id, string $meta_key, float $val = 1, bool $min_zero = false): bool {
		return static::inc_wnd_meta('post', $post_id, $meta_key, $val, $min_zero);
	}

	//############################################################################ wnd meta 封装
	private static function update_wnd_meta(string $meta_type, int $object_id, string $meta_key, $meta_value): bool{
		$update_meta = [$meta_key => $meta_value];
		return static::update_wnd_meta_array($meta_type, $object_id, $update_meta);
	}

	private static function get_wnd_meta(string $meta_type, int $object_id, string $meta_key) {
		$meta = static::get_wnd_meta_array($meta_type, $object_id);
		return $meta[$meta_key] ?? '';
	}

	private static function delete_wnd_meta(string $meta_type, int $object_id, string $meta_key): bool{
		$meta = static::get_wnd_meta_array($meta_type, $object_id);
		if (!is_array($meta)) {
			return false;
		}

		unset($meta[$meta_key]);

		// $append = false 强制替换数据
		return static::update_wnd_meta_array($meta_type, $object_id, $meta, false);
	}

	private static function update_wnd_meta_array(string $meta_type, int $object_id, array $update_meta, bool $append = true): bool{
		$get_method    = '\get_' . $meta_type . '_meta';
		$update_method = '\update_' . $meta_type . '_meta';

		/**
		 * - 追加合并
		 * - 完整替换
		 */
		if ($append) {
			$meta = $get_method($object_id, static::META_KEY, true) ?: [];
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
			return $update_method($object_id, static::META_KEY, $meta);
		} else {
			return static::delete_wnd_meta_array($meta_type, $object_id);
		}
	}

	private static function get_wnd_meta_array(string $meta_type, int $object_id): array{
		$method = '\get_' . $meta_type . '_meta';
		$meta   = $method($object_id, static::META_KEY, true) ?: [];
		if (!is_array($meta)) {
			return [];
		}

		return $meta;
	}

	private static function delete_wnd_meta_array(string $meta_type, int $object_id): bool{
		$delete_method = '\delete_' . $meta_type . '_meta';
		return $delete_method($object_id, static::META_KEY);
	}

	private static function inc_wp_meta(string $meta_type, int $object_id, string $meta_key, float $val, bool $min_zero): bool{
		$get_method    = '\get_' . $meta_type . '_meta';
		$update_method = '\update_' . $meta_type . '_meta';
		$old_value     = (float) $get_method($object_id, $meta_key, true);
		$new_value     = $old_value + $val;

		// 不为负数
		if ($min_zero and $new_value < 0) {
			$new_value = 0;
		}

		return $update_method($object_id, $meta_key, $new_value);
	}

	private static function inc_wnd_meta(string $meta_type, int $object_id, string $meta_key, float $val, bool $min_zero): bool{
		$get_method    = __CLASS__ . '::get_' . $meta_type . '_meta';
		$update_method = __CLASS__ . '::update_' . $meta_type . '_meta';
		$old_value     = (float) $get_method($object_id, $meta_key, true);
		$new_value     = $old_value + $val;

		// 不为负数
		if ($min_zero and $new_value < 0) {
			$new_value = 0;
		}

		return $update_method($object_id, $meta_key, $new_value);
	}

}
