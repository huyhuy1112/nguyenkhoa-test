<?php

require_once 'include/utils/TdbDisplayUtils.php';

class Reports_Management_View extends Vtiger_Index_View {

	/**
	 * Decode HTML entities in plain-text labels for Management UI/export only.
	 * Same pattern as Home/MainPage (tdb_decode_display_text).
	 *
	 * @param mixed $value
	 * @return string
	 */
	protected function managementDecodeDisplayPlain($value) {
		return tdb_decode_display_text((string) $value);
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		parent::preProcess($request, false);
		$viewer = $this->getViewer($request);

		// Đảm bảo đang ở app MANAGEMENT (sidebar)
		$viewer->assign('SELECTED_MENU_CATEGORY', 'MANAGEMENT');
		$viewer->assign('SELECTED_MENU_CATEGORY_LABEL', vtranslate('LBL_MANAGEMENT', 'Vtiger'));
		$menuGroupedByParent = Settings_MenuEditor_Module_Model::getAllVisibleModules();
		if (isset($menuGroupedByParent['MANAGEMENT'])) {
			$viewer->assign('SELECTED_CATEGORY_MENU_LIST', $menuGroupedByParent['MANAGEMENT']);
		}

		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	protected function preProcessTplName(Vtiger_Request $request) {
		return 'IndexViewPreProcess.tpl';
	}

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$currentUser = Users_Record_Model::getCurrentUserModel();

		$filters = array(
			'date_from'   => $request->get('date_from'),
			'date_to'     => $request->get('date_to'),
			'owner_id'    => $request->get('owner_id'),
			'report_type' => $request->get('report_type') ?: 'mkt',
			'export_fmt'  => $request->get('export_format'),
		);
		if (trim((string) $filters['date_from']) === '') {
			$filters['date_from'] = date('Y-m-01');
		}
		if (trim((string) $filters['date_to']) === '') {
			$filters['date_to'] = date('Y-m-t');
		}

		$doExport = $request->get('do_export');
		$activeConfigId = (int) $request->get('selected_config_id');

		// Bảo đảm bảng lưu cấu hình tồn tại + load danh sách cấu hình đã lưu theo user
		$this->ensureConfigTable();
		$savedConfigs = $this->getReportConfigs($currentUser->getId());

		// Save / update / delete saved configurations (user-specific)
		$saveFlag = $request->get('save_config');
		$updateFlag = $request->get('update_config');
		$deleteFlag = $request->get('delete_config');
		$configId = (int) $request->get('config_id');
		$configName = trim((string) $request->get('save_config_name'));

		if ($deleteFlag && $configId > 0) {
			$this->deleteReportConfig($currentUser->getId(), $configId);
			$this->redirectToManagement($filters, 0);
			return;
		}

		if ($updateFlag && $configId > 0) {
			$this->updateReportConfig($currentUser->getId(), $configId, $configName, $filters);
			$this->redirectToManagement($filters, $configId);
			return;
		}

		if ($saveFlag && $configName !== '') {
			$newId = $this->saveReportConfig($currentUser->getId(), $configName, $filters);
			$this->redirectToManagement($filters, $newId ?: 0);
			return;
		}

		// Nếu có yêu cầu export -> xuất file rồi kết thúc
		if ($doExport && !empty($filters['export_fmt'])) {
			$this->exportManagementReport($request, $filters);
			return;
		}

		// Nếu không export, luôn reset export_fmt về rỗng để lần lọc sau không dính giá trị cũ
		$filters['export_fmt'] = '';

		$owners = $currentUser->getAccessibleUsers();
		if (!is_array($owners)) {
			$owners = array();
		}
		$ownersForDisplay = array();
		foreach ($owners as $oid => $oname) {
			$ownersForDisplay[$oid] = $this->managementDecodeDisplayPlain($oname);
		}

		// MKT SALE only — dữ liệu thật từ Leads / Calendar / Potentials
		if ($filters['report_type'] === 'project' || $filters['report_type'] === 'task') {
			$filters['report_type'] = 'mkt';
		}
		if ($filters['report_type'] === 'all') {
			$filters['report_type'] = 'mkt';
		}
		require_once 'modules/Reports/models/MktSaleReportService.php';
		$mktReport = Reports_MktSaleReportService::build($filters);

		$viewer->assign('CURRENT_USER', $currentUser);
		$viewer->assign('REPORT_FILTERS', $filters);
		$viewer->assign('REPORT_OWNERS', $ownersForDisplay);
		$viewer->assign('REPORT_PROJECT_ROWS', array());
		$viewer->assign('REPORT_TASK_ROWS', array());
		$viewer->assign('REPORT_MKT_ROWS', isset($mktReport['daily']) ? $mktReport['daily'] : array());
		$viewer->assign('REPORT_KPI_ROWS', isset($mktReport['summary']) ? array($mktReport['summary']) : array());
		$viewer->assign('REPORT_MKT_SALE', $mktReport);
		$viewer->assign('REPORT_MKT_CHART_JSON', json_encode(isset($mktReport['daily']) ? $mktReport['daily'] : array()));
		$viewer->assign('REPORT_MKT_MONTHLY_JSON', json_encode(isset($mktReport['monthly']) ? $mktReport['monthly'] : array()));
		$viewer->assign('REPORT_MKT_KPI_JSON', json_encode(isset($mktReport['summary']) ? $mktReport['summary'] : array()));
		$viewer->assign('REPORT_MKT_TOTALS_JSON', json_encode(isset($mktReport['daily_total']) ? $mktReport['daily_total'] : array()));
		$viewer->assign('REPORT_MKT_CLASS_JSON', json_encode(isset($mktReport['class_days']) ? $mktReport['class_days'] : array()));
		$viewer->assign('REPORT_MKT_STATUS_JSON', json_encode(isset($mktReport['status_matrix']) ? $mktReport['status_matrix'] : array()));
		$viewer->assign('REPORT_MKT_FUNNEL_ROWS', isset($mktReport['status_matrix']['rows']) ? $mktReport['status_matrix']['rows'] : array());
		$viewer->assign('REPORT_SAVED_CONFIGS', $savedConfigs);
		$viewer->assign('ACTIVE_CONFIG_ID', $activeConfigId);

		$viewer->view('Management.tpl', 'Reports');
	}

