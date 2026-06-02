<?php

/* +***********************************************************************************
 * Leads Edit: Tag-Driven Create/Edit Lead UI — SALES app (UI shell for Backend).
 ************************************************************************************/

class Leads_Edit_View extends Vtiger_Edit_View {

	protected function resolveAppCategory(Vtiger_Request $request) {
		return 'SALES';
	}

	protected function redirectMarketingToSales(Vtiger_Request $request) {
		$app = strtoupper((string) $request->get('app'));
		if ($app === 'MARKETING') {
			$query = array(
				'module' => 'Leads',
				'view' => 'Edit',
				'app' => 'SALES',
			);
			if ($request->get('record')) {
				$query['record'] = $request->get('record');
			}
			header('Location: index.php?' . http_build_query($query));
			exit;
		}
	}

	/** Tag-driven Create/Edit Lead UI (SALES — empty form shell). */
	protected function isMkModernLeadsUi(Vtiger_Request $request) {
		$app = strtoupper((string) $request->get('app'));
		return $app === 'SALES' || $app === '';
	}

	protected function assignModernContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$viewer->assign('SELECTED_MENU_CATEGORY', 'SALES');
		$viewer->assign('VIEW', 'Edit');
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Leads');
		$viewer->assign('MK_MODERN_LEADS_CREATE', true);
		$viewer->assign('MK_LEAD_RECORD_ID', $recordId);
		$viewer->assign('MK_LEADS_EDIT_MODE', !empty($recordId));
	}

	public function requiresPermission(\Vtiger_Request $request) {
		if ($this->isMkModernLeadsUi($request)) {
			return array(
				array('module_parameter' => 'module', 'action' => 'index'),
			);
		}
		return parent::requiresPermission($request);
	}

	public function checkPermission(Vtiger_Request $request) {
		if ($this->isMkModernLeadsUi($request)) {
			$moduleName = $request->getModule();
			if (!Users_Privileges_Model::isPermitted($moduleName, 'index')) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $moduleName));
			}
			return true;
		}
		return parent::checkPermission($request);
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($this->isMkModernLeadsUi($request)) {
			$this->redirectMarketingToSales($request);
			Vtiger_Index_View::preProcess($request, false);
			$this->assignModernContext($request);
			if ($display) {
				$this->preProcessDisplay($request);
			}
			return;
		}
		parent::preProcess($request, $display);
	}

	public function preProcessTplName(Vtiger_Request $request) {
		if ($this->isMkModernLeadsUi($request)) {
			return 'EditViewPreProcess.tpl';
		}
		return parent::preProcessTplName($request);
	}

	public function postProcess(Vtiger_Request $request) {
		if ($this->isMkModernLeadsUi($request)) {
			$viewer = $this->getViewer($request);
			$viewer->view('EditViewPostProcess.tpl', $request->getModule());
			Vtiger_Basic_View::postProcess($request);
			return;
		}
		parent::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		if ($this->isMkModernLeadsUi($request)) {
			$this->redirectMarketingToSales($request);
			$viewer = $this->getViewer($request);
			$this->assignModernContext($request);
			$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
			$viewer->assign('CURRENTDATE', date('Y-n-j'));
			$viewer->view('EditView.tpl', $request->getModule());
			return;
		}

		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$recordModel = $this->record;
		if (!$recordModel) {
			if (!empty($recordId)) {
				$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			} else {
				$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			}
		}

		$viewer = $this->getViewer($request);
		$salutationFieldModel = Vtiger_Field_Model::getInstance('salutationtype', $recordModel->getModule());
		if ($salutationFieldModel) {
			$salutationValue = $request->get('salutationtype');
			if (!empty($salutationValue)) {
				$salutationFieldModel->set('fieldvalue', $salutationValue);
			} else {
				$salutationFieldModel->set('fieldvalue', $recordModel->get('salutationtype'));
			}
			$viewer->assign('SALUTATION_FIELD_MODEL', $salutationFieldModel);
		}

		parent::process($request);
	}
}
