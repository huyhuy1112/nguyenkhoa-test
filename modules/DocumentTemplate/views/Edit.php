<?php
class DocumentTemplate_Edit_View extends Vtiger_Index_View {
	protected $features = array('Invoice', 'Quote', 'Contract', 'Other');

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
		// Reuse ListView wrapper to get standard Tools layout.
		return 'ListViewPreProcess.tpl';
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		// Ensure header/menu templates have required module context.
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		parent::preProcess($request, $display);
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('ListViewPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			"libraries.jquery.ckeditor.ckeditor",
			"libraries.jquery.ckeditor.adapters.jquery",
			"modules.Vtiger.resources.CkEditor",
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}

	public function process(Vtiger_Request $request) {
		if (!$this->isToolsContext($request)) {
			$this->getViewer($request)->view('OperationNotPermitted.tpl', 'Vtiger');
			return;
		}

		require_once 'modules/DocumentTemplate/helpers/TemplateSetup.php';
		DocumentTemplate_TemplateSetup_Helper::runAll();

		$viewer = $this->getViewer($request);
		$viewer->assign('FEATURES', $this->features);

		$recordId = $request->get('record');
		$copyFromId = $request->get('copyFrom');

		$mode = 'new';
		$record = array(
			'templateid' => 0,
			'templatename' => '',
			'feature' => 'Invoice',
			'description' => '',
			'content' => '',
			'version' => 1,
			'isdefault' => 0,
		);
		$copyFrom = 0;

		// BA: copy-first workflow. Block direct new template creation (must copy from an existing template).
		if (empty($recordId) && empty($copyFromId)) {
			header('Location: index.php?module=DocumentTemplate&view=List&app=TOOLS&copyFirst=1');
			exit;
		}

		if (!empty($copyFromId)) {
			$copyFrom = (int) $copyFromId;
			$source = $this->getTemplateById($copyFrom);
			$mode = 'copy';
			if ($source) {
				$record = array(
					'templateid' => 0,
					'templatename' => '',
					'feature' => (string) $source['feature'],
					'description' => (string) $source['description'],
					'content' => (string) $source['content'],
					'version' => 1,
					'isdefault' => 0,
				);
			}
		} elseif (!empty($recordId)) {
			$mode = 'edit';
			$record = $this->getTemplateById((int) $recordId);
			if (!$record) {
				$record = array(
					'templateid' => 0,
					'templatename' => '',
					'feature' => 'Invoice',
					'description' => '',
					'content' => '',
					'version' => 1,
					'isdefault' => 0,
				);
			}
		}

		// BA: default templates are protected (no edit). Encourage copy.
		if ($mode === 'edit' && isset($record['isdefault']) && (int) $record['isdefault'] === 1) {
			header('Location: index.php?module=DocumentTemplate&view=Detail&record='.(int)$recordId.'&app=TOOLS&readonlyDefault=1');
			exit;
		}

		$viewer->assign('MODE', $mode);
		$viewer->assign('RECORD', $record);
		$viewer->assign('COPY_FROM_ID', $copyFrom);

		$viewer->view('EditView.tpl', $request->getModule());
	}

	protected function getTemplateById($templateId) {
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			"SELECT * FROM vtiger_documenttemplates WHERE templateid = ? AND deleted = 0",
			array($templateId)
		);
		if ($db->num_rows($result) <= 0) {
			return false;
		}
		return $db->fetchByAssoc($result);
	}
}
?>

