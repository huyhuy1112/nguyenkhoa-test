<?php
/*+***********************************************************************************
 * SupportFAQ detail — SUPPORT split shell (same as Activities / Tickets detail).
 * URL: index.php?module=SupportFAQ&view=Detail&record=ID&app=SUPPORT
 ************************************************************************************/

class SupportFAQ_Detail_View extends Vtiger_Index_View {

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
		parent::preProcess($request, false);
		$viewer = $this->getViewer($request);
		$viewer->assign('MENU_SELECTED_MODULENAME', 'SupportFAQ');
		$this->assignSupportContext($request);
		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('DetailViewPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		if (empty($recordId)) {
			return false;
		}
		return Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $recordId);
	}

	public function process(Vtiger_Request $request) {
		$recordId = (int)$request->get('record');
		$data = $this->loadRecord($recordId);
		if ($data === null) {
			header('Location: index.php?module=SupportFAQ&view=List&app=SUPPORT');
			exit;
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD_ID', $recordId);
		$viewer->assign('RECORD_DATA', $data);
		$viewer->assign('RECORD', $data);
		$viewer->assign('INCREASE_OCCURRENCE_URL', $this->increaseOccurrenceUrl($recordId));
		$viewer->view('DetailViewFullContents.tpl', $request->getModule());
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~layouts/v7/modules/SupportFAQ/resources/SupportFAQDetail.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			'~layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js',
			'modules.SupportFAQ.resources.Detail',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}

	private static function increaseOccurrenceUrl($recordId) {
		return 'index.php?module=SupportFAQ&action=IncreaseOccurrence&record=' . (int)$recordId . '&app=SUPPORT';
	}

	private static function userInitials($firstName, $lastName, $userName = '') {
		$firstName = trim((string)$firstName);
		$lastName  = trim((string)$lastName);
		if ($firstName !== '' && $lastName !== '') {
			return strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
		}
		if ($firstName !== '') {
			return strtoupper(substr($firstName, 0, 2));
		}
		if ($lastName !== '') {
			return strtoupper(substr($lastName, 0, 2));
		}
		$userName = trim((string)$userName);
		return $userName !== '' ? strtoupper(substr($userName, 0, 2)) : '—';
	}

	private static function userDisplayName($firstName, $lastName, $userName = '') {
		$name = trim(trim((string)$firstName) . ' ' . trim((string)$lastName));
		if ($name !== '') {
			return $name;
		}
		$userName = trim((string)$userName);
		return $userName !== '' ? $userName : '—';
	}

	private static function formatDateTime($value) {
		$value = trim((string)$value);
		if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
			return '—';
		}
		$ts = strtotime($value);
		if ($ts === false) {
			return $value;
		}
		return date('d-m-Y g:i A', $ts);
	}

	private static function formatDateOnly($value) {
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
			'SELECT sf.supportfaqid, sf.question, sf.description, sf.solution,
			        sf.occurrence_count, sf.related_ticket_id,
			        ce.smownerid, ce.smcreatorid, ce.createdtime, ce.modifiedtime,
			        u.first_name, u.last_name, u.user_name
			   FROM vtiger_supportfaq sf
			   INNER JOIN vtiger_crmentity ce ON ce.crmid = sf.supportfaqid AND ce.deleted = 0
			   LEFT JOIN vtiger_users u ON u.id = ce.smownerid
			  WHERE sf.supportfaqid = ?',
			array($recordId)
		);
		if (!$res || $db->num_rows($res) < 1) {
			return null;
		}

		$row = $db->fetchByAssoc($res);
		$question = decode_html($row['question'] ?? '');
		$description = decode_html($row['description'] ?? '');
		$solution = decode_html($row['solution'] ?? '');
		$relatedTicketId = (int)($row['related_ticket_id'] ?? 0);

		return array(
			'supportfaqid' => (int)$row['supportfaqid'],
			'question' => $question,
			'description' => $description,
			'solution' => $solution,
			'occurrence_count' => (int)($row['occurrence_count'] ?? 0),
			'related_ticket_id' => $relatedTicketId,
			'smownerid' => (int)($row['smownerid'] ?? 0),
			'assigned_name' => self::userDisplayName(
				$row['first_name'] ?? '',
				$row['last_name'] ?? '',
				$row['user_name'] ?? ''
			),
			'assigned_initials' => self::userInitials(
				$row['first_name'] ?? '',
				$row['last_name'] ?? '',
				$row['user_name'] ?? ''
			),
			'createdtime' => $row['createdtime'] ?? '',
			'modifiedtime' => $row['modifiedtime'] ?? '',
			'created_display' => self::formatDateTime($row['createdtime'] ?? ''),
			'modified_display' => self::formatDateTime($row['modifiedtime'] ?? ''),
			'created_at_display' => self::formatDateOnly($row['createdtime'] ?? ''),
			'ticket_detail_url' => $relatedTicketId > 0
				? 'index.php?module=HelpDesk&view=TicketDetail&record=' . $relatedTicketId . '&app=SUPPORT'
				: '',
		);
	}
}
