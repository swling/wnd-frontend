<?php
namespace Wnd\Action\Admin;

use Exception;
use Wnd\Action\Wnd_Action;

/**
 * @since 0.9.89
 * 设置单个 option
 */
class Wnd_Set_Option extends Wnd_Action {

	protected $verify_sign = false;

	private $value;
	private $name;

	protected function execute(): array {
		$action = update_option($this->name, $this->value);
		if (!$action) {
			throw new Exception('update option failed: ' . $this->name);
		}
		return [
			'status' => 1,
			'msg'    => 'update option success',
		];
	}

	protected function parse_data() {
		$this->name  = $this->data['name'] ?? '';
		$this->value = $this->data['value'] ?? '';
	}

}
