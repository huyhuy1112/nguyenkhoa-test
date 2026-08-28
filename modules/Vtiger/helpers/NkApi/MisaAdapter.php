<?php
/*+***********************************************************************************
 * MISA accounting adapter (stub).
 *
 * Senior: implement test() and transfer() in this file. Do not change Settings UI.
 *************************************************************************************/

require_once 'modules/Vtiger/helpers/NkApi/Adapter.php';

class NkApi_Misa_Adapter extends NkApi_Adapter {

	public function code() {
		return 'misa';
	}

	public function label() {
		return 'MISA';
	}

	public function description() {
		return 'Đẩy đơn hàng (Sales Order) sang kế toán MISA.';
	}

	public function hint() {
		return 'Nút “Chuyển MISA” trên đơn hàng gọi NkApi_Misa_Adapter::transfer(). Senior implement method đó — không sửa UI.';
	}

	/**
	 * Called from SalesOrder_TransferToMisa_Action.
	 * @param Vtiger_Record_Model $soModel
	 * @return array {success?, message?, error?}
	 */
	public function transfer($soModel) {
		if (!$this->isEnabled()) {
			return array(
				'error' => 'Kết nối MISA đang tắt. Bật trong Cài đặt → Tích hợp hệ thống.',
			);
		}
		$row = NkApiConnection::getRow($this->code());
		if (!$this->hasCredentials($row) && empty($row['base_url'])) {
			return array(
				'error' => 'Chưa cấu hình MISA (URL / thông tin đăng nhập). Vào Cài đặt → Tích hợp hệ thống.',
			);
		}
		if (!$this->isImplemented()) {
			return array(
				'error' => 'Adapter MISA chưa được cắm API. Cấu hình đã lưu tại Cài đặt → Tích hợp hệ thống.',
			);
		}

		// Senior: POST $soModel to MISA here, then:
		// NkApiConnection::saveRow('misa', array('status' => 'ok', 'last_sync' => date('Y-m-d H:i:s'), 'last_error' => ''), $userId);
		return array('error' => 'Adapter MISA chưa được implement.');
	}
}
