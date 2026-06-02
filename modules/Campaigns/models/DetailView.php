<?php
/*+***********************************************************************************
 * Campaigns: Detail header — only Duplicate + Delete; no Edit / More (DETAILVIEW empty).
 * Related-module icon tabs hidden via getDetailViewRelatedLinks().
 *************************************************************************************/

class Campaigns_DetailView_Model extends Vtiger_DetailView_Model {

	public function getDetailViewLinks($linkParams) {
		$linkModelList = parent::getDetailViewLinks($linkParams);
		$moduleModel = $this->getModule();
		$recordModel = $this->getRecord();
		$moduleName = $moduleModel->getName();
		$recordId = $recordModel->getId();

		$newBasic = array();

		if ($moduleModel->isDuplicateOptionAllowed('CreateView', $recordId)) {
			$newBasic[] = Vtiger_Link_Model::getInstanceFromValues(array(
				'linktype' => 'DETAILVIEWBASIC',
				'linklabel' => 'LBL_DUPLICATE',
				'linkurl' => $recordModel->getDuplicateRecordUrl(),
				'linkicon' => '',
			));
		}

		if (Users_Privileges_Model::isPermitted($moduleName, 'Delete', $recordId)) {
			$newBasic[] = Vtiger_Link_Model::getInstanceFromValues(array(
				'linktype' => 'DETAILVIEWBASIC',
				'linklabel' => sprintf('%s %s', getTranslatedString('LBL_DELETE', $moduleName), vtranslate('SINGLE_' . $moduleName, $moduleName)),
				'linkurl' => 'javascript:Vtiger_Detail_Js.deleteRecord("' . $recordModel->getDeleteUrl() . '")',
				'linkicon' => '',
			));
		}

		$linkModelList['DETAILVIEWBASIC'] = $newBasic;
		$linkModelList['DETAILVIEW'] = array();

		return $linkModelList;
	}

	public function getDetailViewRelatedLinks() {
		$links = parent::getDetailViewRelatedLinks();
		$out = array();
		foreach ($links as $link) {
			if (!empty($link['linktype']) && $link['linktype'] === 'DETAILVIEWRELATED') {
				continue;
			}
			$out[] = $link;
		}
		return $out;
	}
}
