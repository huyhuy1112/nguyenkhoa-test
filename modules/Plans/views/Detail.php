<?php
/*+**********************************************************************************
 * Plans Detail View - Plan info + linked campaigns + schedule list view.
 *************************************************************************************/

require_once dirname(__FILE__) . '/../helpers/PlanCampaignHelper.php';

class Plans_Detail_View extends Vtiger_Detail_View {

	protected function debugSummaryFieldMetadata($moduleName) {
		// Temporary debug for fatal on SummaryRecordStructure.php:44
		global $adb;
		try {
			$tabId = getTabid($moduleName);
			$res = $adb->pquery(
				"SELECT fieldname, columnname, tablename, block, uitype, presence, summaryfield
				 FROM vtiger_field
				 WHERE tabid = ? AND summaryfield = 1
				 ORDER BY sequence ASC",
				array((int)$tabId)
			);
			$names = array();
			$invalid = array();
			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
			if ($res) {
				for ($i = 0; $i < $adb->num_rows($res); $i++) {
					$fn = (string)$adb->query_result($res, $i, 'fieldname');
					$names[] = $fn;
					$fm = $moduleModel ? $moduleModel->getField($fn) : false;
					if (!$fm) {
						$invalid[] = $fn;
					}
				}
			}
			error_log('[Plans][Detail][Debug] module=' . $moduleName . ' tabid=' . (int)$tabId .
				' summaryFieldCount=' . count($names) .
				' summaryFields=' . json_encode($names, JSON_UNESCAPED_UNICODE) .
				' invalidSummaryFields=' . json_encode($invalid, JSON_UNESCAPED_UNICODE));
		} catch (Exception $e) {
			error_log('[Plans][Detail][Debug] summary metadata error: ' . $e->getMessage());
		}
	}

	protected function getPlanCampaignRows($planId) {
		global $adb;
		$rows = PlanCampaignHelper::fetchPlanCampaignRows($adb, $planId);
		return is_array($rows) ? $rows : array();
	}

	/**
	 * Override Summary view rendering to avoid fatal errors caused by invalid summary field metadata.
	 *
	 * Root issue: SummaryRecordStructure iterates summary field list and may receive a boolean instead
	 * of a Vtiger_Field_Model when vtiger_field has invalid summaryfield entries for Plans.
	 *
	 * Safe fallback: build summary structure from DETAIL structure (first block), which uses
	 * Vtiger_RecordStructure_Model field models.
	 */
	public function showModuleSummaryView($request) {
		$recordId = (int)$request->get('record');
		$moduleName = $request->getModule();

		if (!$this->record) {
			$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();
		$moduleModel = $recordModel->getModule();

		// Summary should render our module template (AJAX safe) and include campaign manager.
		$viewer = $this->getViewer($request);
		$rows = $this->getPlanCampaignRows($recordId);
		$viewer->assign('CAMPAIGNS_JSON', json_encode($rows, JSON_UNESCAPED_UNICODE));
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('RECORD_ID', $recordId);
		error_log('[Plans][Detail] render Summary template record=' . $recordId);

		// Avoid core SummaryRecordStructure fatal by bypassing ModuleSummaryView.tpl.
		return $viewer->view('DetailViewSummaryContents.tpl', $moduleName, true);

	}

	public function showModuleDetailView(Vtiger_Request $request) {
		$recordId = (int)$request->get('record');
		$moduleName = $request->getModule();

		// Ensure DetailView model exists for parent rendering pipeline
		if (!$this->record) {
			$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		}

		$viewer = $this->getViewer($request);
		$rows = $this->getPlanCampaignRows($recordId);
		$viewer->assign('CAMPAIGNS_JSON', json_encode($rows, JSON_UNESCAPED_UNICODE));
		error_log('[Plans][Detail] render Details template record=' . $recordId);

		return parent::showModuleDetailView($request);
	}
}

