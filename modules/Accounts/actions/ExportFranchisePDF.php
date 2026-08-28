<?php
/*+***********************************************************************************
 * Legacy route: cùng ExportFranchiseWord (preview PDF / download .docx).
 *************************************************************************************/

require_once 'modules/Accounts/helpers/FranchiseContractService.php';

class Accounts_ExportFranchisePDF_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array(
			'module_parameter' => 'module',
			'action' => 'DetailView',
			'record_parameter' => 'record',
		);
		return $permissions;
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = (int) $request->get('record');
		if ($recordId <= 0) {
			throw new AppException(vtranslate('LBL_RECORD_NOT_FOUND'));
		}

		Accounts_FranchiseContractService_Helper::ensureFranchiseFields();

		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
		$contractNo = trim((string) $recordModel->get('tb_contract_no'));
		if ($contractNo === '') {
			$contractNo = (string) $recordId;
		}
		$safeNo = preg_replace('/[^A-Za-z0-9_-]+/', '_', $contractNo);
		$fileName = 'HopDong_NhuongQuyen_TUI_BAO_' . $safeNo . '.docx';

		if ($request->get('preview') === '1' || $request->get('mode') === 'inline') {
			@set_time_limit(180);
			Accounts_FranchiseContractService_Helper::outputWordPreviewPdf(
				$recordModel,
				'Hợp đồng nhượng quyền TUI BAO'
			);
			exit;
		}

		Accounts_FranchiseContractService_Helper::outputWord($recordModel, $fileName, false);
		exit;
	}
}
