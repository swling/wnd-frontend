<?php
namespace Wnd\WPDB;

use Wnd\Utility\Wnd_Singleton_Trait;
use Wnd\WPDB\WPDB_Row;

/**
 * 自定义附件数据库
 * @since 0.9.86
 *
 */
class Wnd_Attachment_DB extends WPDB_Row {

	protected $table_name        = 'wnd_attachments';
	protected $object_name       = 'wnd_attachment';
	protected $primary_id_column = 'ID';
	protected $required_columns  = ['file_path', 'mime_type'];

	protected $object_cache_fields = ['ID'];

	/**
	 * 单例模式
	 */
	use Wnd_Singleton_Trait;

	private function __construct() {
		parent::__construct();
	}
}
