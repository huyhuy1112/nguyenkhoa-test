<?php
/*+***********************************************************************************
 * Campaigns: Detail actions — Duplicate + Delete as primary; drop Edit from header row.
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

		$filtered = array();
		if (!empty($linkModelList['DETAILVIEW'])) {
			foreach ($linkModelList['DETAILVIEW'] as $lm) {
				$url = $lm->getUrl();
				$lbl = $lm->getLabel();
				if ($lbl === 'LBL_EDIT') {
					continue;
				}
				if ($lbl === 'LBL_DUPLICATE') {
					continue;
				}
				if (stripos($url, 'deleteRecord') !== false) {
					continue;
				}
				if ($lbl === sprintf('%s %s', getTranslatedString('LBL_DELETE', $moduleName), vtranslate('SINGLE_' . $moduleName, $moduleName))) {
					continue;
				}
				$filtered[] = $lm;
			}
		}

		if (Users_Privileges_Model::isPermitted($moduleName, 'EditView', $recordId)) {
			array_unshift(
				$filtered,
				Vtiger_Link_Model::getInstanceFromValues(array(
					'linktype' => 'DETAILVIEW',
					'linklabel' => 'LBL_EDIT',
					'linkurl' => $recordModel->getEditViewUrl(),
					'linkicon' => '',
				))
			);
		}

		$linkModelList['DETAILVIEW'] = $filtered;

		return $linkModelList;
	}
}
