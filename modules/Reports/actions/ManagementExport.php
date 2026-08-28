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

	protected function reportTypeLabel($type) {
		$map = array(
			'all' => 'Tất cả',
			'project' => 'Dự án',
			'task' => 'Nhiệm vụ',
		);
		return isset($map[$type]) ? $map[$type] : (string) $type;
	}

	protected function sendDownloadHeaders($filename, $contentType) {
		header('Content-Type: ' . $contentType);
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Pragma: public');
		header('Cache-Control: max-age=0, must-revalidate');
		header('Expires: Mon, 31 Dec 2000 00:00:00 GMT');
		header('X-Content-Type-Options: nosniff');
	}

	protected function exportCsv(array $filters, array $projectRows, array $taskRows) {
		$this->flushOutputBuffers();
		$filename = 'management_report_' . date('Ymd_His') . '.csv';
		$this->sendDownloadHeaders($filename, 'text/csv; charset=UTF-8');

		$out = fopen('php://output', 'w');
		fwrite($out, "\xEF\xBB\xBF");
		$this->writeReportCsv($out, $filters, $projectRows, $taskRows);
		fclose($out);
		exit;
	}

	protected function writeReportCsv($out, array $filters, array $projectRows, array $taskRows) {
		fputcsv($out, array('BÁO CÁO QUẢN TRỊ', 'Nguyên Khoa CRM', 'Xuất lúc', date('d/m/Y H:i:s')));
		fputcsv($out, array('Từ ngày', $filters['date_from'] ?: '—', 'Đến ngày', $filters['date_to'] ?: '—'));
		fputcsv($out, array('Phụ trách', $this->ownerLabel($filters['owner_id']), 'Loại báo cáo', $this->reportTypeLabel($filters['report_type'])));
		fputcsv($out, array());

		if (!empty($projectRows)) {
			fputcsv($out, array('BÁO CÁO DỰ ÁN'));
			fputcsv($out, array('Dự án', 'Bắt đầu', 'Kết thúc', 'Phụ trách', 'Tổng NV', 'Hoàn thành', 'Đang làm', 'Trạng thái'));
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
			fputcsv($out, array('BÁO CÁO NHIỆM VỤ'));
			fputcsv($out, array('Nhiệm vụ', 'Hạn hoàn thành', 'Phụ trách', 'Tiến độ (%)'));
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
			fputcsv($out, array('Không có dữ liệu phù hợp với bộ lọc đã chọn.'));
		}
	}

	protected function excelStyleTitle() {
		return array(
			'font' => array('bold' => true, 'size' => 16, 'color' => array('rgb' => 'FFFFFF'), 'name' => 'Arial'),
			'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => '08A045')),
			'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT, 'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
		);
	}

	protected function excelStyleMetaLabel() {
		return array(
			'font' => array('bold' => true, 'size' => 10, 'color' => array('rgb' => '475569'), 'name' => 'Arial'),
			'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'F1F5F9')),
			'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'E2E8F0'))),
		);
	}

	protected function excelStyleMetaValue() {
		return array(
			'font' => array('size' => 10, 'color' => array('rgb' => '1E293B'), 'name' => 'Arial'),
			'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'E2E8F0'))),
		);
	}

	protected function excelStyleSection() {
		return array(
			'font' => array('bold' => true, 'size' => 12, 'color' => array('rgb' => '0B6E4F'), 'name' => 'Arial'),
			'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'ECFDF3')),
			'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT, 'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
		);
	}

	protected function excelStyleTableHeader() {
		return array(
			'font' => array('bold' => true, 'size' => 10, 'color' => array('rgb' => 'FFFFFF'), 'name' => 'Arial'),
			'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => '0B6E4F')),
			'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, 'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER, 'wrap' => true),
			'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'CBD5E1'))),
		);
	}

	protected function excelStyleDataRow($zebra) {
		return array(
			'font' => array('size' => 10, 'color' => array('rgb' => '1E293B'), 'name' => 'Arial'),
			'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => $zebra ? 'F8FAFC' : 'FFFFFF')),
			'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'E2E8F0'))),
			'alignment' => array('vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER, 'wrap' => true),
		);
	}

	protected function excelStyleNumber() {
		return array(
			'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER),
		);
	}

	protected function writeExcelMetaBlock($sheet, &$row, array $filters) {
		$meta = array(
			array('Từ ngày', $filters['date_from'] ?: '—', 'Đến ngày', $filters['date_to'] ?: '—'),
			array('Phụ trách', $this->ownerLabel($filters['owner_id']), 'Loại báo cáo', $this->reportTypeLabel($filters['report_type'])),
			array('Xuất lúc', date('d/m/Y H:i:s'), '', ''),
		);
		foreach ($meta as $line) {
			$sheet->setCellValue('A' . $row, $line[0]);
			$sheet->setCellValue('B' . $row, $line[1]);
			$sheet->setCellValue('C' . $row, $line[2]);
			$sheet->setCellValue('D' . $row, $line[3]);
			$sheet->getStyle('A' . $row)->applyFromArray($this->excelStyleMetaLabel());
			$sheet->getStyle('B' . $row)->applyFromArray($this->excelStyleMetaValue());
			if ($line[2] !== '') {
				$sheet->getStyle('C' . $row)->applyFromArray($this->excelStyleMetaLabel());
				$sheet->getStyle('D' . $row)->applyFromArray($this->excelStyleMetaValue());
			} else {
				$sheet->mergeCells('B' . $row . ':H' . $row);
			}
			$row++;
		}
	}

	protected function writeExcelTableSection($sheet, &$row, $title, array $headers, array $rows, array $numericCols) {
		$lastCol = PHPExcel_Cell::stringFromColumnIndex(count($headers) - 1);
		$sheet->mergeCells('A' . $row . ':' . $lastCol . $row);
		$sheet->setCellValue('A' . $row, $title);
		$sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($this->excelStyleSection());
		$sheet->getRowDimension($row)->setRowHeight(24);
		$row++;

		$colIndex = 0;
		foreach ($headers as $header) {
			$sheet->setCellValueByColumnAndRow($colIndex, $row, $header);
			$colIndex++;
		}
		$sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($this->excelStyleTableHeader());
		$sheet->getRowDimension($row)->setRowHeight(22);
		$headerRow = $row;
		$row++;

		$dataIndex = 0;
		foreach ($rows as $item) {
			$colIndex = 0;
			foreach ($item as $cell) {
				$sheet->setCellValueByColumnAndRow($colIndex, $row, $cell);
				$colIndex++;
			}
			$sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($this->excelStyleDataRow($dataIndex % 2 === 1));
			foreach ($numericCols as $numCol) {
				$colLetter = PHPExcel_Cell::stringFromColumnIndex($numCol);
				$sheet->getStyle($colLetter . $row)->applyFromArray($this->excelStyleNumber());
			}
			$row++;
			$dataIndex++;
		}

		$sheet->setAutoFilter('A' . $headerRow . ':' . $lastCol . $headerRow);
		$row++;
	}

	protected function exportExcel(array $filters, array $projectRows, array $taskRows) {
		require_once 'libraries/PHPExcel/PHPExcel.php';
		$this->flushOutputBuffers();

		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()
			->setCreator('Nguyên Khoa CRM')
			->setTitle('Báo cáo quản trị');
		$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

		$sheet = $objPHPExcel->setActiveSheetIndex(0);
		$sheet->setTitle('Bao cao');

		$row = 1;
		$sheet->mergeCells('A1:H1');
		$sheet->setCellValue('A1', 'NGUYÊN KHOA · BÁO CÁO QUẢN TRỊ');
		$sheet->getStyle('A1:H1')->applyFromArray($this->excelStyleTitle());
		$sheet->getRowDimension(1)->setRowHeight(34);
		$row = 2;
		$this->writeExcelMetaBlock($sheet, $row, $filters);
		$row++;

		if (!empty($projectRows)) {
			$headers = array('Dự án', 'Bắt đầu', 'Kết thúc', 'Phụ trách', 'Tổng NV', 'Hoàn thành', 'Đang làm', 'Trạng thái');
			$dataRows = array();
			foreach ($projectRows as $item) {
				$dataRows[] = array(
					$item['title'],
					$item['start'],
					$item['end'],
					$item['owner'],
					(int) $item['task_count'],
					(int) $item['task_done'],
					(int) $item['task_in_progress'],
					$item['status'],
				);
			}
			$this->writeExcelTableSection($sheet, $row, 'BÁO CÁO DỰ ÁN', $headers, $dataRows, array(4, 5, 6));
		}

		if (!empty($taskRows)) {
			$headers = array('Nhiệm vụ', 'Hạn hoàn thành', 'Phụ trách', 'Tiến độ (%)');
			$dataRows = array();
			foreach ($taskRows as $item) {
				$dataRows[] = array(
					$item['title'],
					$item['due'],
					$item['owner'],
					$item['status'],
				);
			}
			$this->writeExcelTableSection($sheet, $row, 'BÁO CÁO NHIỆM VỤ', $headers, $dataRows, array());
		}

		if (empty($projectRows) && empty($taskRows)) {
			$sheet->mergeCells('A' . $row . ':H' . $row);
			$sheet->setCellValue('A' . $row, 'Không có dữ liệu phù hợp với bộ lọc đã chọn.');
			$sheet->getStyle('A' . $row)->getFont()->setItalic(true);
			$sheet->getStyle('A' . $row)->getFont()->getColor()->setRGB('64748B');
		}

		$widths = array('A' => 36, 'B' => 14, 'C' => 14, 'D' => 22, 'E' => 10, 'F' => 12, 'G' => 12, 'H' => 18);
		foreach ($widths as $col => $width) {
			$sheet->getColumnDimension($col)->setWidth($width);
		}
		$sheet->freezePane('A2');

		$filename = 'management_report_' . date('Ymd_His') . '.xlsx';
		$this->sendDownloadHeaders($filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
		exit;
	}

	protected function pdfCell($value, $widthPct, $align = 'left', $extraStyle = '') {
		$value = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
		$style = 'width:' . $widthPct . ';';
		if ($align === 'center') {
			$style .= 'text-align:center;';
		} elseif ($align === 'right') {
			$style .= 'text-align:right;';
		}
		if ($extraStyle !== '') {
			$style .= $extraStyle;
		}
		return '<td style="' . $style . '">' . $value . '</td>';
	}

	protected function buildPdfMetaHtml(array $filters) {
		$esc = function ($value) {
			return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
		};
		$lbl = 'background-color:#f1f5f9;font-weight:bold;color:#475569;';
		$val = 'color:#1e293b;';

		$html = '<table width="100%" border="1" cellpadding="6" cellspacing="0" style="border-color:#e2e8f0;margin-bottom:10px;">';
		$html .= '<tr>'
			. '<td style="width:18%;' . $lbl . '">Từ ngày</td>'
			. '<td style="width:32%;' . $val . '">' . $esc($filters['date_from'] ?: '—') . '</td>'
			. '<td style="width:18%;' . $lbl . '">Đến ngày</td>'
			. '<td style="width:32%;' . $val . '">' . $esc($filters['date_to'] ?: '—') . '</td>'
			. '</tr>';
		$html .= '<tr>'
			. '<td style="' . $lbl . '">Phụ trách</td>'
			. '<td style="' . $val . '">' . $esc($this->ownerLabel($filters['owner_id'])) . '</td>'
			. '<td style="' . $lbl . '">Loại báo cáo</td>'
			. '<td style="' . $val . '">' . $esc($this->reportTypeLabel($filters['report_type'])) . '</td>'
			. '</tr>';
		$html .= '<tr>'
			. '<td style="' . $lbl . '">Xuất lúc</td>'
			. '<td colspan="3" style="' . $val . '">' . $esc(date('d/m/Y H:i:s')) . '</td>'
			. '</tr>';
		$html .= '</table>';
		return $html;
	}

	protected function buildPdfProjectTableHtml(array $projectRows) {
		if (empty($projectRows)) {
			return '';
		}

		$cols = array(
			array('label' => 'Dự án', 'width' => '26%', 'align' => 'left'),
			array('label' => 'Bắt đầu', 'width' => '11%', 'align' => 'center'),
			array('label' => 'Kết thúc', 'width' => '11%', 'align' => 'center'),
			array('label' => 'Phụ trách', 'width' => '18%', 'align' => 'left'),
			array('label' => 'Tổng NV', 'width' => '8%', 'align' => 'center'),
			array('label' => 'Hoàn thành', 'width' => '9%', 'align' => 'center'),
			array('label' => 'Đang làm', 'width' => '9%', 'align' => 'center'),
			array('label' => 'Trạng thái', 'width' => '8%', 'align' => 'center'),
		);

		$html = '<table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin:12px 0 4px 0;">'
			. '<tr><td style="background-color:#ecfdf3;color:#0b6e4f;font-size:12pt;font-weight:bold;padding:8px 10px;">BÁO CÁO DỰ ÁN</td></tr>'
			. '</table>';

		$html .= '<table width="100%" border="1" cellpadding="5" cellspacing="0" style="border-color:#cbd5e1;">';
		$html .= '<tr style="background-color:#0b6e4f;color:#ffffff;font-weight:bold;">';
		foreach ($cols as $col) {
			$html .= '<td style="width:' . $col['width'] . ';text-align:center;font-weight:bold;color:#ffffff;">' . $col['label'] . '</td>';
		}
		$html .= '</tr>';

		$i = 0;
		foreach ($projectRows as $row) {
			$bg = ($i % 2 === 1) ? 'background-color:#f8fafc;' : 'background-color:#ffffff;';
			$html .= '<tr>';
			$html .= $this->pdfCell($row['title'], $cols[0]['width'], 'left', $bg);
			$html .= $this->pdfCell($row['start'], $cols[1]['width'], 'center', $bg);
			$html .= $this->pdfCell($row['end'], $cols[2]['width'], 'center', $bg);
			$html .= $this->pdfCell($row['owner'], $cols[3]['width'], 'left', $bg);
			$html .= $this->pdfCell((int) $row['task_count'], $cols[4]['width'], 'center', $bg);
			$html .= $this->pdfCell((int) $row['task_done'], $cols[5]['width'], 'center', $bg);
			$html .= $this->pdfCell((int) $row['task_in_progress'], $cols[6]['width'], 'center', $bg);
			$html .= $this->pdfCell($row['status'], $cols[7]['width'], 'center', $bg);
			$html .= '</tr>';
			$i++;
		}
		$html .= '</table>';
		return $html;
	}

	protected function buildPdfTaskTableHtml(array $taskRows) {
		if (empty($taskRows)) {
			return '';
		}

		$cols = array(
			array('label' => 'Nhiệm vụ', 'width' => '36%', 'align' => 'left'),
			array('label' => 'Hạn hoàn thành', 'width' => '18%', 'align' => 'center'),
			array('label' => 'Phụ trách', 'width' => '30%', 'align' => 'left'),
			array('label' => 'Tiến độ (%)', 'width' => '16%', 'align' => 'center'),
		);

		$html = '<table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin:12px 0 4px 0;">'
			. '<tr><td style="background-color:#ecfdf3;color:#0b6e4f;font-size:12pt;font-weight:bold;padding:8px 10px;">BÁO CÁO NHIỆM VỤ</td></tr>'
			. '</table>';

		$html .= '<table width="100%" border="1" cellpadding="5" cellspacing="0" style="border-color:#cbd5e1;">';
		$html .= '<tr style="background-color:#0b6e4f;color:#ffffff;font-weight:bold;">';
		foreach ($cols as $col) {
			$html .= '<td style="width:' . $col['width'] . ';text-align:center;font-weight:bold;color:#ffffff;">' . $col['label'] . '</td>';
		}
		$html .= '</tr>';

		$i = 0;
		foreach ($taskRows as $row) {
			$bg = ($i % 2 === 1) ? 'background-color:#f8fafc;' : 'background-color:#ffffff;';
			$html .= '<tr>';
			$html .= $this->pdfCell($row['title'], $cols[0]['width'], 'left', $bg);
			$html .= $this->pdfCell($row['due'], $cols[1]['width'], 'center', $bg);
			$html .= $this->pdfCell($row['owner'], $cols[2]['width'], 'left', $bg);
			$html .= $this->pdfCell($row['status'], $cols[3]['width'], 'center', $bg);
			$html .= '</tr>';
			$i++;
		}
		$html .= '</table>';
		return $html;
	}

	protected function buildPdfHtml(array $filters, array $projectRows, array $taskRows) {
		$html = '<div style="background-color:#08A045;color:#ffffff;font-size:16pt;font-weight:bold;padding:10px 12px;margin-bottom:10px;">NGUYÊN KHOA · BÁO CÁO QUẢN TRỊ</div>';
		$html .= $this->buildPdfMetaHtml($filters);
		$html .= $this->buildPdfProjectTableHtml($projectRows);
		return $html;
	}

	protected function buildPdfTaskSectionHtml(array $taskRows) {
		return $this->buildPdfTaskTableHtml($taskRows);
	}

	protected function exportPdf(array $filters, array $projectRows, array $taskRows) {
		if (!class_exists('TCPDF')) {
			require_once 'vendor/autoload.php';
		}
		if (!class_exists('TCPDF')) {
			throw new AppException('PDF export is not available (TCPDF missing).');
		}

		$this->flushOutputBuffers();
		$filename = 'management_report_' . date('Ymd_His') . '.pdf';

		$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
		$pdf->SetCreator('Nguyên Khoa CRM');
		$pdf->SetAuthor('Nguyên Khoa CRM');
		$pdf->SetTitle('Báo cáo quản trị');
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetMargins(10, 10, 10);
		$pdf->SetAutoPageBreak(true, 12);
		$pdf->AddPage();
		$pdf->SetFont('dejavusans', '', 9);

		if (empty($projectRows) && empty($taskRows)) {
			$html = '<div style="background-color:#08A045;color:#ffffff;font-size:16pt;font-weight:bold;padding:10px 12px;margin-bottom:10px;">NGUYÊN KHOA · BÁO CÁO QUẢN TRỊ</div>';
			$html .= $this->buildPdfMetaHtml($filters);
			$html .= '<p style="color:#64748b;font-style:italic;">Không có dữ liệu phù hợp với bộ lọc đã chọn.</p>';
			$pdf->writeHTML($html, true, false, true, false, '');
		} else {
			$pdf->writeHTML($this->buildPdfHtml($filters, $projectRows, $taskRows), true, false, true, false, '');
			if (!empty($taskRows)) {
				if (!empty($projectRows)) {
					$pdf->AddPage();
				}
				$pdf->writeHTML($this->buildPdfTaskSectionHtml($taskRows), true, false, true, false, '');
			}
		}

		$pdf->Output($filename, 'D');
		exit;
	}
}
