<?php
/*
 * Campaigns Detail: assign phase indices + Documents shortcuts for Description/results files.
 */

class Campaigns_Detail_View extends Vtiger_Detail_View {

	public function showModuleDetailView(Vtiger_Request $request) {
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		if (!$this->record) {
			$this->record = Campaigns_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();
		$recordStrucure = Vtiger_RecordStructure_Model::getInstanceFromRecordModel(
			$recordModel,
			Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_DETAIL
		);
		$structuredValues = $recordStrucure->getStructure();
		$moduleModel = $recordModel->getModule();

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('RECORD_STRUCTURE', $structuredValues);
		$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('DAY_STARTS', '');
		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', Vtiger_Functions::jsonEncode($picklistDependencyDatasource));

		$viewer->assign('CAMPAIGN_PHASE_INDICES', $this->buildCampaignPhaseIndices($recordModel));
		$viewer->assign('CAMPAIGN_DOCUMENTS_SECTION', $this->buildCampaignDocumentsSection($recordModel));

		if ($request->get('displayMode') == 'overlay') {
			$viewer->assign('MODULE_MODEL', $moduleModel);
			$this->setModuleInfo($request, $moduleModel);
			$viewer->assign('SCRIPTS', $this->getOverlayHeaderScripts($request));

			$detailViewLinkParams = array('MODULE' => $moduleName, 'RECORD' => $recordId);
			$detailViewLinks = $this->record->getDetailViewLinks($detailViewLinkParams);
			$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
			return $viewer->view('OverlayDetailView.tpl', $moduleName);
		}
		return $viewer->view('DetailViewFullContents.tpl', $moduleName, true);
	}

	protected function buildCampaignPhaseIndices(Vtiger_Record_Model $recordModel) {
		$count = (int) $recordModel->get('campaign_phase_count');
		if ($count < 2) {
			$count = 2;
		}
		if ($count > 5) {
			$count = 5;
		}
		$indices = array();
		for ($i = 1; $i <= 5; $i++) {
			if ($i <= $count || $this->campaignPhaseHasData($recordModel, $i)) {
				$indices[] = $i;
			}
		}
		sort($indices);
		return $indices;
	}

	protected function campaignPhaseHasData(Vtiger_Record_Model $recordModel, $i) {
		$fields = array(
			"phase{$i}_expected",
			"phase{$i}_actual",
			"phase{$i}_comment",
			"phase{$i}_start_date",
			"phase{$i}_end_date",
		);
		foreach ($fields as $f) {
			$v = $recordModel->get($f);
			if ($v !== '' && $v !== null && $v !== false) {
				return true;
			}
		}
		return false;
	}

	protected function buildCampaignDocumentsSection(Vtiger_Record_Model $recordModel) {
		$moduleModel = $recordModel->getModule();
		$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$documentsModule = Vtiger_Module_Model::getInstance('Documents');
		if (!$documentsModule) {
			return array('enabled' => false);
		}
		if (!$userPrivilegesModel->hasModuleActionPermission($documentsModule->getId(), 'DetailView')) {
			return array('enabled' => false);
		}
		if (!$moduleModel->isModuleRelated('Documents')) {
			return array('enabled' => false);
		}

		$recordId = $recordModel->getId();
		$relationId = null;
		foreach ($moduleModel->getRelations() as $relation) {
			if ($relation->get('relatedModuleName') === 'Documents') {
				$relationId = $relation->getId();
				break;
			}
		}

		$relatedListUrl = $recordModel->getDetailViewUrl() . '&relatedModule=Documents&mode=showRelatedList';
		if ($relationId) {
			$relatedListUrl .= '&relationId=' . $relationId;
		}

		$canAdd = $userPrivilegesModel->hasModuleActionPermission($documentsModule->getId(), 'CreateView');
		$addUrl = 'index.php?module=Documents&view=Edit&relationOperation=true&sourceModule=Campaigns&sourceRecord=' . $recordId;

		return array(
			'enabled' => true,
			'related_list_url' => $relatedListUrl,
			'add_url' => $addUrl,
			'can_add' => $canAdd,
		);
	}
}
