<?php
/*+***********************************************************************************
 * Activities detail view (SUPPORT app) — modern split shell UI.
 ************************************************************************************/

class Activities_Detail_View extends Vtiger_Index_View {

	protected function preProcessTplName(Vtiger_Request $request) {
		return 'DetailViewPreProcess.tpl';
	}

	protected function assignSupportContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$appName = $request->get('app');
		$viewer->assign('SELECTED_MENU_CATEGORY', !empty($appName) ? $appName : 'SUPPORT');
		$viewer->assign('VIEW', 'Detail');
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->assignSupportContext($request);
		parent::preProcess($request, $display);
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('DetailViewPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}

	public function checkPermission(Vtiger_Request $request) {
		return true;
	}

	public function process(Vtiger_Request $request) {
		$recordId = (int)$request->get('record');
		$data = $this->loadRecord($recordId);
		if ($data === null) {
			header('Location: index.php?module=Activities&view=List&app=SUPPORT');
			exit;
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD_ID', $recordId);
		$viewer->assign('RECORD_DATA', $data);
		$viewer->assign('RECORD', $data);
		$viewer->view('DetailViewFullContents.tpl', 'Activities');
	}

	/**
	 * @param int $recordId
	 * @return array|null
	 */
	protected function loadRecord($recordId) {
		if ($recordId <= 0) {
			return null;
		}

		$db = PearDatabase::getInstance();
		$res = $db->pquery(
			"SELECT a.*, ce.smownerid, ce.createdtime, ce.modifiedtime,
			        u.first_name, u.last_name,
			        org.accountname AS org_name,
			        pr.projectname AS project_name
			   FROM vtiger_activities a
			   JOIN vtiger_crmentity ce ON ce.crmid = a.activityid AND ce.deleted = 0
			   LEFT JOIN vtiger_users u ON u.id = COALESCE(a.assigned_user_id, ce.smownerid)
			   LEFT JOIN vtiger_account org ON org.accountid = a.organizationid
			   LEFT JOIN vtiger_project pr ON pr.projectid = a.projectid
			  WHERE a.activityid = ?",
			[$recordId]
		);
		if (!$res || $db->num_rows($res) < 1) {
			return null;
		}

		$row = $db->fetchByAssoc($res);
		return $this->normalizeRecord($row);
	}

	/**
	 * @param array $row
	 * @return array
	 */
	protected function normalizeRecord(array $row) {
		$activityType = trim((string)($row['activitytype'] ?? $row['activity_type'] ?? ''));
		$content = trim((string)($row['content'] ?? $row['activity_name'] ?? $row['title'] ?? ''));
		if ($content === '') {
			$content = 'Activity ' . (int)($row['activityid'] ?? 0);
		}

		$first = trim((string)($row['first_name'] ?? ''));
		$last = trim((string)($row['last_name'] ?? ''));
		$assignedName = trim($first . ' ' . $last);
		$initials = '';
		if ($first !== '') {
			$initials .= strtoupper(substr($first, 0, 1));
		}
		if ($last !== '') {
			$initials .= strtoupper(substr($last, 0, 1));
		}
		if ($initials === '') {
			$initials = '?';
		}

		$status = trim((string)($row['status'] ?? ''));
		$statusKey = strtolower(preg_replace('/\s+/', '_', $status));

		$createdRaw = (string)($row['createdtime'] ?? '');
		$modifiedRaw = (string)($row['modifiedtime'] ?? '');

		return [
			'activityid' => (int)($row['activityid'] ?? 0),
			'activitytype' => $activityType,
			'activity_type' => $activityType,
			'type_label' => $activityType !== '' ? $activityType : '—',
			'type_class' => $this->getTypeClass($activityType),
			'subject' => $content,
			'content' => $content,
			'org_name' => trim((string)($row['org_name'] ?? '')),
			'project_name' => trim((string)($row['project_name'] ?? '')),
			'ticketid' => (int)($row['ticketid'] ?? 0),
			'assigned_name' => $assignedName !== '' ? $assignedName : '—',
			'assigned_initials' => $initials,
			'activity_date' => trim((string)($row['activity_date'] ?? '')),
			'activity_date_display' => $this->formatDateTime($row['activity_date'] ?? ''),
			'status' => $status !== '' ? $status : '—',
			'status_key' => $statusKey,
			'status_class' => $this->getStatusClass($status),
			'note_before' => trim((string)($row['note_before'] ?? '')),
			'note_after' => trim((string)($row['note_after'] ?? '')),
			'createdtime' => $createdRaw,
			'modifiedtime' => $modifiedRaw,
			'created_display' => $this->formatDateTime($createdRaw),
			'modified_display' => $this->formatDateTime($modifiedRaw),
			'created_at_display' => $this->formatDateOnly($createdRaw),
		];
	}

	protected function getTypeClass($type) {
		$key = strtolower(trim($type));
		$map = [
			'anniversary' => 'anniversary',
			'webinar' => 'webinar',
			'meeting' => 'meeting',
			'follow up' => 'follow-up',
			'follow_up' => 'follow-up',
			'gift' => 'gift',
			'intro' => 'intro',
			'call' => 'call',
			'event' => 'event',
			'task' => 'task',
			'other' => 'other',
		];
		return isset($map[$key]) ? $map[$key] : 'default';
	}

	protected function getStatusClass($status) {
		$key = strtolower(preg_replace('/\s+/', '_', trim($status)));
		$allowed = ['scheduled', 'ready', 'completed', 'skipped'];
		return in_array($key, $allowed, true) ? $key : 'default';
	}

	protected function formatDateTime($value) {
		$value = trim((string)$value);
		if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
			return '—';
		}
		$ts = strtotime($value);
		if ($ts === false) {
			return $value;
		}
		return date('Y-m-d H:i:s', $ts);
	}

	protected function formatDateOnly($value) {
		$value = trim((string)$value);
		if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
			return '—';
		}
		$ts = strtotime($value);
		if ($ts === false) {
			return $value;
		}
		return date('d-m-Y', $ts);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = [
			'~layouts/v7/modules/Vtiger/resources/DashBoard.css',
			'~layouts/v7/modules/Activities/resources/ActivitiesDetail.css',
		];
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = [
			'~layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js',
			'~layouts/v7/modules/Activities/resources/Detail.js',
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}