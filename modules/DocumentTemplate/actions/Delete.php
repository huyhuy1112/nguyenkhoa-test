<?php
class DocumentTemplate_Delete_Action extends Vtiger_Delete_Action {
	public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}

	public function checkPermission($request) {
		return true;
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = (int) $request->get('record');

		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			"SELECT isdefault FROM vtiger_documenttemplates WHERE templateid = ? AND deleted = 0",
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

		$db->pquery(
			"UPDATE vtiger_documenttemplates SET deleted = 1 WHERE templateid = ?",
			array($recordId)
		);

		header("Location: " . 'index.php?module=DocumentTemplate&view=List&app=TOOLS&deleted=1');
		exit;
	}
}
?>

