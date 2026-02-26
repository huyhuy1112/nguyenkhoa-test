<?php
/*+***********************************************************************************
 * Feed đơn nghỉ phép CHỈ cho mini calendar (không hiển thị trên full calendar).
 * Chỉ Admin và CEO thấy tất cả đơn; mọi role khác (kể cả Sales Manager) chỉ thấy đơn của mình.
 *************************************************************************************/

class Calendar_FeedMiniLeave_Action extends Vtiger_BasicAjax_Action {

	public function process(Vtiger_Request $request) {
		// Tránh cache: user sale không được thấy response cũ của admin
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		header('Pragma: no-cache');

		$start = $request->get('start');
		$end = $request->get('end');
		$result = array();
		$adb = PearDatabase::getInstance();
		if (!Vtiger_Utils::CheckTable('vtiger_leaverequest')) {
			// Tự tạo bảng đơn nghỉ phép lần đầu (chỉ Admin)
			$currentUser = Users_Record_Model::getCurrentUserModel();
			if ($currentUser && $currentUser->isAdminUser()) {
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
				$adb->pquery($sql, array());
			}
			if (!Vtiger_Utils::CheckTable('vtiger_leaverequest')) {
				echo json_encode($result);
				return;
			}
		}
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser) {
			echo json_encode($result);
			return;
		}
		$userId = (int) $currentUser->getId();
		$startDb = DateTimeField::convertToDBFormat($start);
		$endDb = DateTimeField::convertToDBFormat($end);

		// Chỉ Admin (is_admin trong DB) hoặc role CEO được xem tất cả đơn; mọi role khác CHỈ thấy đơn của mình (không thấy ngày nghỉ của nhau)
		$canSeeAll = $this->userCanSeeAllLeaveRequests($adb, $userId);

		$query = "SELECT leaverequestid, subject, leave_type, approval_status, half_day, date_start, due_date, created_user_id
			FROM vtiger_leaverequest
			WHERE (date_start BETWEEN ? AND ?) OR (due_date BETWEEN ? AND ?) OR (date_start <= ? AND due_date >= ?)";
		$params = array($startDb, $endDb, $startDb, $endDb, $startDb, $endDb);

		if (!$canSeeAll) {
			if ($userId <= 0) {
				echo json_encode($result);
				return;
			}
			$query .= " AND created_user_id = ?";
			$params[] = $userId;
		}

		$query .= " ORDER BY date_start";
		$res = $adb->pquery($query, $params);

		$leaveTypeLabels = array('paid' => 'Nghỉ phép có lương', 'unpaid' => 'Nghỉ phép không lương');
		$statusLabels = array('pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối');

		while ($row = $adb->fetchByAssoc($res)) {
			$title = decode_html($row['subject']);
			if (empty($title)) {
				$title = isset($leaveTypeLabels[$row['leave_type']]) ? $leaveTypeLabels[$row['leave_type']] : $row['leave_type'];
			}
			$status = isset($statusLabels[$row['approval_status']]) ? $statusLabels[$row['approval_status']] : $row['approval_status'];
			$title .= ' (' . $status . ')';
			if (!empty($row['half_day'])) {
				$title .= ' [Nửa ngày]';
			}

			$endDate = $row['due_date'];
			if (empty($endDate)) {
				$endDate = $row['date_start'];
			}
			$endDate = date('Y-m-d', strtotime($endDate . ' +1 day'));

			$result[] = array(
				'id' => 'leaverequest_' . $row['leaverequestid'],
				'title' => $title,
				'start' => $row['date_start'],
				'end' => $endDate,
				'allDay' => true,
				'color' => $row['approval_status'] === 'approved' ? '#5cb85c' : ($row['approval_status'] === 'rejected' ? '#d9534f' : '#f0ad4e'),
				'textColor' => '#fff',
				'url' => 'index.php?module=Calendar&view=LeaveRequestDetail&record=' . $row['leaverequestid'],
				'module' => 'LeaveRequest',
				'editable' => false
			);
		}

		echo json_encode($result);
	}

	/**
	 * Kiểm tra user có được xem tất cả đơn nghỉ (chỉ Admin hoặc CEO). Dùng trực tiếp DB để tránh cache/session sai.
	 */
	protected function userCanSeeAllLeaveRequests($adb, $userId) {
		if ($userId <= 0) {
			return false;
		}
		$r = $adb->pquery("SELECT u.is_admin, r.rolename FROM vtiger_users u LEFT JOIN vtiger_role r ON r.roleid = u.roleid WHERE u.id = ?", array($userId));
		if (!$adb->num_rows($r)) {
			return false;
		}
		$isAdmin = $adb->query_result($r, 0, 'is_admin');
		$rolename = strtolower(trim((string) $adb->query_result($r, 0, 'rolename')));
		if ($isAdmin === 'on' || $isAdmin === 1 || $isAdmin === '1') {
			return true;
		}
		return ($rolename === 'ceo' || preg_match('/\bceo\b/', $rolename));
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

	protected function parseDateToDb($dateStr, $user) {
		if (empty($dateStr)) {
			return date('Y-m-d');
		}
		if (preg_match('/^\d{4}-\d{2}-\d{2}/', $dateStr)) {
			return substr($dateStr, 0, 10);
		}
		return DateTimeField::__convertToDBFormat($dateStr, $user->get('date_format'));
	}
}
