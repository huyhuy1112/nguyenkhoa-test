<?php
/*
 * Campaigns Detail: custom blocks (phases, files) for all full-detail render paths.
 */

class Campaigns_Detail_View extends Vtiger_Detail_View {

	/**
	 * Full detail (record blocks + dashboard + phases + file metadata). Use for every path that renders DetailViewFullContents / DetailViewBlockView.
	 *
	 * @param string $renderPath e.g. full | ajax | basic (summary+full contents)
	 */
	protected function assignCampaignCustomDetailData(
		Vtiger_Request $request,
		Vtiger_Record_Model $recordModel,
		array $structuredValues,
		$renderPath
	) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$hasDescriptionFieldInStructure = false;
		foreach ($structuredValues as $_blk => $_fields) {
			if (!is_array($_fields)) {
				continue;
			}
			foreach ($_fields as $_fn => $_fm) {
				$n = (is_object($_fm) && method_exists($_fm, 'getName')) ? $_fm->getName() : (string) $_fn;
				if ($n === 'description') {
					$hasDescriptionFieldInStructure = true;
					break 2;
				}
			}
		}

		require_once 'modules/Campaigns/models/CampaignPhaseHelper.php';
		$phaseIndices = Campaigns_CampaignPhase_Helper::phaseIndicesFromRecord($recordModel);
		if (empty($phaseIndices)) {
			$phaseIndices = array(1, 2);
		}
		$viewer->assign('CAMPAIGN_PHASE_INDICES', $phaseIndices);
		$viewer->assign('CAMPAIGN_PHASE_INDICES_JSON', Vtiger_Functions::jsonEncode($phaseIndices));
		$viewer->assign('CAMPAIGN_DOCUMENTS_SECTION', $this->buildCampaignDocumentsSection($recordModel));
		$viewer->assign('CAMPAIGN_PHASE_SUMS', $this->buildCampaignPhaseKpiSums($recordModel, $phaseIndices));
		$campaignFilesSection = $this->buildCampaignDetailDescFilesSection($recordModel);
		$viewer->assign('CAMPAIGN_DETAIL_DESC_FILES', $campaignFilesSection);
		$viewer->assign('CAMPAIGN_FILES', $campaignFilesSection['files']);
		$viewer->assign(
			'CAMPAIGN_FILES_COUNT',
			is_array($campaignFilesSection['files']) ? count($campaignFilesSection['files']) : 0
		);
		$viewer->assign('CAMPAIGN_RENDER_FILES_FALLBACK', $moduleName === 'Campaigns' && !$hasDescriptionFieldInStructure);
		$viewer->assign('CAMPAIGN_DETAIL_RENDER_PATH', $renderPath);

		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', Vtiger_Functions::jsonEncode($picklistDependencyDatasource));
		$viewer->assign('DAY_STARTS', '');
	}

	/**
	 * Summary layout first paint (many users default_record_view = Summary): includes DetailViewFullContents via DetailViewSummaryContents — must assign CAMPAIGN_* same as full detail.
	 */
	public function showModuleBasicView(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if ($moduleName !== 'Campaigns') {
			parent::showModuleBasicView($request);
			return;
		}

		$recordId = $request->get('record');
		if (!$this->record) {
			$this->record = Campaigns_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();

		$detailViewLinkParams = array('MODULE' => $moduleName, 'RECORD' => $recordId);
		$detailViewLinks = $this->record->getDetailViewLinks($detailViewLinkParams);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE_SUMMARY', $this->showModuleSummaryView($request));
		$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE', $moduleName);

		$recordStrucure = Vtiger_RecordStructure_Model::getInstanceFromRecordModel(
			$recordModel,
			Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_DETAIL
		);
		$structuredValues = $recordStrucure->getStructure();
		$structuredValues = $this->removeLegacyProductFieldFromDetailStructure($structuredValues);
		unset($structuredValues['LBL_CAMPAIGN_PHASES']);

		$moduleModel = $recordModel->getModule();
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());

		$this->assignCampaignCustomDetailData($request, $recordModel, $structuredValues, 'basic');

		$viewer->assign('RECORD_STRUCTURE', $structuredValues);
		echo $viewer->view('DetailViewSummaryContents.tpl', $moduleName, true);
	}

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
		if ($moduleName === 'Campaigns') {
			$structuredValues = $this->removeLegacyProductFieldFromDetailStructure($structuredValues);
			unset($structuredValues['LBL_CAMPAIGN_PHASES']);
		}
		$moduleModel = $recordModel->getModule();

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('RECORD_STRUCTURE', $structuredValues);
		$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		$viewer->assign('MODULE', $moduleName);

		if ($moduleName === 'Campaigns') {
			$renderPath = $request->isAjax() ? 'ajax' : 'full';
			$this->assignCampaignCustomDetailData($request, $recordModel, $structuredValues, $renderPath);
		}

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

	/**
	 * Drop legacy single Product reference from detail blocks (Products & Services relation stays).
	 */
	protected function removeLegacyProductFieldFromDetailStructure(array $structuredValues) {
		$hide = array('product' => true, 'product_id' => true);
		foreach ($structuredValues as $blockLabel => &$blockFields) {
			if (!is_array($blockFields)) {
				continue;
			}
			foreach ($blockFields as $fieldName => $fieldModel) {
				$name = '';
				if (is_object($fieldModel) && method_exists($fieldModel, 'getName')) {
					$name = (string) $fieldModel->getName();
				}
				if ($name === '') {
					$name = (string) $fieldName;
				}
				$lname = strtolower($name);
				if (isset($hide[$name]) || isset($hide[$lname])) {
					unset($blockFields[$fieldName]);
				}
			}
		}
		unset($blockFields);
		return $structuredValues;
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

	protected function buildCampaignPhaseKpiSums(Vtiger_Record_Model $recordModel, array $phaseIndices) {
		$expected = 0.0;
		$actual = 0.0;
		foreach ($phaseIndices as $i) {
			$expected += (float) $recordModel->get('phase' . (int) $i . '_expected');
			$actual += (float) $recordModel->get('phase' . (int) $i . '_actual');
		}
		return array(
			'expected' => $expected,
			'actual' => $actual,
			'expected_fmt' => number_format($expected, 0, '.', ','),
			'actual_fmt' => number_format($actual, 0, '.', ','),
		);
	}

	/**
	 * Campaign files: vtiger_attachments + vtiger_seattachmentsrel (setype Campaigns Attachment).
	 */
	protected function buildCampaignDetailDescFilesSection(Vtiger_Record_Model $recordModel) {
		require_once 'modules/Campaigns/models/CampaignFilesHelper.php';
		$recordId = (int) $recordModel->getId();
		if ($recordId <= 0) {
			return array('enabled' => false, 'files' => array(), 'can_edit' => false);
		}
		$rows = Campaigns_CampaignFiles_Helper::getFilesForCampaign($recordId);
		$list = array();
		foreach ($rows as $f) {
			$aid = (int) $f['id'];
			$list[] = array(
				'attachmentsid' => $aid,
				'id' => $aid,
				'original_name' => $f['original_name'],
				'is_image' => !empty($f['is_image']),
				'download_url' => 'index.php?module=Campaigns&action=DownloadCampaignFile&record=' . $recordId . '&fileid=' . $aid,
				'preview_url' => 'index.php?module=Campaigns&action=DownloadCampaignFile&record=' . $recordId . '&fileid=' . $aid . '&inline=1',
			);
		}
		$canEdit = Users_Privileges_Model::isPermitted('Campaigns', 'EditView', $recordId);
		return array(
			'enabled' => true,
			'files' => $list,
			'can_edit' => $canEdit,
		);
	}
}
