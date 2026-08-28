<?php
/*+***********************************************************************************
 * SupportFAQ list – custom SUPPORT shell (mockup-aligned).
 * URL: index.php?module=SupportFAQ&view=List&app=SUPPORT
 ************************************************************************************/

class SupportFAQ_List_View extends Vtiger_Index_View {

	protected function preProcessTplName(Vtiger_Request $request) {
		return 'SupportFAQViewPreProcess.tpl';
	}

	protected function assignSupportContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$appName = $request->get('app');
		$viewer->assign('SELECTED_MENU_CATEGORY', !empty($appName) ? $appName : 'SUPPORT');
		$viewer->assign('VIEW', 'List');
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->assignSupportContext($request);
		parent::preProcess($request, false);
		$viewer = $this->getViewer($request);
		$viewer->assign('MENU_SELECTED_MODULENAME', 'SupportFAQ');
		// Basic_View overwrites SELECTED_MENU_CATEGORY / VIEW — restore for shell CSS + sidebar.
		$this->assignSupportContext($request);
		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('SupportFAQViewPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			'modules.SupportFAQ.resources.List',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~layouts/v7/modules/SupportFAQ/resources/SupportFAQList.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}

	private static function listRedirectUrl($page = 1, $search = '') {
		$page = (int)$page;
		if ($page < 1) {
			$page = 1;
		}
		$url = 'index.php?module=SupportFAQ&view=List&app=SUPPORT&page=' . $page;
		$search = trim((string)$search);
		if ($search !== '') {
			$url .= '&search=' . rawurlencode($search);
		}
		return $url;
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
		return trim((string)$userName) !== '' ? trim((string)$userName) : '—';
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$viewer     = $this->getViewer($request);
		$db         = PearDatabase::getInstance();

		$page      = (int)$request->get('page');
		$page      = $page > 0 ? $page : 1;
		$pageLimit = 14;
		$search    = trim((string)$request->get('search'));

		$where  = ' WHERE ce.deleted = 0 ';
		$params = array();

		if ($search !== '') {
			$where   .= ' AND sf.question LIKE ?';
			$params[] = '%' . $search . '%';
		}

		$countSql = 'SELECT COUNT(*) AS total
			FROM vtiger_supportfaq sf
			INNER JOIN vtiger_crmentity ce ON ce.crmid = sf.supportfaqid
			' . $where;
		$countRes = $db->pquery($countSql, $params);
		$total    = $countRes && $db->num_rows($countRes) ? (int)$db->query_result($countRes, 0, 'total') : 0;

		$pageCount = $pageLimit > 0 ? (int)max(1, ceil($total / $pageLimit)) : 1;
		if ($page > $pageCount) {
			$page = $pageCount;
		}
		$offset = ($page - 1) * $pageLimit;

		$listSql = 'SELECT
				sf.supportfaqid,
				sf.question,
				sf.occurrence_count,
				sf.related_ticket_id,
				ce.smcreatorid,
				ce.createdtime,
				u.first_name,
				u.last_name,
				u.user_name
			FROM vtiger_supportfaq sf
			INNER JOIN vtiger_crmentity ce ON ce.crmid = sf.supportfaqid
			LEFT JOIN vtiger_users u ON u.id = ce.smcreatorid
			' . $where . '
			ORDER BY ce.modifiedtime DESC
			LIMIT ' . (int)$offset . ', ' . (int)$pageLimit;

		$listRes = $db->pquery($listSql, $params);
		$records = array();
		if ($listRes && $db->num_rows($listRes) > 0) {
			while ($row = $db->fetchByAssoc($listRes)) {
				$row['question'] = decode_html($row['question'] ?? '');
				$row['created_by_name'] = self::userDisplayName(
					$row['first_name'] ?? '',
					$row['last_name'] ?? '',
					$row['user_name'] ?? ''
				);
				$row['created_by_initials'] = self::userInitials(
					$row['first_name'] ?? '',
					$row['last_name'] ?? '',
					$row['user_name'] ?? ''
				);
				$records[] = $row;
			}
		}

		$showFrom = $total > 0 ? $offset + 1 : 0;
		$showTo   = min($offset + count($records), $total);

		$paginationPages = array();
		if ($pageCount <= 7) {
			for ($i = 1; $i <= $pageCount; $i++) {
				$paginationPages[] = $i;
			}
		} else {
			$paginationPages[] = 1;
			if ($page > 3) {
				$paginationPages[] = '…';
			}
			$start = max(2, $page - 1);
			$end   = min($pageCount - 1, $page + 1);
			for ($i = $start; $i <= $end; $i++) {
				$paginationPages[] = $i;
			}
			if ($page < $pageCount - 2) {
				$paginationPages[] = '…';
			}
			$paginationPages[] = $pageCount;
		}

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('FAQ_RECORDS', $records);
		$viewer->assign('FAQ_TOTAL', $total);
		$viewer->assign('FAQ_PAGE', $page);
		$viewer->assign('FAQ_PAGES', $pageCount);
		$viewer->assign('FAQ_SHOW_FROM', $showFrom);
		$viewer->assign('FAQ_SHOW_TO', $showTo);
		$viewer->assign('FAQ_PAGINATION', $paginationPages);
		$viewer->assign('FAQ_SEARCH', $search);
		$viewer->assign('FAQ_LIST_URL', self::listRedirectUrl(1, $search));
		$viewer->assign('FAQ_SEARCH_QUERY', $search !== '' ? '&search=' . rawurlencode($search) : '');
		$viewer->view('SupportFAQList.tpl', $moduleName);
	}
}
