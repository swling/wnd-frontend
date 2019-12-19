<?php
namespace Wnd\Model;

use Exception;

/**
 *@since 2019.12.19
 *验证授权
 *
 *指定用户验证
 */
class Wnd_Auth_User extends Wnd_Auth {

	// 数据库字段
	protected $db_field = 'user_id';

	// 提示文字
	protected $text = '用户';

	public function __construct($auth_object) {
		parent::__construct();

		$this->auth_object    = $auth_object;
		$this->db_field_value = $auth_object->ID ?? 0;
		if (!$this->db_field_value) {
			throw new Exception('指定用户无效');
		}
	}

	/**
	 *
	 *验证指定用户，仅作为一种验证方法，不支持发送操作
	 */
	public function send() {
		throw new Exception(__CLASS__ . '仅做验证，不支持发送');
	}

	/**
	 *根据auth_object查询数据库记录
	 *
	 *@since 2019.12.19
	 *
	 */
	protected function get_db_record() {
		global $wpdb;
		$data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->wnd_users WHERE {$this->db_field} = %d;",
				$this->db_field_value
			)
		);

		return $data;
	}
}
