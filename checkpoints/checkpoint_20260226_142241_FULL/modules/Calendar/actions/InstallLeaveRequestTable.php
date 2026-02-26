<?php
/*+***********************************************************************************
 * Tạo bảng đơn nghỉ phép (chạy một lần: index.php?module=Calendar&action=InstallLeaveRequestTable).
 * Chỉ Admin mới được gọi.
 *************************************************************************************/

class Calendar_InstallLeaveRequestTable_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$user = Users_Record_Model::getCurrentUserModel();
		if (!$user || !$user->isAdminUser()) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		$table = 'vtiger_leaverequest';
		if (Vtiger_Utils::CheckTable($table)) {
			$response->setResult(array('success' => true, 'message' => 'Table already exists.'));
			$response->emit();
			return;
		}
		$sql = "CREATE TABLE `vtiger_leaverequest` (
		  `leaverequestid` int(11) NOT NULL AUTO_INCREMENT,
		  `subject` varchar(255) DEFAULT '',
		  `leave_type` varchar(50) DEFAULT 'paid',
		  `approval_status` varchar(50) DEFAULT 'pending',
		  `half_day` tinyint(1) DEFAULT 0,
		  `date_start` date DEFAULT NULL,
		  `due_date` date DEFAULT NULL,
		  `description` text,
		  `created_user_id` int(11) DEFAULT NULL,
		  `createdtime` datetime DEFAULT NULL,
		  PRIMARY KEY (`leaverequestid`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		PearDatabase::getInstance()->pquery($sql, array());
		$response->setResult(array('success' => true, 'message' => 'Table created.'));
		$response->emit();
	}
}
