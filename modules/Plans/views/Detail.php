<?php
/*+**********************************************************************************
 * Plans Detail View - Plan info + linked campaigns + schedule list view.
 *************************************************************************************/

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

	protected function getCampaignTableName() {
		global $adb;
		// Match GetSchedule behavior: use vtiger_campaign if it exists; otherwise vtiger_campaigns
		$primary = 'vtiger_campaign';
		$t1 = $adb->pquery("SHOW TABLES LIKE ?", array($primary));
		if ($t1 && $adb->num_rows($t1) > 0) {
			return $primary;
		}
		return 'vtiger_campaigns';
	}

	protected function getPlanCampaignRows($planId) {
		global $adb;
		$campaignTable = $this->getCampaignTableName();

		$res = $adb->pquery(
			"SELECT
				pc.id,
				pc.campaign_id,
				pc.start_date,
				pc.end_date,
				pc.status,
				pc.createdtime,
				c.campaignname,
				ce.description AS description
			 FROM vtiger_plan_campaigns pc
			 INNER JOIN {$campaignTable} c ON c.campaignid = pc.campaign_id
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = c.campaignid
			 WHERE ce.deleted = 0 AND pc.plan_id = ?
			 ORDER BY pc.start_date ASC, pc.id ASC",
			array((int)$planId)
		);

		$rows = array();
		if ($res) {
			for ($i = 0; $i < $adb->num_rows($res); $i++) {
				$row = $adb->fetchByAssoc($res, $i);
				$start = trim((string)$row['start_date']);
				$end = trim((string)$row['end_date']);
				$rows[] = array(
					'id' => (int)$row['id'],
					'campaign_id' => (int)$row['campaign_id'],
					'campaignname' => (string)$row['campaignname'],
					'start_date' => $start,
					'end_date' => $end,
					'status' => (string)$row['status'],
					'description' => (string)$row['description'],
					'link' => 'index.php?module=Campaigns&view=Detail&record=' . (int)$row['campaign_id'],
				);
			}
		}
		return $rows;
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

