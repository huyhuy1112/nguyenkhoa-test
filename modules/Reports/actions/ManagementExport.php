<?php

require_once 'include/utils/TdbDisplayUtils.php';

class Reports_ManagementExport_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		if (!Users_Privileges_Model::isPermitted('Reports', 'DetailView')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
		}
	}

	public function process(Vtiger_Request $request) {
		@ini_set('display_errors', '0');
		@ini_set('zlib.output_compression', '0');

		$format = strtolower((string) $request->get('format'));
		if (!in_array($format, array('excel', 'csv', 'pdf'), true)) {
			$format = 'excel';
		}

		$reportType = strtolower((string) $request->get('report_type'));
		if (!in_array($reportType, array('all', 'project', 'task'), true)) {
			$reportType = 'all';
		}

		$filters = array(
			'date_from'   => $request->get('date_from'),
			'date_to'     => $request->get('date_to'),
			'owner_id'    => $request->get('owner_id'),
			'report_type' => $reportType,
		);

		list($projectRows, $taskRows) = $this->buildData($filters, $request);

		if ($format === 'pdf') {
			$this->exportPdf($filters, $projectRows, $taskRows);
		} elseif ($format === 'excel') {
			$this->exportExcel($filters, $projectRows, $taskRows);
		} else {
			$this->exportCsv($filters, $projectRows, $taskRows);
		}
	}

	protected function buildData(array $filters, Vtiger_Request $request) {
		$projectRows = array();
		$taskRows = array();

		$dateFrom = !empty($filters['date_from']) ? $filters['date_from'] : null;
		$dateTo   = !empty($filters['date_to']) ? $filters['date_to'] : null;
		$ownerIdFilter = !empty($filters['owner_id']) ? $filters['owner_id'] : null;
		$reportType = isset($filters['report_type']) ? $filters['report_type'] : 'all';

		if (($reportType === 'all' || $reportType === 'project') && Users_Privileges_Model::isPermitted('Project', 'DetailView')) {
			try {
				$listViewModel = Vtiger_ListView_Model::getInstance('Project', '0', array());
				$pagingModel = new Vtiger_Paging_Model();
				$pagingModel->set('page', 1);
				$pagingModel->set('limit', 1000);
				$entries = $listViewModel->getListViewEntries($pagingModel);

				$projectIds = array();
				foreach ($entries as $recordId => $recordModel) {
					if ($recordModel instanceof Vtiger_Record_Model) {
						$projectIds[] = $recordId;
					}
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
					if ($ownerIdFilter !== null && (string) $ownerId !== (string) $ownerIdFilter) {
						continue;
					}

					$startRaw = is_array($raw) && isset($raw['startdate']) ? $raw['startdate'] : $recordModel->get('startdate');
					$endRaw   = is_array($raw) && isset($raw['enddate']) ? $raw['enddate'] : $recordModel->get('enddate');
					if ($dateFrom || $dateTo) {
						$start = $startRaw ?: null;
						$end   = $endRaw ?: $start;
						$include = true;
						if ($dateFrom && $end && $end < $dateFrom) {
							$include = false;
						}
						if ($dateTo && $start && $start > $dateTo) {
							$include = false;
						}
						if (!$include) {
							continue;
						}
					}

					$ownerName = $ownerId ? getOwnerName($ownerId) : '';
					$title = $recordModel->get('projectname');
					if ($title === null || $title === '') {
						$title = $recordModel->getDisplayValue('projectname') ?: ('Project #' . $recordId);
					}
					$counts = isset($taskCounts[$recordId]) ? $taskCounts[$recordId] : array('total' => 0, 'done' => 0, 'in_progress' => 0);
					$projectRows[] = array(
						'id' => $recordId,
						'title' => $this->plainText($title),
						'status' => $this->plainText($recordModel->getDisplayValue('projectstatus')),
						'start' => $this->plainText($recordModel->getDisplayValue('startdate')),
						'end' => $this->plainText($recordModel->getDisplayValue('enddate')),
						'owner' => $this->plainText($ownerName),
						'task_count' => (int) $counts['total'],
						'task_done' => (int) $counts['done'],
						'task_in_progress' => (int) $counts['in_progress'],
					);
				}
			} catch (Exception $e) {
				// ignore
			}
		}

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
					if ($ownerIdFilter !== null && (string) $ownerId !== (string) $ownerIdFilter) {
						continue;
					}
					$startRaw = is_array($raw) && isset($raw['startdate']) ? $raw['startdate'] : $recordModel->get('startdate');
					$endRaw   = is_array($raw) && isset($raw['enddate']) ? $raw['enddate'] : $recordModel->get('enddate');
					if ($dateFrom || $dateTo) {
						$due = $endRaw ?: $startRaw;
						if ($dateFrom && $due && $due < $dateFrom) {
							continue;
						}
						if ($dateTo && $due && $due > $dateTo) {
							continue;
						}
					}

					$ownerName = $ownerId ? getOwnerName($ownerId) : '';
					$title = $recordModel->get('projecttaskname');
					if ($title === null || $title === '') {
						$title = $recordModel->getDisplayValue('projecttaskname') ?: ('Task #' . $recordId);
					}
					$taskRows[] = array(
						'id' => $recordId,
						'title' => $this->plainText($title),
						'status' => $this->plainText($recordModel->getDisplayValue('projecttaskprogress')),
						'due' => $this->plainText($recordModel->getDisplayValue('enddate') ?: $recordModel->getDisplayValue('startdate')),
						'owner' => $this->plainText($ownerName),
					);
				}
			} catch (Exception $e) {
				// ignore
			}
		}

		$selectedProjectIds = $this->parseIdList($request->get('export_project_ids'));
		if (!empty($selectedProjectIds)) {
			$projectRows = array_values(array_filter($projectRows, function ($row) use ($selectedProjectIds) {
				return in_array((string) $row['id'], $selectedProjectIds, true);
			}));
		}
		$selectedTaskIds = $this->parseIdList($request->get('export_task_ids'));
		if (!empty($selectedTaskIds)) {
			$taskRows = array_values(array_filter($taskRows, function ($row) use ($selectedTaskIds) {
				return in_array((string) $row['id'], $selectedTaskIds, true);
			}));
		}

		return array($projectRows, $taskRows);
	}

	protected function parseIdList($raw) {
		$raw = trim((string) $raw);
		if ($raw === '') {
			return array();
		}
		$ids = array();
		foreach (explode(',', $raw) as $id) {
			$id = trim($id);
			if ($id !== '') {
				$ids[] = $id;
			}
		}
		return $ids;
	}

	protected function plainText($value) {
		$value = tdb_decode_display_text((string) $value);
		$value = strip_tags($value);
		$value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		return trim(preg_replace('/\s+/u', ' ', $value));
	}

	protected function ownerLabel($ownerId) {
		if ($ownerId === null || $ownerId === '') {
			return 'All';
		}
		return $this->plainText(getOwnerName($ownerId));
	}

	protected function flushOutputBuffers() {
		while (ob_get_level() > 0) {
			ob_end_clean();
		}
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

	protected function exportCsv(array $filters, array $projectRows, array $taskRows) {
		$this->flushOutputBuffers();
		$filename = 'management_report_' . date('Ymd_His') . '.csv';

		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Pragma: public');
		header('Cache-Control: max-age=0');
		header('Expires: Mon, 31 Dec 2000 00:00:00 GMT');

		$out = fopen('php://output', 'w');
		fwrite($out, "\xEF\xBB\xBF");
		$this->writeReportCsv($out, $filters, $projectRows, $taskRows);
		fclose($out);
		exit;
	}

	protected function writeReportCsv($out, array $filters, array $projectRows, array $taskRows) {
		fputcsv($out, array('Management Report', 'Generated at', date('Y-m-d H:i:s')));
		fputcsv($out, array(
			'Date From', $filters['date_from'],
			'Date To', $filters['date_to'],
			'Assigned To', $this->ownerLabel($filters['owner_id']),
			'Report Type', $filters['report_type'],
		));
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

		if (empty($projectRows) && empty($taskRows)) {
			fputcsv($out, array('No data found for the selected filters.'));
		}
	}

	protected function exportExcel(array $filters, array $projectRows, array $taskRows) {
		require_once 'libraries/PHPExcel/PHPExcel.php';
		$this->flushOutputBuffers();

		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()
			->setCreator('BACE CRM')
			->setTitle('Management Report');
		$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

		$sheet = $objPHPExcel->setActiveSheetIndex(0);
		$sheet->setTitle('Report');

		$row = 1;
		$sheet->setCellValue('A' . $row, 'Management Report');
		$sheet->setCellValue('B' . $row, 'Generated at');
		$sheet->setCellValue('C' . $row, date('Y-m-d H:i:s'));
		$row++;
		$sheet->setCellValue('A' . $row, 'Date From');
		$sheet->setCellValue('B' . $row, $filters['date_from']);
		$sheet->setCellValue('C' . $row, 'Date To');
		$sheet->setCellValue('D' . $row, $filters['date_to']);
		$row++;
		$sheet->setCellValue('A' . $row, 'Assigned To');
		$sheet->setCellValue('B' . $row, $this->ownerLabel($filters['owner_id']));
		$sheet->setCellValue('C' . $row, 'Report Type');
		$sheet->setCellValue('D' . $row, $filters['report_type']);
		$row += 2;

		if (!empty($projectRows)) {
			$sheet->setCellValue('A' . $row, 'Project Report');
			$row++;
			$headers = array('Project', 'Start', 'End', 'Assigned To', 'Tasks', 'Done', 'In Progress', 'Status');
			$col = 'A';
			foreach ($headers as $header) {
				$sheet->setCellValue($col . $row, $header);
				$col++;
			}
			$row++;
			foreach ($projectRows as $item) {
				$sheet->setCellValue('A' . $row, $item['title']);
				$sheet->setCellValue('B' . $row, $item['start']);
				$sheet->setCellValue('C' . $row, $item['end']);
				$sheet->setCellValue('D' . $row, $item['owner']);
				$sheet->setCellValue('E' . $row, $item['task_count']);
				$sheet->setCellValue('F' . $row, $item['task_done']);
				$sheet->setCellValue('G' . $row, $item['task_in_progress']);
				$sheet->setCellValue('H' . $row, $item['status']);
				$row++;
			}
			$row++;
		}

		if (!empty($taskRows)) {
			$sheet->setCellValue('A' . $row, 'Task Report');
			$row++;
			$headers = array('Task', 'Due date', 'Assigned To', 'Status (%)');
			$col = 'A';
			foreach ($headers as $header) {
				$sheet->setCellValue($col . $row, $header);
				$col++;
			}
			$row++;
			foreach ($taskRows as $item) {
				$sheet->setCellValue('A' . $row, $item['title']);
				$sheet->setCellValue('B' . $row, $item['due']);
				$sheet->setCellValue('C' . $row, $item['owner']);
				$sheet->setCellValue('D' . $row, $item['status']);
				$row++;
			}
		}

		if (empty($projectRows) && empty($taskRows)) {
			$sheet->setCellValue('A' . $row, 'No data found for the selected filters.');
		}

		foreach (range('A', 'H') as $columnId) {
			$sheet->getColumnDimension($columnId)->setAutoSize(true);
		}

		$filename = 'management_report_' . date('Ymd_His') . '.xlsx';
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Pragma: public');
		header('Cache-Control: max-age=0');
		header('Expires: Mon, 31 Dec 2000 00:00:00 GMT');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
		exit;
	}

	protected function exportPdf(array $filters, array $projectRows, array $taskRows) {
		require_once 'libraries/tcpdf/tcpdf.php';
		$this->flushOutputBuffers();

		$filename = 'management_report_' . date('Ymd_His') . '.pdf';

		$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
		$pdf->SetCreator('BACE CRM');
		$pdf->SetAuthor('BACE CRM');
		$pdf->SetTitle('Management Report');
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetMargins(10, 12, 10);
		$pdf->AddPage();
		$pdf->SetFont('dejavusans', '', 9);

		$html = '<h2>Management Report</h2>';
		$html .= '<p><strong>Generated at:</strong> ' . date('Y-m-d H:i:s') . '</p>';
		$html .= '<p><strong>Date From:</strong> ' . htmlspecialchars((string) $filters['date_from']) .
			' &nbsp; <strong>Date To:</strong> ' . htmlspecialchars((string) $filters['date_to']) .
			' &nbsp; <strong>Assigned To:</strong> ' . htmlspecialchars($this->ownerLabel($filters['owner_id'])) .
			' &nbsp; <strong>Type:</strong> ' . htmlspecialchars((string) $filters['report_type']) . '</p>';

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

		if (empty($projectRows) && empty($taskRows)) {
			$html .= '<p><em>No data found for the selected filters.</em></p>';
		}

		$pdf->writeHTML($html, true, false, true, false, '');
		$pdf->Output($filename, 'D');
		exit;
	}
}
