<?php
/*+***********************************************************************************
 * Generic API connection adapter (URL + credentials). Senior implements real client later.
 *************************************************************************************/

require_once 'modules/Vtiger/helpers/NkApi/Adapter.php';

class NkApi_Generic_Adapter extends NkApi_Adapter {

	/** @var array */
	protected $meta;

	public function __construct(array $meta) {
		$this->meta = $meta;
	}

	public function code() {
		return (string) $this->meta['code'];
	}

	public function label() {
		return (string) $this->meta['label'];
	}

	public function description() {
		return (string) $this->meta['description'];
	}

	public function icon() {
		return isset($this->meta['icon']) ? (string) $this->meta['icon'] : $this->code();
	}

	public function hint() {
		if (!empty($this->meta['hint'])) {
			return (string) $this->meta['hint'];
		}
		return 'Lưu URL và thông tin đăng nhập. Senior implement API client cho ' . $this->label() . '.';
	}
}
