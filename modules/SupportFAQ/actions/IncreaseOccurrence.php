<?php
/*+***********************************************************************************
 * Increase occurrence_count for SupportFAQ.
 * No core modification. Uses standard Action controller.
 *************************************************************************************/

class SupportFAQ_IncreaseOccurrence_Action extends Vtiger_Action_Controller {

	public function validateRequest(Vtiger_Request $request) {
		return $request->validateWriteAccess();
	}

	public function requiresPermission(Vtiger_Request $request) {
		return array(
			array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record'),
			array('module_parameter' => 'module', 'action' => 'EditView', 'record_parameter' => 'record'),
		);
	}

	public function process(Vtiger_Request $request) {
		$recordId = $request->get('record');
		if (empty($recordId)) {
			throw new AppException(vtranslate('LBL_RECORD_NOT_FOUND'));
		}

		$db = PearDatabase::getInstance();
		$db->pquery(
			'UPDATE vtiger_supportfaq SET occurrence_count = IFNULL(occurrence_count,0) + 1 WHERE supportfaqid = ?',
			array($recordId)
		);

		$app = $request->get('app');
		$redirectUrl = 'index.php?module=SupportFAQ&view=Detail&record=' . urlencode($recordId);
		if (!empty($app)) {
			$redirectUrl .= '&app=' . urlencode($app);
		}

		if (ob_get_level() > 0) {
			ob_clean();
		}
		header('Location: ' . $redirectUrl);
		exit;
	}
}

