<?php
namespace Wnd\WPDB;

use Wnd\Utility\Wnd_Singleton_Trait;
use Wnd\WPDB\WPDB_Row;

/**
 * 自定义站内信息
 * @since 0.9.73
 *
 * `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,  -- 消息ID，主键，自增
 * `from` BIGINT UNSIGNED NOT NULL,               -- 发送者ID
 * `to` BIGINT UNSIGNED NOT NULL,                 -- 接收者ID
 * `subject` VARCHAR(255) NOT NULL,               -- 消息主题
 * `content` TEXT NOT NULL,                       -- 消息内容
 * `sent_at` bigint(20) NOT NULL,                 -- 发送时间，默认当前时间
 * `read_at` bigint(20) NOT NULL,                 -- 阅读时间，默认为NULL，表示未读
 * `status` ENUM('unread', 'read', 'deleted') DEFAULT 'unread',  -- 消息状态：未读，已读，删除
 * PRIMARY KEY (`ID`),                            -- 设置主键
 * INDEX `to` (`to`),                             -- 为接收者ID添加索引，方便查询
 * INDEX `from` (`from`)                          -- 为发送者ID添加索引，方便查询
 */
class Wnd_Mail_DB extends WPDB_Row {

	protected $table_name        = 'wnd_mails';
	protected $object_name       = 'wnd_mail';
	protected $primary_id_column = 'ID';
	protected $required_columns  = ['to', 'subject', 'content', 'sent_at'];

	protected $object_cache_fields = ['ID', 'to'];

	/**
	 * 单例模式
	 */
	use Wnd_Singleton_Trait;

	private function __construct() {
		parent::__construct();
	}

}
