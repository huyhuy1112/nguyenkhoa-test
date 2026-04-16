<?php
/*+***********************************************************************************
 * Additive Potentials Edit view to attach module-specific header scripts.
 * Does not change save/edit flow; only UI locking for auto-generated fields.
 *************************************************************************************/

class Potentials_Edit_View extends Vtiger_Edit_View {

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			'~layouts/' . Vtiger_Viewer::getDefaultLayoutName() . '/modules/Potentials/resources/EditLockAutoFields.js',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}