	public function postProcessTplName(Vtiger_Request $request) {
		return 'IndexViewPostProcess.tpl';
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view($this->postProcessTplName($request), $request->getModule());
		// MANAGEMENT split shell — skip IndexPostProcess.tpl extra wrappers (same as Home/MainPage).
		Vtiger_Basic_View::postProcess($request);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~layouts/v7/modules/Reports/resources/ReportsMkManagement.css?mk_v=20260713_mktui8',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			"~layouts/v7/modules/Reports/resources/ReportsMkManagement.js?mk_v=20260713_mktui8",
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}

	/**
	 * Export management report to CSV / Excel (CSV) – dùng chung filters + dữ liệu từ getProjectRows/getTaskRows.
	 */
	protected function exportManagementReport(Vtiger_Request $request, array $filters) {
		$format = strtolower($filters['export_fmt']);
		if (!in_array($format, array('csv', 'excel', 'pdf'), true)) {
			$format = 'csv';
		}

		$reportType = $filters['report_type'];
		if (!$reportType) {
			$reportType = 'all';
		}

		// Các id cụ thể được tick trên UI (nếu có) – dạng "1,2,3"
		$selectedProjectIds = array();
		$selectedTaskIds = array();
		$projectIdsParam = trim((string) $request->get('export_project_ids'));
		if ($projectIdsParam !== '') {
			foreach (explode(',', $projectIdsParam) as $id) {
				$id = trim($id);
				if ($id !== '') {
					$selectedProjectIds[] = $id;
				}
			}
		}
		$taskIdsParam = trim((string) $request->get('export_task_ids'));
		if ($taskIdsParam !== '') {
			foreach (explode(',', $taskIdsParam) as $id) {
				$id = trim($id);
				if ($id !== '') {
					$selectedTaskIds[] = $id;
				}
			}
		}

		$projectRows = array();
		$taskRows = array();
		require_once 'modules/Reports/models/MktSaleReportService.php';
		$mktReport = Reports_MktSaleReportService::build($filters);

		// Nếu người dùng tick cụ thể dòng nào thì chỉ export các dòng đó
		if (!empty($selectedProjectIds)) {
			$projectRows = array_values(array_filter($projectRows, function($row) use ($selectedProjectIds) {
				return in_array($row['id'], $selectedProjectIds);
			}));
		}
		if (!empty($selectedTaskIds)) {
			$taskRows = array_values(array_filter($taskRows, function($row) use ($selectedTaskIds) {
				return in_array($row['id'], $selectedTaskIds);
			}));
		}

		$filenameSuffix = date('Ymd_His');
		if ($format === 'pdf') {
			$filename = "management_report_{$filenameSuffix}.pdf";

			require_once 'libraries/tcpdf/tcpdf.php';
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
						<td>' . htmlspecialchars(strip_tags($row['owner'])) . '</td>
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
						<td>' . htmlspecialchars(strip_tags($row['owner'])) . '</td>
						<td>' . htmlspecialchars($row['status']) . '</td>
					</tr>';
				}
				$html .= '</table>';
			}

