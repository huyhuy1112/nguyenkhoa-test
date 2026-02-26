<?php
/*+**********************************************************************************
 * Documents History view - hiển thị lịch sử chỉnh sửa / di chuyển / xóa / upload
 * Dữ liệu lấy từ vtiger_modtracker_basic (ModTracker)
 ************************************************************************************/

class Documents_History_View extends Vtiger_Index_View {

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$db = PearDatabase::getInstance();
		$params = array('Documents');

		// Lấy 100 hoạt động gần nhất của module Documents
		$sql = "SELECT b.id, b.crmid, b.module, b.whodid, b.changedon, b.status,
					ce.label AS record_label,
					u.first_name, u.last_name, u.user_name
				FROM vtiger_modtracker_basic b
				INNER JOIN vtiger_crmentity ce ON ce.crmid = b.crmid AND ce.deleted = 0
				LEFT JOIN vtiger_users u ON u.id = b.whodid
				WHERE b.module = ?
				ORDER BY b.changedon DESC
				LIMIT 100";

		$result = $db->pquery($sql, $params);
		$rows = array();
		if ($result) {
			while ($row = $db->fetchByAssoc($result)) {
				$label = $row['record_label'];
				$userFullName = trim($row['first_name'] . ' ' . $row['last_name']);
				if ($userFullName === '') {
					$userFullName = $row['user_name'];
				}
				$action = $this->getStatusLabel((int)$row['status']);
				$rows[] = array(
					'id' => (int)$row['id'],
					'crmid' => (int)$row['crmid'],
					'label' => $label,
					'changedon' => $row['changedon'],
					'user' => $userFullName,
					'action' => $action,
					'detailUrl' => "index.php?module=Documents&view=Detail&record=".(int)$row['crmid']
				);
			}
		}

		$viewer->assign('HISTORY_ROWS', $rows);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->view('History.tpl', $moduleName);
	}

	protected function getStatusLabel($status) {
		// Tham khảo ModTracker::$CREATED, UPDATED, DELETED...
		require_once 'modules/ModTracker/ModTracker.php';
		switch ($status) {
			case ModTracker::$CREATED: return 'Created';
			case ModTracker::$UPDATED: return 'Updated';
			case ModTracker::$DELETED: return 'Deleted';
			case ModTracker::$RESTORED: return 'Restored';
			case ModTracker::$LINK: return 'Related';
			case ModTracker::$UNLINK: return 'Unrelated';
			default: return 'Updated';
		}
	}
}

