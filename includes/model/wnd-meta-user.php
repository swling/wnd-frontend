<?php
namespace Wnd\Model;

/**
 * 自定义数组格式存储 User Meta
 * @since 0.9.52
 */
class Wnd_Meta_User extends Wnd_Meta {

	protected $meta_type = 'user';

	protected function update_wp_meta($meta) {
		return update_user_meta($this->object_id, static::META_KEY, $meta);
	}

	protected function get_wp_meta() {
		return get_user_meta($this->object_id, static::META_KEY, true);
	}

	protected function delete_wp_meta() {
		return delete_user_meta($this->object_id, static::META_KEY);
	}
}