			if (!empty($mktReport['daily'])) {
				$html .= '<h3>Bảng 1 — Data Marketing theo ngày</h3>';
				$html .= '<table border="1" cellspacing="0" cellpadding="3"><tr style="background-color:#f1f5f9;">'
					. '<th>Ngày</th><th>Tổng MKT</th><th>N.Khoa</th><th>TikTok</th><th>KV1</th><th>KV2</th><th>KV3</th><th>K.rõ KV</th>'
					. '<th>Đang TV</th><th>Không LH được</th><th>Không học/TB/Trùng</th><th>Online</th></tr>';
				foreach ($mktReport['daily'] as $row) {
					$html .= '<tr>'
						. '<td>' . htmlspecialchars($row['label']) . '</td>'
						. '<td align="right">' . (int) $row['total_leads'] . '</td>'
						. '<td align="right">' . (int) $row['n_khoa'] . '</td>'
						. '<td align="right">' . (int) $row['tiktok'] . '</td>'
						. '<td align="right">' . (int) $row['kv1'] . '</td>'
						. '<td align="right">' . (int) $row['kv2'] . '</td>'
						. '<td align="right">' . (int) $row['kv3'] . '</td>'
						. '<td align="right">' . (int) $row['region_unknown'] . '</td>'
						. '<td align="right">' . (int) $row['consulting'] . '</td>'
						. '<td align="right">' . (int) $row['unreachable'] . '</td>'
						. '<td align="right">' . (int) $row['invalid'] . '</td>'
						. '<td align="right">' . (int) $row['online_class'] . '</td>'
						. '</tr>';
				}
				if (!empty($mktReport['daily_total'])) {
					$t = $mktReport['daily_total'];
					$html .= '<tr style="background:#dbeafe;font-weight:bold;">'
						. '<td>' . htmlspecialchars($t['label']) . '</td>'
						. '<td align="right">' . (int) $t['total_leads'] . '</td>'
						. '<td align="right">' . (int) $t['n_khoa'] . '</td>'
						. '<td align="right">' . (int) $t['tiktok'] . '</td>'
						. '<td align="right">' . (int) $t['kv1'] . '</td>'
						. '<td align="right">' . (int) $t['kv2'] . '</td>'
						. '<td align="right">' . (int) $t['kv3'] . '</td>'
						. '<td align="right">' . (int) $t['region_unknown'] . '</td>'
						. '<td align="right">' . (int) $t['consulting'] . '</td>'
						. '<td align="right">' . (int) $t['unreachable'] . '</td>'
						. '<td align="right">' . (int) $t['invalid'] . '</td>'
						. '<td align="right">' . (int) $t['online_class'] . '</td>'
						. '</tr>';
				}
				$html .= '</table><br/>';
			}

			if (!empty($mktReport['class_days'])) {
				$html .= '<h3>Bảng 2 — Funnel theo ngày học</h3>';
				$html .= '<table border="1" cellspacing="0" cellpadding="3"><tr style="background-color:#dbeafe;">'
					. '<th>Tháng</th><th>Thứ</th><th>Ngày học</th><th>Data MKT</th><th>Hẹn</th><th>Dời</th><th>Không học</th>'
					. '<th>XN</th><th>Show</th><th>%TN</th><th>%Show/hẹn</th><th>%Chốt</th></tr>';
				foreach ($mktReport['class_days'] as $row) {
					$html .= '<tr>'
						. '<td>' . htmlspecialchars($row['month_label']) . '</td>'
						. '<td>' . htmlspecialchars($row['weekday']) . '</td>'
						. '<td>' . htmlspecialchars($row['label']) . '</td>'
						. '<td align="right">' . (int) $row['total_leads'] . '</td>'
						. '<td align="right">' . (int) $row['appointments'] . '</td>'
						. '<td align="right">' . (int) $row['reschedule'] . '</td>'
						. '<td align="right">' . (int) $row['khong_hoc'] . '</td>'
						. '<td align="right">' . (int) $row['confirmed'] . '</td>'
						. '<td align="right">' . (int) $row['show'] . '</td>'
						. '<td align="right">' . $row['pct_potential'] . '%</td>'
						. '<td align="right">' . $row['pct_show_appt'] . '%</td>'
						. '<td align="right">' . $row['pct_close_appt'] . '%</td>'
						. '</tr>';
				}
				$html .= '</table><br/>';
			}

