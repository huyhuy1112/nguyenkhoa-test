<?php
/*+***********************************************************************************
 * Contacts Edit — premium Create workspace (SALES, new record). Stock Save + all fields.
 *************************************************************************************/

class Contacts_Edit_View extends Vtiger_Edit_View {

	protected function isMkModernContactCreate(Vtiger_Request $request) {
		if (!empty($request->get('record')) && !$request->get('isDuplicate')) {
			return false;
		}
		if ($request->get('displayMode') === 'overlay') {
			return false;
		}
		$app = strtoupper((string) $request->get('app'));
		return $app === 'SALES' || $app === '';
	}

	protected function assignModernContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$viewer->assign('SELECTED_MENU_CATEGORY', 'SALES');
		$viewer->assign('VIEW', 'Edit');
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Contacts');
		$viewer->assign('MK_MODERN_CONTACT_CREATE', true);
	}

	protected function redirectMarketingToSales(Vtiger_Request $request) {
		$app = strtoupper((string) $request->get('app'));
		if ($app === 'MARKETING' && empty($request->get('record'))) {
			header('Location: index.php?module=Contacts&view=Edit&app=SALES');
			exit;
		}
	}

	protected function assignSalutationField(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$recordModel = $this->record;
		if (!$recordModel) {
			if (!empty($recordId)) {
				$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			} else {
				$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			}
			$this->record = $recordModel;
		}

		$viewer = $this->getViewer($request);
		$salutationFieldModel = Vtiger_Field_Model::getInstance('salutationtype', $recordModel->getModule());
		if (!$salutationFieldModel) {
			return;
		}
		$salutationType = $request->get('salutationtype');
		if (!empty($salutationType)) {
			$salutationFieldModel->set('fieldvalue', $salutationType);
		} else {
			$salutationFieldModel->set('fieldvalue', $recordModel->get('salutationtype'));
		}
		$viewer->assign('SALUTATION_FIELD_MODEL', $salutationFieldModel);
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($this->isMkModernContactCreate($request)) {
			$this->redirectMarketingToSales($request);
			parent::preProcess($request, false);
			$this->assignModernContext($request);
			if ($display) {
				$this->preProcessDisplay($request);
			}
			return;
		}
		parent::preProcess($request, $display);
	}

	public function preProcessTplName(Vtiger_Request $request) {
		if ($this->isMkModernContactCreate($request)) {
			return 'EditViewPreProcess.tpl';
		}
		return parent::preProcessTplName($request);
	}

	public function postProcess(Vtiger_Request $request) {
		if ($this->isMkModernContactCreate($request)) {
			$viewer = $this->getViewer($request);
			$viewer->view('EditViewPostProcess.tpl', $request->getModule());
			Vtiger_Basic_View::postProcess($request);
			return;
		}
		parent::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		if ($this->isMkModernContactCreate($request)) {
			$this->assignModernContext($request);
		}
		$this->assignSalutationField($request);
		parent::process($request);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		if (!$this->isMkModernContactCreate($request)) {
			return $headerCssInstances;
		}
		$cssFileNames = array(
			'~layouts/v7/modules/Contacts/resources/ContactMkEdit.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		if (!$this->isMkModernContactCreate($request)) {
			return $headerScriptInstances;
		}
		$jsFileNames = array(
			'~layouts/v7/modules/Contacts/resources/ContactMkEdit.js',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}
