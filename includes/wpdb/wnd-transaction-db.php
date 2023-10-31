<?php

namespace Wnd\WPDB;

use Wnd\Utility\Wnd_Singleton_Trait;
use Wnd\WPDB\WPDB_Row;

/**
 * 自定义交易订单数据表
 * @since 0.9.67
 * 
 *  'ID'              => $this->transaction_id,
 *  'user_id'         => $this->user_id,
 *  'object_id'       => $this->object_id,
 *  'type'            => $this->transaction_type,
 *  'total_amount'    => $this->total_amount,
 *  'payment_gateway' => $this->payment_gateway,
 *  'status'          => $this->status,
 *  'subject'         => $this->subject,
 *  'slug'            => $this->transaction_slug ?: uniqid(),
 *  'date'            => current_time('mysql'),
 *  'props'           => "{订单SKU等属性，json}"
 */
class Wnd_Transaction_DB extends WPDB_Row {

	protected $table_name        = 'wnd_transactions';
	protected $object_name       = 'wnd_transaction';
	protected $primary_id_column = 'ID';
	protected $required_columns  = ['user_id', 'type', 'total_amount', 'payment_gateway', 'status', 'subject', 'slug'];

	protected $object_cache_fields = ['ID', 'slug', ['object_id', 'user_id', 'type', 'status']];

	/**
	 * 单例模式
	 * @since 0.9.59
	 */
	use Wnd_Singleton_Trait;

	private function __construct() {
		parent::__construct();
	}

}
