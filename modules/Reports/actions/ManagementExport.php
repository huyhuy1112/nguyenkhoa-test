<?php

class Reports_ManagementExport_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		// Cho phép user có quyền xem Reports (không phân biệt Project/Task)
		if (!Users_Privileges_Model::isPermitted('Reports', 'DetailView')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
		}
	}

	public function process(Vtiger_Request $request) {
		$format = strtolower($request->get('format'));
		if (!in_array($format, array('excel', 'csv', 'pdf'), true)) {
			$format = 'excel';
		}

		$reportType = strtolower($request->get('report_type'));
		if (!in_array($reportType, array('all', 'project', 'task'), true)) {
			$reportType = 'all';
		}
		$filters = array(
			'date_from'   => $request->get('date_from'),
			'date_to'     => $request->get('date_to'),
			'owner_id'    => $request->get('owner_id'),
			'report_type' => $reportType,
		);

		list($projectRows, $taskRows) = $this->buildData($filters);

		if ($format === 'pdf') {
			$this->exportPdf($filters, $projectRows, $taskRows);
		} else {
			// excel & csv đều dùng CSV – Excel vẫn mở bình thường
			$this->exportCsv($filters, $projectRows, $taskRows, $format);
		}
	}

	protected function buildData(array $filters) {
		// Lấy logic y như trong Reports_Management_View nhưng viết lại đơn giản tại đây
		$projectRows = array();
		$taskRows = array();

		$dateFrom = !empty($filters['date_from']) ? $filters['date_from'] : null;
		$dateTo   = !empty($filters['date_to']) ? $filters['date_to'] : null;
		$ownerIdFilter = !empty($filters['owner_id']) ? $filters['owner_id'] : null;
		$reportType = isset($filters['report_type']) ? $filters['report_type'] : 'all';

		// PROJECT (chỉ khi report_type = all hoặc project)
		if (($reportType === 'all' || $reportType === 'project') && Users_Privileges_Model::isPermitted('Project', 'DetailView')) {
			try {
				$listViewModel = Vtiger_ListView_Model::getInstance('Project', '0', array());
				$pagingModel = new Vtiger_Paging_Model();
				$pagingModel->set('page', 1);
				$pagingModel->set('limit', 1000);
				$entries = $listViewModel->getListViewEntries($pagingModel);

				$projectIds = array();
				foreach ($entries as $recordId => $recordModel) {
					if (!($recordModel instanceof Vtiger_Record_Model)) {
						continue;
					}
					$projectIds[] = $recordId;
				}
				$taskCounts = $this->getTaskCountsByProject($projectIds);

				foreach ($entries as $recordId => $recordModel) {
					if (!($recordModel instanceof Vtiger_Record_Model)) {
						continue;
					}
					$raw = method_exists($recordModel, 'getRawData') ? $recordModel->getRawData() : array();
					$ownerId = null;
					if (is_array($raw) && isset($raw['smownerid']) && $raw['smownerid'] !== '') {
						$ownerId = $raw['smownerid'];
					} else {
						$ownerId = $recordModel->get('smownerid');
					}
					if ($ownerIdFilter !== null && (string)$ownerId !== (string)$ownerIdFilter) {
						continue;
					}

					$startRaw = is_array($raw) && isset($raw['startdate']) ? $raw['startdate'] : $recordModel->get('startdate');
					$endRaw   = is_array($raw) && isset($raw['enddate']) ? $raw['enddate'] : $recordModel->get('enddate');
					if ($dateFrom || $dateTo) {
						$start = $startRaw ?: null;
						$end   = $endRaw ?: $start;
						$include = true;
						if ($dateFrom && $end && $end < $dateFrom) $include = false;
						if ($dateTo && $start && $start > $dateTo) $include = false;
						if (!$include) continue;
					}

					$ownerName = $ownerId ? getOwnerName($ownerId) : '';
					$title = $recordModel->get('projectname');
					if ($title === null || $title === '') {
						$title = $recordModel->getDisplayValue('projectname') ?: ('Project #' . $recordId);
					}
					$counts = isset($taskCounts[$recordId]) ? $taskCounts[$recordId] : array('total' => 0, 'done' => 0, 'in_progress' => 0);
					$projectRows[] = array(
						'id' => $recordId,
						'title' => $title,
						'status' => $recordModel->getDisplayValue('projectstatus'),
						'start' => $recordModel->getDisplayValue('startdate'),
						'end' => $recordModel->getDisplayValue('enddate'),
						'owner' => $ownerName,
						'task_count' => (int) $counts['total'],
						'task_done' => (int) $counts['done'],
						'task_in_progress' => (int) $counts['in_progress'],
					);
				}
			} catch (Exception $e) {
				// ignore
			}
		}

		// TASK (chỉ khi report_type = all hoặc task)
		if (($reportType === 'all' || $reportType === 'task') && Users_Privileges_Model::isPermitted('ProjectTask', 'DetailView')) {
			try {
				$listViewModel = Vtiger_ListView_Model::getInstance('ProjectTask', '0', array());
				$pagingModel = new Vtiger_Paging_Model();
				$pagingModel->set('page', 1);
				$pagingModel->set('limit', 1000);
				$entries = $listViewModel->getListViewEntries($pagingModel);

				foreach ($entries as $recordId => $recordModel) {
					if (!($recordModel instanceof Vtiger_Record_Model)) {
						continue;
					}
					$raw = method_exists($recordModel, 'getRawData') ? $recordModel->getRawData() : array();
					$ownerId = null;
					if (is_array($raw) && isset($raw['smownerid']) && $raw['smownerid'] !== '') {
						$ownerId = $raw['smownerid'];
					} else {
						$ownerId = $recordModel->get('smownerid');
					}
					if ($ownerIdFilter !== null && (string)$ownerId !== (string)$ownerIdFilter) {
						continue;
					}
					$startRaw = is_array($raw) && isset($raw['startdate']) ? $raw['startdate'] : $recordModel->get('startdate');
					$endRaw   = is_array($raw) && isset($raw['enddate']) ? $raw['enddate'] : $recordModel->get('enddate');
					if ($dateFrom || $dateTo) {
						$due = $endRaw ?: $startRaw;
						if ($dateFrom && $due && $due < $dateFrom) continue;
						if ($dateTo && $due && $due > $dateTo) continue;
					}

					$ownerName = $ownerId ? getOwnerName($ownerId) : '';
					$title = $recordModel->get('projecttaskname');
					if ($title === null || $title === '') {
						$title = $recordModel->getDisplayValue('projecttaskname') ?: ('Task #' . $recordId);
					}
					$taskRows[] = array(
						'id' => $recordId,
						'title' => $title,
						'status' => $recordModel->getDisplayValue('projecttaskprogress'),
						'due' => $recordModel->getDisplayValue('enddate') ?: $recordModel->getDisplayValue('startdate'),
						'owner' => $ownerName,
					);
				}
			} catch (Exception $e) {
				// ignore
			}
		}

		return array($projectRows, $taskRows);
	}

	protected function getTaskCountsByProject(array $projectIds) {
		$out = array();
		if (empty($projectIds)) {
			return $out;
		}
		$db = PearDatabase::getInstance();
		$placeholders = implode(',', array_fill(0, count($projectIds), '?'));
		$sql = "SELECT projectid,
			COUNT(*) AS total,
			SUM(CASE WHEN (COALESCE(projecttaskprogress, 0) + 0) >= 100 THEN 1 ELSE 0 END) AS done,
			SUM(CASE WHEN (COALESCE(projecttaskprogress, 0) + 0) < 100 AND (COALESCE(projecttaskprogress, -1) + 0) >= 0 THEN 1 ELSE 0 END) AS in_progress
			FROM vtiger_projecttask
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_projecttask.projecttaskid AND vtiger_crmentity.deleted = 0
			WHERE projectid IN ({$placeholders})
			GROUP BY projectid";
		$res = $db->pquery($sql, $projectIds);
		while ($row = $db->fetchByAssoc($res)) {
			$pid = $row['projectid'];
			$out[$pid] = array(
				'total' => (int) $row['total'],
				'done' => (int) $row['done'],
				'in_progress' => (int) $row['in_progress'],
			);
		}
		return $out;
	}

	protected function exportCsv(array $filters, array $projectRows, array $taskRows, $format) {
		$filenameSuffix = date('Ymd_His');
		$extension = $format === 'excel' ? 'xls' : 'csv';
		$filename = "management_report_{$filenameSuffix}.{$extension}";

		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Pragma: no-cache');
		header('Expires: 0');

		echo "\xEF\xBB\xBF";
		$out = fopen('php://output', 'w');

		fputcsv($out, array('Management Report', 'Generated at', date('Y-m-d H:i:s')));
		fputcsv($out, array('Date From', $filters['date_from'], 'Date To', $filters['date_to'], 'Owner', $filters['owner_id']));
		fputcsv($out, array());

		if (!empty($projectRows)) {
			fputcsv($out, array('Project Report'));
			fputcsv($out, array('Project', 'Start', 'End', 'Assigned To', 'Tasks', 'Done', 'In Progress', 'Status'));
			foreach ($projectRows as $row) {
				fputcsv($out, array(
					$row['title'],
					$row['start'],
					$row['end'],
					$row['owner'],
					$row['task_count'],
					$row['task_done'],
					$row['task_in_progress'],
					$row['status'],
				));
			}
			fputcsv($out, array());
		}

		if (!empty($taskRows)) {
			fputcsv($out, array('Task Report'));
			fputcsv($out, array('Task', 'Due date', 'Assigned To', 'Status (%)'));
			foreach ($taskRows as $row) {
				fputcsv($out, array(
					$row['title'],
					$row['due'],
					$row['owner'],
					$row['status'],
				));
			}
		}

		fclose($out);
		exit;
	}

	protected function exportPdf(array $filters, array $projectRows, array $taskRows) {
		require_once 'libraries/tcpdf/tcpdf.php';
		$filenameSuffix = date('Ymd_His');
		$filename = "management_report_{$filenameSuffix}.pdf";

		$pdf = new TCPDF();
		$pdf->SetCreator('vtiger');
		$pdf->SetAuthor('vtiger');
		$pdf->SetTitle('Management Report');
		$pdf->SetMargins(10, 15, 10);
		$pdf->AddPage();
		$pdf->SetFont('dejavusans', '', 9);

		$html = '<h2>Management Report</h2>';
		$html .= '<p><strong>Generated at:</strong> ' . date('Y-m-d H:i:s') . '</p>';
		$html .= '<p><strong>Date From:</strong> ' . htmlspecialchars($filters['date_from']) .
			' &nbsp; <strong>Date To:</strong> ' . htmlspecialchars($filters['date_to']) .
			' &nbsp; <strong>Owner:</strong> ' . htmlspecialchars($filters['owner_id']) . '</p>';

		if (!empty($projectRows)) {
			$html .= '<h3>Project Report</h3>';
			$html .= '<table border="1" cellspacing="0" cellpadding="3">
				<tr style="background-color:#f1f5f9;">
					<th>Project</th><th>Start</th><th>End</th><th>Assigned To</th>
					<th>Tasks</th><th>Done</th><th>In Progress</th><th>Status</th>
				</tr>';
			foreach ($projectRows as $row) {
				$html .= '<tr>
					<td>' . htmlspecialchars($row['title']) . '</td>
					<td>' . htmlspecialchars($row['start']) . '</td>
					<td>' . htmlspecialchars($row['end']) . '</td>
					<td>' . htmlspecialchars($row['owner']) . '</td>
					<td align="right">' . (int) $row['task_count'] . '</td>
					<td align="right">' . (int) $row['task_done'] . '</td>
					<td align="right">' . (int) $row['task_in_progress'] . '</td>
					<td>' . htmlspecialchars($row['status']) . '</td>
				</tr>';
			}
			$html .= '</table><br/>';
		}

		if (!empty($taskRows)) {
			$html .= '<h3>Task Report</h3>';
			$html .= '<table border="1" cellspacing="0" cellpadding="3">
				<tr style="background-color:#f1f5f9;">
					<th>Task</th><th>Due date</th><th>Assigned To</th><th>Status</th>
				</tr>';
			foreach ($taskRows as $row) {
				$html .= '<tr>
					<td>' . htmlspecialchars($row['title']) . '</td>
					<td>' . htmlspecialchars($row['due']) . '</td>
					<td>' . htmlspecialchars($row['owner']) . '</td>
					<td>' . htmlspecialchars($row['status']) . '</td>
				</tr>';
			}
			$html .= '</table>';
		}

		$pdf->writeHTML($html, true, false, true, false, '');
		$pdf->Output($filename, 'D');
		exit;
	}
}

