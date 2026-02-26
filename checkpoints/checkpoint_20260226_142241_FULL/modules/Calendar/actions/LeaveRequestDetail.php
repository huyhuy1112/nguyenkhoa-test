<?php
/*+***********************************************************************************
 * Chi tiết đơn nghỉ phép (modal). CEO/Admin có nút Duyệt/Từ chối.
 *************************************************************************************/

class Calendar_LeaveRequestDetail_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$user = Users_Record_Model::getCurrentUserModel();
		if (!$user) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function process(Vtiger_Request $request) {
		$recordId = (int) $request->get('record');
		if (!$recordId) {
			echo $this->wrapModal(vtranslate('LBL_RECORD_NOT_FOUND', 'Calendar'), '', false);
			return null;
		}

		if (!Vtiger_Utils::CheckTable('vtiger_leaverequest')) {
			echo $this->wrapModal(vtranslate('LBL_TABLE_NOT_FOUND', 'Calendar'), '', false);
			return null;
		}

		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT leaverequestid, subject, leave_type, approval_status, half_day, date_start, due_date, description, created_user_id, createdtime FROM vtiger_leaverequest WHERE leaverequestid = ?",
			array($recordId)
		);
		if (!$adb->num_rows($res)) {
			echo $this->wrapModal(vtranslate('LBL_RECORD_NOT_FOUND', 'Calendar'), '', false);
			return null;
		}

		$row = $adb->fetchByAssoc($res);
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$userId = $currentUser->getId();
		$createdBy = (int) $row['created_user_id'];
		$isAdmin = $currentUser->isAdminUser();
		$isCEO = $this->isUserCEO($currentUser);
		$canView = ($createdBy === $userId) || $isAdmin || $isCEO;
		if (!$canView) {
			echo $this->wrapModal(vtranslate('LBL_PERMISSION_DENIED'), '', false);
			return null;
		}

		$canApprove = $isAdmin || $isCEO;
		$status = $row['approval_status'];
		$leaveTypeLabels = array('paid' => vtranslate('LBL_LEAVE_TYPE_PAID', 'Calendar'), 'unpaid' => vtranslate('LBL_LEAVE_TYPE_UNPAID', 'Calendar'));
		$statusLabels = array('pending' => vtranslate('LBL_LEAVE_STATUS_PENDING', 'Calendar'), 'approved' => vtranslate('LBL_LEAVE_STATUS_APPROVED', 'Calendar'), 'rejected' => vtranslate('LBL_LEAVE_STATUS_REJECTED', 'Calendar'));

		$subject = htmlspecialchars(decode_html($row['subject'] ?: vtranslate('LBL_LEAVE_REQUEST', 'Calendar')));
		$leaveType = isset($leaveTypeLabels[$row['leave_type']]) ? $leaveTypeLabels[$row['leave_type']] : $row['leave_type'];
		$approvalStatus = isset($statusLabels[$status]) ? $statusLabels[$status] : $status;
		$halfDay = !empty($row['half_day']) ? vtranslate('LBL_LEAVE_HALF_DAY_YES', 'Calendar') : vtranslate('LBL_LEAVE_HALF_DAY_NO', 'Calendar');
		$dateStart = $row['date_start'] ? DateTimeField::convertToUserFormat($row['date_start']) : '-';
		$dueDate = $row['due_date'] ? DateTimeField::convertToUserFormat($row['due_date']) : '-';
		$description = htmlspecialchars(decode_html($row['description'] ?: '-'));

		// Người xin nghỉ (để chấm công)
		$requesterName = '-';
		if (!empty($row['created_user_id'])) {
			$creatorModel = Users_Record_Model::getInstanceById($row['created_user_id'], 'Users');
			if ($creatorModel) {
				$requesterName = htmlspecialchars(decode_html($creatorModel->getName()));
			}
		}

		$body = '<div class="form-horizontal">';
		$body .= '<div class="form-group"><label class="control-label col-sm-4">' . vtranslate('LBL_LEAVE_REQUESTED_BY', 'Calendar') . '</label><div class="col-sm-8"><p class="form-control-static">' . $requesterName . '</p></div></div>';
		$body .= '<div class="form-group"><label class="control-label col-sm-4">' . vtranslate('Subject', 'Calendar') . '</label><div class="col-sm-8"><p class="form-control-static">' . $subject . '</p></div></div>';
		$body .= '<div class="form-group"><label class="control-label col-sm-4">' . vtranslate('LBL_LEAVE_TYPE', 'Calendar') . '</label><div class="col-sm-8"><p class="form-control-static">' . htmlspecialchars($leaveType) . '</p></div></div>';
		$body .= '<div class="form-group"><label class="control-label col-sm-4">' . vtranslate('LBL_LEAVE_APPROVAL_STATUS', 'Calendar') . '</label><div class="col-sm-8"><p class="form-control-static">' . htmlspecialchars($approvalStatus) . '</p></div></div>';
		$body .= '<div class="form-group"><label class="control-label col-sm-4">' . vtranslate('LBL_LEAVE_HALF_DAY', 'Calendar') . '</label><div class="col-sm-8"><p class="form-control-static">' . $halfDay . '</p></div></div>';
		$body .= '<div class="form-group"><label class="control-label col-sm-4">' . vtranslate('LBL_START_DATE', 'Calendar') . '</label><div class="col-sm-8"><p class="form-control-static">' . htmlspecialchars($dateStart) . '</p></div></div>';
		$body .= '<div class="form-group"><label class="control-label col-sm-4">' . vtranslate('LBL_END_DATE', 'Calendar') . '</label><div class="col-sm-8"><p class="form-control-static">' . htmlspecialchars($dueDate) . '</p></div></div>';
		$body .= '<div class="form-group"><label class="control-label col-sm-4">' . vtranslate('LBL_ADD_DESCRIPTION', 'Calendar') . '</label><div class="col-sm-8"><p class="form-control-static">' . nl2br($description) . '</p></div></div>';
		$body .= '</div>';

		$footer = '';
		if ($canApprove && $status === 'pending') {
			$footer = '<button type="button" class="btn btn-success btn-approve-leave" data-record="' . (int)$recordId . '" data-status="approved">' . vtranslate('LBL_LEAVE_APPROVE', 'Calendar') . '</button>';
			$footer .= ' <button type="button" class="btn btn-danger btn-approve-leave" data-record="' . (int)$recordId . '" data-status="rejected">' . vtranslate('LBL_LEAVE_REJECT', 'Calendar') . '</button>';
		}
		if ($canApprove) {
			$footer .= ' <button type="button" class="btn btn-default btn-delete-leave" data-record="' . (int)$recordId . '">' . vtranslate('LBL_LEAVE_DELETE', 'Calendar') . '</button>';
		}
		$footer .= ' <button type="button" class="btn btn-default" data-dismiss="modal">' . vtranslate('LBL_CLOSE', 'Vtiger') . '</button>';

		echo $this->wrapModal(vtranslate('LBL_LEAVE_REQUEST_DETAIL', 'Calendar'), $body, $footer);
		return null;
	}

	protected function wrapModal($title, $body, $footer) {
		if ($footer === false) {
			$footer = '<button type="button" class="btn btn-default" data-dismiss="modal">' . vtranslate('LBL_CLOSE', 'Vtiger') . '</button>';
		}
		return '<div class="modal fade in" tabindex="-1" role="dialog" style="display:block;"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">' . htmlspecialchars($title) . '</h4></div><div class="modal-body">' . $body . '</div><div class="modal-footer">' . $footer . '</div></div></div></div><div class="modal-backdrop fade in"></div>';
	}

	protected function isUserCEO($userModel) {
		$roleId = $userModel->get('roleid');
		if (empty($roleId)) return false;
		$adb = PearDatabase::getInstance();
		$r = $adb->pquery("SELECT rolename FROM vtiger_role WHERE roleid = ?", array($roleId));
		if ($adb->num_rows($r)) {
			$name = strtolower(trim($adb->query_result($r, 0, 'rolename')));
			return ($name === 'ceo' || preg_match('/\bceo\b/', $name));
		}
		return false;
	}
}