			if (!empty($mktReport['status_matrix']['columns']) && !empty($mktReport['status_matrix']['rows'])) {
				$html .= '<h3>Bảng 3 — Tình trạng theo ngày học</h3>';
				$html .= '<table border="1" cellspacing="0" cellpadding="3"><tr style="background-color:#dcfce7;"><th>Tình trạng</th>';
				foreach ($mktReport['status_matrix']['columns'] as $col) {
					$html .= '<th>' . htmlspecialchars($col['label']) . '</th>';
				}
				$html .= '</tr>';
				foreach ($mktReport['status_matrix']['rows'] as $srow) {
					$html .= '<tr><td>' . htmlspecialchars($srow['label']) . '</td>';
					$cells = isset($srow['cells']) ? $srow['cells'] : array();
					foreach ($mktReport['status_matrix']['columns'] as $i => $col) {
						$html .= '<td align="right">' . (isset($cells[$i]) ? (int) $cells[$i] : 0) . '</td>';
					}
					$html .= '</tr>';
				}
				$html .= '</table>';
			}

			$pdf->writeHTML($html, true, false, true, false, '');
			$pdf->Output($filename, 'D');
			exit;
		} else {
			$extension = $format === 'excel' ? 'xls' : 'csv';
			$filename = "management_report_{$filenameSuffix}.{$extension}";

			header('Content-Type: text/csv; charset=UTF-8');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Pragma: no-cache');
			header('Expires: 0');

			// BOM để Excel hiểu UTF‑8
			echo "\xEF\xBB\xBF";

			$out = fopen('php://output', 'w');

			// Ghi filters lên đầu file
			fputcsv($out, array('Management Report', 'Generated at', date('Y-m-d H:i:s')));
			fputcsv($out, array('Date From', $filters['date_from'], 'Date To', $filters['date_to'], 'Owner', $filters['owner_id']));
			fputcsv($out, array()); // dòng trống

			if (!empty($projectRows)) {
				fputcsv($out, array('Project Report'));
				// Project: Tên, ngày bắt đầu/kết thúc, Assigned To, số task (total/done/in progress), trạng thái
				fputcsv($out, array('Project', 'Start', 'End', 'Assigned To', 'Tasks', 'Done', 'In Progress', 'Status'));
				foreach ($projectRows as $row) {
					fputcsv($out, array(
						$row['title'],
						$row['start'],
						$row['end'],
						strip_tags($row['owner']),
						$row['task_count'],
						$row['task_done'],
						$row['task_in_progress'],
						$row['status'],
					));
				}
				fputcsv($out, array()); // dòng trống giữa 2 section
			}

			if (!empty($taskRows)) {
				fputcsv($out, array('Task Report'));
				// Task: Tên, ngày kết thúc, Assigned To, % hoàn thành
				fputcsv($out, array('Task', 'Due date', 'Assigned To', 'Status (%)'));
				foreach ($taskRows as $row) {
					fputcsv($out, array(
						$row['title'],
						$row['due'],
						strip_tags($row['owner']),
						$row['status'],
					));
				}
				fputcsv($out, array());
			}

			if (!empty($mktReport['daily'])) {
				fputcsv($out, array('Bảng 1 — Data Marketing theo ngày'));
				fputcsv($out, array(
					'Ngày', 'Tổng Data Marketing', 'N.Khoa', 'TikTok',
					'KV1', 'KV2', 'KV3', 'K. rõ KV',
					'Đang tư vấn / Hẹn / KNM', 'Liên hệ không được', 'Không học / Thuê bao / Trùng số', 'Lớp online',
				));
				foreach ($mktReport['daily'] as $row) {
					fputcsv($out, array(
						$row['label'],
						$row['total_leads'],
						$row['n_khoa'],
						$row['tiktok'],
						$row['kv1'],
						$row['kv2'],
						$row['kv3'],
						$row['region_unknown'],
						$row['consulting'],
						$row['unreachable'],
						$row['invalid'],
						$row['online_class'],
					));
				}
				if (!empty($mktReport['daily_total'])) {
					$t = $mktReport['daily_total'];
					fputcsv($out, array(
						$t['label'],
						$t['total_leads'],
						$t['n_khoa'],
						$t['tiktok'],
						$t['kv1'],
						$t['kv2'],
						$t['kv3'],
						$t['region_unknown'],
						$t['consulting'],
						$t['unreachable'],
						$t['invalid'],
						$t['online_class'],
					));
				}
				fputcsv($out, array());
			}

			if (!empty($mktReport['class_days'])) {
				fputcsv($out, array('Bảng 2 — Funnel theo ngày học'));
				fputcsv($out, array(
					'Tháng', 'Thứ', 'Ngày học', 'Tổng Data Marketing', 'Số lượng hẹn', 'Dời lịch', 'Không học',
					'Xác nhận', 'Thực tế tham gia', '% Tiềm năng', '% Tham gia/hẹn', '% Xác nhận/hẹn', '% Thực tế/XN',
					'PCTH', 'PCTH+MQ', 'MQ', 'Tỷ lệ chốt', 'Tổng %',
					'KNM/Bận', 'Phân vân', 'Mời lại', 'Ngóng chờ', 'Chưa XĐ', 'Học chỗ khác', 'QT nhượng quyền', 'QT nguyên liệu',
				));
				foreach ($mktReport['class_days'] as $row) {
					fputcsv($out, array(
						$row['month_label'],
						$row['weekday'],
						$row['label'],
						$row['total_leads'],
						$row['appointments'],
						$row['reschedule'],
						$row['khong_hoc'],
						$row['confirmed'],
						$row['show'],
						$row['pct_potential'] . '%',
						$row['pct_show_appt'] . '%',
						$row['pct_confirm_appt'] . '%',
						$row['pct_show_confirm'] . '%',
						$row['pcth'],
						$row['pcth_mq'],
						$row['mq'],
						$row['pct_close_appt'] . '%',
						$row['pct_close_total'] . '%',
						$row['knm_ban'],
						$row['phan_van'],
						$row['moi_lai'],
						$row['ngong_cho'],
						$row['chua_xac_dinh'],
						$row['hoc_cho_khac'],
						$row['quan_tam_nq'],
						$row['quan_tam_nl'],
					));
				}
				fputcsv($out, array());
			}

			if (!empty($mktReport['status_matrix']['columns']) && !empty($mktReport['status_matrix']['rows'])) {
				fputcsv($out, array('Bảng 3 — Tình trạng theo ngày học'));
				$header = array('Tình trạng');
				foreach ($mktReport['status_matrix']['columns'] as $col) {
					$header[] = $col['label'];
				}
				fputcsv($out, $header);
				foreach ($mktReport['status_matrix']['rows'] as $srow) {
					$line = array($srow['label']);
					$cells = isset($srow['cells']) ? $srow['cells'] : array();
					foreach ($mktReport['status_matrix']['columns'] as $i => $col) {
						$line[] = isset($cells[$i]) ? $cells[$i] : 0;
					}
					fputcsv($out, $line);
				}
				fputcsv($out, array());
			}

			if (!empty($mktReport['monthly'])) {
				fputcsv($out, array('Tổng kết theo tháng (12 tháng)'));
				fputcsv($out, array('Tháng', 'Lead', 'Đã LH', 'Đặt lịch', 'Show', 'XN', 'Chốt', '%Show/hẹn', '%XN/hẹn', '%Chốt/hẹn', '%Chốt/Lead'));
				foreach ($mktReport['monthly'] as $row) {
					fputcsv($out, array(
						$row['label'],
						$row['total_leads'],
						$row['contacted'],
						$row['appointments'],
						$row['show'],
						$row['confirmed'],
						$row['closed'],
						$row['pct_show_appt'] . '%',
						$row['pct_confirm_appt'] . '%',
						$row['pct_close_appt'] . '%',
						$row['pct_close_lead'] . '%',
					));
				}
			}

			fclose($out);
			exit;
		}
	}

	/**
	 * Tạo bảng lưu cấu hình nếu chưa có.
	 */
	protected function ensureConfigTable() {
		$db = PearDatabase::getInstance();
		$db->pquery("
			CREATE TABLE IF NOT EXISTS mgmt_report_configs (
				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				userid INT NOT NULL,
				name VARCHAR(255) NOT NULL,
				filters TEXT NOT NULL,
				createdtime DATETIME NOT NULL,
				INDEX idx_userid (userid)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8
		", array());

		// Optional: add modifiedtime column (idempotent)
		$col = $db->pquery("SHOW COLUMNS FROM mgmt_report_configs LIKE 'modifiedtime'", array());
		if ($col && $db->num_rows($col) === 0) {
			$db->pquery("ALTER TABLE mgmt_report_configs ADD COLUMN modifiedtime DATETIME NULL", array());
		}
	}

	/**
	 * Lưu cấu hình bộ lọc theo user.
	 */
	protected function saveReportConfig($userId, $name, array $filters) {
		$db = PearDatabase::getInstance();
		// Không lưu export_fmt
		unset($filters['export_fmt']);
		$json = json_encode($filters);
		$db->pquery(
			"INSERT INTO mgmt_report_configs (userid, name, filters, createdtime, modifiedtime) VALUES (?, ?, ?, NOW(), NOW())",
			array((int) $userId, $name, $json)
		);
		return (int) $db->getLastInsertID();
	}

	/**
	 * Update an existing config for current user only.
	 */
	protected function updateReportConfig($userId, $configId, $name, array $filters) {
		$db = PearDatabase::getInstance();
		unset($filters['export_fmt']);
		$json = json_encode($filters);

		// If name empty, keep existing name.
		if ($name !== '') {
			$db->pquery(
				"UPDATE mgmt_report_configs SET name = ?, filters = ?, modifiedtime = NOW() WHERE id = ? AND userid = ?",
				array($name, $json, (int) $configId, (int) $userId)
			);
		} else {
			$db->pquery(
				"UPDATE mgmt_report_configs SET filters = ?, modifiedtime = NOW() WHERE id = ? AND userid = ?",
				array($json, (int) $configId, (int) $userId)
			);
		}
	}

	/**
	 * Delete an existing config for current user only.
	 */
	protected function deleteReportConfig($userId, $configId) {
		$db = PearDatabase::getInstance();
		$db->pquery(
			"DELETE FROM mgmt_report_configs WHERE id = ? AND userid = ?",
			array((int) $configId, (int) $userId)
		);
	}

	/**
	 * Post/Redirect/Get helper to avoid form resubmission.
	 */
	protected function redirectToManagement(array $filters, $activeConfigId) {
		$params = array(
			'module' => 'Reports',
			'view' => 'Management',
			'app' => 'MANAGEMENT',
			'date_from' => $filters['date_from'],
			'date_to' => $filters['date_to'],
			'owner_id' => $filters['owner_id'],
			'report_type' => $filters['report_type'] ?: 'all',
			'selected_config_id' => (int) $activeConfigId,
		);
		// Ensure we don't carry action flags forward
		$url = 'index.php?' . http_build_query($params);
		header('Location: ' . $url);
		exit;
	}

	/**
	 * Lấy các cấu hình đã lưu cho user.
	 * @return array[]
	 */
	protected function getReportConfigs($userId) {
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			"SELECT id, name, filters FROM mgmt_report_configs WHERE userid = ? ORDER BY createdtime DESC",
			array($userId)
		);
		$configs = array();
		if ($result && $db->num_rows($result)) {
			while ($row = $db->fetch_array($result)) {
				$configs[] = array(
					'id' => (int) $row['id'],
					'name' => $row['name'],
					'filters_json' => $row['filters'],
				);
			}
		}
		return $configs;
	}

	protected function getProjectRows(array $filters) {
		$rows = array();
		if (!Users_Privileges_Model::isPermitted('Project', 'DetailView')) {
			return $rows;
		}
		$dateFrom = !empty($filters['date_from']) ? $filters['date_from'] : null;
		$dateTo   = !empty($filters['date_to']) ? $filters['date_to'] : null;
		$ownerIdFilter = !empty($filters['owner_id']) ? $filters['owner_id'] : null;
		try {
			$listViewModel = Vtiger_ListView_Model::getInstance('Project', '0', array());
			$pagingModel = new Vtiger_Paging_Model();
			$pagingModel->set('page', 1);
			$pagingModel->set('limit', 100);
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
				// Assigned To: luôn lấy từ owner chuẩn (smownerid) để đảm bảo có tên user/group
				$raw = method_exists($recordModel, 'getRawData') ? $recordModel->getRawData() : array();
				$ownerId = null;
				if (is_array($raw) && isset($raw['smownerid']) && $raw['smownerid'] !== '') {
					$ownerId = $raw['smownerid'];
				} else {
					$ownerId = $recordModel->get('smownerid');
				}
				// Lọc theo owner nếu có filter
				if ($ownerIdFilter !== null && (string)$ownerId !== (string)$ownerIdFilter) {
					continue;
				}

				// Lọc theo khoảng ngày (dùng startdate/enddate dạng YYYY-mm-dd)
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
				$ownerName = $this->managementDecodeDisplayPlain($ownerName);
				$title = $recordModel->get('projectname');
				if ($title === null || $title === '') {
					$title = $recordModel->getDisplayValue('projectname') ?: ('Project #' . $recordId);
				}
				$title = $this->managementDecodeDisplayPlain($title);
				$counts = isset($taskCounts[$recordId]) ? $taskCounts[$recordId] : array('total' => 0, 'done' => 0, 'in_progress' => 0);
				$rows[] = array(
					'id' => $recordId,
					'title' => $title,
					'status' => $recordModel->getDisplayValue('projectstatus'),
					'start' => $recordModel->getDisplayValue('startdate'),
					'end' => $recordModel->getDisplayValue('enddate'),
					'owner' => $ownerName,
					'url' => $recordModel->getDetailViewUrl(),
					'task_count' => (int) $counts['total'],
					'task_done' => (int) $counts['done'],
					'task_in_progress' => (int) $counts['in_progress'],
				);
			}
		} catch (Exception $e) {
			// ignore
		}
		return $rows;
	}

	/**
	 * Task counts per project: total, done (progress >= 100), in_progress (0 <= progress < 100).
	 * @param int[] $projectIds
	 * @return array [ projectId => ['total'=>n, 'done'=>n, 'in_progress'=>n], ... ]
	 */
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

	protected function getTaskRows(array $filters) {
		$rows = array();
		if (!Users_Privileges_Model::isPermitted('ProjectTask', 'DetailView')) {
			return $rows;
		}
		$dateFrom = !empty($filters['date_from']) ? $filters['date_from'] : null;
		$dateTo   = !empty($filters['date_to']) ? $filters['date_to'] : null;
		$ownerIdFilter = !empty($filters['owner_id']) ? $filters['owner_id'] : null;
		try {
			$listViewModel = Vtiger_ListView_Model::getInstance('ProjectTask', '0', array());
			$pagingModel = new Vtiger_Paging_Model();
			$pagingModel->set('page', 1);
			$pagingModel->set('limit', 100);
			$entries = $listViewModel->getListViewEntries($pagingModel);
			foreach ($entries as $recordId => $recordModel) {
				if (!($recordModel instanceof Vtiger_Record_Model)) {
					continue;
				}
				// Assigned To: dùng owner chuẩn (smownerid) để luôn có tên
				$raw = method_exists($recordModel, 'getRawData') ? $recordModel->getRawData() : array();
				$ownerId = null;
				if (is_array($raw) && isset($raw['smownerid']) && $raw['smownerid'] !== '') {
					$ownerId = $raw['smownerid'];
				} else {
					$ownerId = $recordModel->get('smownerid');
				}
				// Lọc owner nếu có filter
				if ($ownerIdFilter !== null && (string)$ownerId !== (string)$ownerIdFilter) {
					continue;
				}

				// Lọc theo ngày (ưu tiên enddate, fallback startdate)
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
				$ownerName = $this->managementDecodeDisplayPlain($ownerName);
				$title = $recordModel->get('projecttaskname');
				if ($title === null || $title === '') {
					$title = $recordModel->getDisplayValue('projecttaskname') ?: ('Task #' . $recordId);
				}
				$title = $this->managementDecodeDisplayPlain($title);
				$rows[] = array(
					'id' => $recordId,
					'title' => $title,
					'status' => $recordModel->getDisplayValue('projecttaskprogress'),
					'due' => $recordModel->getDisplayValue('enddate') ?: $recordModel->getDisplayValue('startdate'),
					'owner' => $ownerName,
					'url' => $recordModel->getDetailViewUrl(),
				);
			}
		} catch (Exception $e) {
			// ignore
		}
		return $rows;
	}
}

