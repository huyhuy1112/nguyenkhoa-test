<?php
class DocumentTemplate_Delete_Action extends Vtiger_Delete_Action {
	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}
	public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}

	public function checkPermission($request) {
		return true;
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = (int) $request->get('record');

		require_once 'modules/DocumentTemplate/helpers/TemplateSetup.php';
		DocumentTemplate_TemplateSetup_Helper::runAll();

		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			"SELECT isdefault, templatename, description, content, version FROM vtiger_documenttemplates WHERE templateid = ? AND deleted = 0",
			array($recordId)
		);

		$redirectUrl = 'index.php?module=DocumentTemplate&view=List&app=TOOLS';
		if ($db->num_rows($result) <= 0) {
			header("Location: $redirectUrl");
			exit;
		}

		$isdefault = (int) $db->query_result($result, 0, 'isdefault');
		if ($isdefault === 1) {
			$redirectUrl = 'index.php?module=DocumentTemplate&view=Detail&record=' . $recordId . '&app=TOOLS&deleteBlocked=1';
			header("Location: $redirectUrl");
			exit;
		}

		// Record history snapshot before delete.
		$userId = (int) Users_Record_Model::getCurrentUserModel()->getId();
		$now = date('Y-m-d H:i:s');
		$snapshot = array(
			'templatename' => (string) $db->query_result($result, 0, 'templatename'),
			'description' => (string) $db->query_result($result, 0, 'description'),
			'content' => (string) $db->query_result($result, 0, 'content'),
		);
		$version = (int) $db->query_result($result, 0, 'version');
		DocumentTemplate_TemplateSetup_Helper::recordHistory($db, $recordId, $version, $userId, $now, 'delete', $snapshot);

		$db->pquery(
			"UPDATE vtiger_documenttemplates SET deleted = 1 WHERE templateid = ?",
			array($recordId)
		);

		header("Location: " . 'index.php?module=DocumentTemplate&view=List&app=TOOLS&deleted=1');
		exit;
	}
}
?>

