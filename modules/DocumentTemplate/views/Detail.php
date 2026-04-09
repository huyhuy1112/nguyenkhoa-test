<?php
class DocumentTemplate_Detail_View extends Vtiger_Index_View {
	protected $features = array('Invoice', 'Quote', 'Contract', 'Other');
	protected $detailRecordData = null;
	protected $detailRecordModel = null;

	protected function isToolsContext(Vtiger_Request $request) {
		return strtoupper((string) $request->get('app')) === 'TOOLS';
	}

	public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}

	public function checkPermission($request) {
		return true;
	}

	protected function preProcessTplName(Vtiger_Request $request) {
		return 'ListViewPreProcess.tpl';
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		// Ensure header/menu templates have required module context.
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));

		if ($this->isToolsContext($request)) {
			$recordId = (int) $request->get('record');
			if ($recordId > 0) {
				$record = $this->getTemplateById($recordId);
				if ($record) {
					$this->detailRecordData = $record;
					$this->detailRecordModel = $this->buildRecordModel($request->getModule(), $recordId, $record);
					// Must be present before ListViewPreProcess/ModuleHeader renders.
					$viewer->assign('RECORD', $this->detailRecordModel);
					$viewer->assign('RECORD_MODEL', $this->detailRecordModel);
					$viewer->assign('RECORD_DATA', $this->detailRecordData);
				}
			}
		}
		parent::preProcess($request, $display);
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('ListViewPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		if (!$this->isToolsContext($request)) {
			$viewer->view('OperationNotPermitted.tpl', 'Vtiger');
			return;
		}

		$errorMessage = '';
		try {
			require_once 'modules/DocumentTemplate/helpers/TemplateSetup.php';
			DocumentTemplate_TemplateSetup_Helper::runAll();
		} catch (Exception $e) {
			$errorMessage = $e->getMessage();
		}

		$recordId = (int) $request->get('record');
		$record = null;
		try {
			$record = $this->detailRecordData ?: $this->getTemplateById($recordId);
		} catch (Exception $e) {
			$errorMessage = $errorMessage ?: $e->getMessage();
		}
		if (!$record) {
			header("Location: index.php?module=DocumentTemplate&view=List&app=TOOLS");
			exit;
		}

		$recordModel = $this->detailRecordModel ?: $this->buildRecordModel($request->getModule(), $recordId, $record);

		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('RECORD_DATA', $record);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('DELETE_BLOCKED', (string) $request->get('deleteBlocked') === '1');
		$viewer->assign('READONLY_DEFAULT', (string) $request->get('readonlyDefault') === '1');
		$viewer->assign('DT_ERROR_MESSAGE', $errorMessage);
		try {
			$viewer->assign('HISTORY', DocumentTemplate_TemplateSetup_Helper::getHistoryByTemplateId(PearDatabase::getInstance(), $recordId));
		} catch (Exception $e) {
			$viewer->assign('HISTORY', array());
			$viewer->assign('DT_ERROR_MESSAGE', $errorMessage ?: $e->getMessage());
		}
		$viewer->view('DetailViewFullContents.tpl', $request->getModule());
	}

	protected function buildRecordModel($moduleName, $recordId, array $record) {
		$recordModel = new Vtiger_Record_Model();
		// Vtiger core here expects module name string (not Vtiger_Module_Model),
		// otherwise cacheKey('module', <object>) will fatal.
		$recordModel->setModule((string) $moduleName);
		$recordModel->setId($recordId);
		$recordModel->set('label', (string) $record['templatename']);
		$recordModel->setData($record);
		return $recordModel;
	}

	protected function getTemplateById($templateId) {
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			"SELECT
				dt.*,
				u.user_name AS updated_user_name,
				u.first_name AS updated_first_name,
				u.last_name AS updated_last_name,
				uc.user_name AS created_user_name,
				uc.first_name AS created_first_name,
				uc.last_name AS created_last_name
			FROM vtiger_documenttemplates dt
			LEFT JOIN vtiger_users u ON u.id = dt.updatedby
			LEFT JOIN vtiger_users uc ON uc.id = dt.createdby
			WHERE dt.templateid = ? AND dt.deleted = 0",
			array($templateId)
		);
		if ($db->num_rows($result) <= 0) {
			return false;
		}

		$row = $db->fetchByAssoc($result);
		$updatedFullName = trim((string) $row['updated_first_name'] . ' ' . (string) $row['updated_last_name']);
		if ($updatedFullName === '') {
			$updatedFullName = (string) $row['updated_user_name'];
		}
		$row['updatedby_name'] = $updatedFullName;

		$createdFullName = trim((string) $row['created_first_name'] . ' ' . (string) $row['created_last_name']);
		if ($createdFullName === '') {
			$createdFullName = (string) $row['created_user_name'];
		}
		$row['createdby_name'] = $createdFullName;

		return $row;
	}
}
?>

