<?php
/*+***********************************************************************************
 * E-commerce / web thương mại adapter (stub).
 *
 * Senior: implement test() and any sync methods in this file. Do not change Settings UI.
 *************************************************************************************/

require_once 'modules/Vtiger/helpers/NkApi/Adapter.php';

class NkApi_Ecommerce_Adapter extends NkApi_Adapter {

	public function code() {
		return 'ecommerce';
	}

	public function label() {
		return 'Web thương mại';
	}

	public function description() {
		return 'Đồng bộ đơn / tồn kho với website thương mại điện tử.';
	}

	public function icon() {
		return 'website';
	}

	public function hint() {
		return 'Chưa có API. Lưu URL + credential tại đây; senior implement NkApi_Ecommerce_Adapter.';
	}
}
