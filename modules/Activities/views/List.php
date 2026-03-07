<?php
/*+***********************************************************************************
 * Keep Support -> Activities menu on module=Activities while rendering the new
 * dedicated Support activities dashboard implementation.
 ************************************************************************************/

require_once 'modules/Support/views/Activities.php';

class Activities_List_View extends Support_Activities_View {

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$layout = Vtiger_Viewer::getDefaultLayoutName();
		$cssFileNames = array(
			'~layouts/' . $layout . '/modules/SupportActivities/resources/Dashboard.css',
			'~layouts/' . $layout . '/modules/Support/resources/Activities.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}
}

