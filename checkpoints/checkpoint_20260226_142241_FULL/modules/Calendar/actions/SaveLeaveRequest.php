<?php
/*+***********************************************************************************
 * Lưu đơn nghỉ phép (tạo mới hoặc cập nhật).
 * Chỉ CEO/Admin được phép đổi trạng thái phê duyệt (approved/rejected).
 *************************************************************************************/

class Calendar_SaveLeaveRequest_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		$adb = PearDatabase::getInstance();
		if (!Vtiger_Utils::CheckTable('vtiger_leaverequest')) {
			$response->setError(vtranslate('LBL_TABLE_NOT_FOUND', 'Calendar'));
			$response->emit();
			return;
		}

		$currentUser = Users_Record_Model::getCurrentUserModel();
		$userId = $currentUser->getId();
		$isAdmin = $currentUser->isAdminUser();
		$isCEO = $this->isUserCEO($currentUser);
		$canApprove = $isAdmin || $isCEO;

		$recordId = $request->get('leaverequestid');
		$approvalStatus = $request->get('approval_status');

		if ($recordId) {
			// Update: chỉ cho phép đổi approval_status nếu là CEO/Admin
			$res = $adb->pquery("SELECT created_user_id FROM vtiger_leaverequest WHERE leaverequestid = ?", array($recordId));
			if (!$adb->num_rows($res)) {
				$response->setError(vtranslate('LBL_RECORD_NOT_FOUND', 'Calendar'));
				$response->emit();
				return;
			}
			$createdBy = $adb->query_result($res, 0, 'created_user_id');
			if ($canApprove && in_array($approvalStatus, array('approved', 'rejected', 'pending'))) {
				$adb->pquery("UPDATE vtiger_leaverequest SET approval_status = ? WHERE leaverequestid = ?", array($approvalStatus, $recordId));
				$response->setResult(array('success' => true, 'id' => $recordId));
			} else if (!$canApprove && $createdBy == $userId) {
				// Người tạo có thể sửa nội dung (trừ approval_status) khi còn pending
				$subject = $request->get('subject');
				$leaveType = $request->get('leave_type');
				$halfDay = (int) $request->get('half_day');
				$dateStart = $this->parseDateToDb($request->get('date_start'), $currentUser);
				$dueDate = $request->get('due_date');
				$dueDate = empty($dueDate) ? $dateStart : $this->parseDateToDb($dueDate, $currentUser);
				$description = $request->get('description');
				$adb->pquery("UPDATE vtiger_leaverequest SET subject = ?, leave_type = ?, half_day = ?, date_start = ?, due_date = ?, description = ? WHERE leaverequestid = ? AND approval_status = 'pending'",
					array($subject, $leaveType, $halfDay, $dateStart, $dueDate, $description, $recordId));
				$response->setResult(array('success' => true, 'id' => $recordId));
			} else {
				$response->setError(vtranslate('LBL_PERMISSION_DENIED'));
			}
		} else {
			// Create
			$subject = $request->get('subject');
			$leaveType = in_array($request->get('leave_type'), array('paid', 'unpaid')) ? $request->get('leave_type') : 'paid';
			$halfDay = (int) $request->get('half_day');
			$dateStart = $this->parseDateToDb($request->get('date_start'), $currentUser);
			$dueDate = $request->get('due_date');
			$dueDate = empty($dueDate) ? $dateStart : $this->parseDateToDb($dueDate, $currentUser);
			$description = $request->get('description');
			if (empty($dateStart)) {
				$response->setError(vtranslate('LBL_PLEASE_SELECT_DATE', 'Calendar'));
				$response->emit();
				return;
			}
			$adb->pquery("INSERT INTO vtiger_leaverequest (subject, leave_type, approval_status, half_day, date_start, due_date, description, created_user_id, createdtime) VALUES (?,?,?,?,?,?,?,?,?)",
				array($subject, $leaveType, 'pending', $halfDay, $dateStart, $dueDate, $description, $userId, date('Y-m-d H:i:s')));
			$newId = $adb->getLastInsertID();
			$response->setResult(array('success' => true, 'id' => $newId));
		}

		$response->emit();
	}

	protected function isUserCEO($userModel) {
		$roleId = $userModel->get('roleid');
		if (empty($roleId)) {
			return false;
		}
		$adb = PearDatabase::getInstance();
		$r = $adb->pquery("SELECT rolename FROM vtiger_role WHERE roleid = ?", array($roleId));
		if ($adb->num_rows($r)) {
			$name = strtolower(trim($adb->query_result($r, 0, 'rolename')));
			return ($name === 'ceo' || preg_match('/\bceo\b/', $name));
		}
		return false;
	}

	protected function parseDateToDb($dateStr, $userModel) {
		if (empty($dateStr)) {
			return '';
		}
		if (preg_match('/^\d{4}-\d{2}-\d{2}/', $dateStr)) {
			return substr($dateStr, 0, 10);
		}
		return DateTimeField::__convertToDBFormat($dateStr, $userModel->get('date_format'));
	}
}
