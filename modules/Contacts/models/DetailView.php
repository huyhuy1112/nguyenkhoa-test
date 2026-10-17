<?php
/*+***********************************************************************************
 * Contacts Detail View — inherit Accounts base, strip franchise-only toolbar links.
 *************************************************************************************/

class Contacts_DetailView_Model extends Accounts_DetailView_Model {

	public function getDetailViewLinks($linkParams) {
		$linkModelList = parent::getDetailViewLinks($linkParams);
		$skipLabels = array(
			'LBL_PRINT_FRANCHISE_CONTRACT',
			'LBL_PREVIEW_FRANCHISE_CONTRACT',
			'LBL_EXPORT_FRANCHISE_CONTRACT_WORD',
			'LBL_SHOW_ACCOUNT_HIERARCHY',
		);

		foreach (array('DETAILVIEWBASIC', 'DETAILVIEW') as $linkType) {
			if (empty($linkModelList[$linkType]) || !is_array($linkModelList[$linkType])) {
				continue;
			}
			$cleaned = array();
			foreach ($linkModelList[$linkType] as $link) {
				$label = is_object($link) ? $link->getLabel() : '';
				if ($label !== '' && in_array($label, $skipLabels, true)) {
					continue;
				}
				$cleaned[] = $link;
			}
			$linkModelList[$linkType] = $cleaned;
		}

		return $linkModelList;
	}
}
