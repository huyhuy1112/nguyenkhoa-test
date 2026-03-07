<?php
/*+***********************************************************************************
 * Support_Activities_View
 * Dedicated Support activities dashboard.
 ************************************************************************************/

require_once 'modules/Support/models/Activities.php';

class Support_Activities_View extends Vtiger_Index_View {

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$fromDate = trim((string)$request->get('from_date'));
		$fromDate = $fromDate !== '' ? $fromDate : null;
		$data = Support_Activities_Model::getUpcomingData($fromDate, 0);

		$viewer->assign('MODULE', $request->getModule());
		$viewer->assign('SUPPORT_ACTIVITIES_DATA', $data);
		$viewer->assign('SUPPORT_ACTIVITIES_FROM_DATE', $fromDate);
		$viewer->view('Activities.tpl', 'Support');
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			'~layouts/' . Vtiger_Viewer::getDefaultLayoutName() . '/modules/Support/resources/Activities.js',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = $this->getActivitiesCoreCssFiles();
		$cssFileNames[] = '~layouts/' . Vtiger_Viewer::getDefaultLayoutName() . '/modules/SupportActivities/resources/Dashboard.css';
		$cssFileNames[] = '~layouts/' . Vtiger_Viewer::getDefaultLayoutName() . '/modules/Support/resources/Activities.css';
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}

	/**
	 * Load requested core files when available, fallback to existing v7 files.
	 *
	 * @return array
	 */
	protected function getActivitiesCoreCssFiles() {
		$layout = Vtiger_Viewer::getDefaultLayoutName();
		$root = rtrim(vglobal('root_directory'), '/');

		$candidates = array(
			"layouts/{$layout}/lib/bootstrap/css/bootstrap.min.css",
			"layouts/{$layout}/lib/todc/css/bootstrap.min.css",
			"layouts/{$layout}/lib/font-awesome/css/font-awesome.min.css",
			"layouts/{$layout}/resources/ListView.css",
			"layouts/{$layout}/resources/DetailView.css",
			"layouts/{$layout}/modules/Calendar/resources/style.css",
			"layouts/{$layout}/modules/Calendar/resources/calendar-google.css",
			"layouts/{$layout}/modules/Calendar/resources/Calendar.css",
			"layouts/{$layout}/skins/support/style.css",
		);

		$cssFileNames = array();
		foreach ($candidates as $relativePath) {
			if (file_exists($root . '/' . $relativePath)) {
				$cssFileNames[] = '~' . $relativePath;
			}
		}

		return array_values(array_unique($cssFileNames));
	}
}

