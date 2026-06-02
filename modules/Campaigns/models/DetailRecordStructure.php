<?php
/*
 * Campaigns Detail record structure: shorten phase comment field labels (match Edit view).
 */

class Campaigns_DetailRecordStructure_Model extends Vtiger_DetailRecordStructure_Model {

	public function getStructure() {
		$values = parent::getStructure();
		foreach ($values as $blockLabel => $blockFields) {
			if (!is_array($blockFields)) {
				continue;
			}
			foreach ($blockFields as $fieldName => $fieldModel) {
				if (!is_object($fieldModel)) {
					continue;
				}
				if (preg_match('/^phase[1-5]_comment$/', (string) $fieldName)) {
					$fieldModel->set('label', 'LBL_COMMENT_SHORT');
				}
			}
		}
		return $values;
	}
}
