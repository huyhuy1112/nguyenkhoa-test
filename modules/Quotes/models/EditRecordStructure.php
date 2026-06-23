<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * Quotes Edit View Record Structure Model
 */
class Quotes_EditRecordStructure_Model extends Inventory_EditRecordStructure_Model {

	public function getStructure() {
		$structure = parent::getStructure();
		if (!isset($structure['LBL_TERMS_INFORMATION']['terms_conditions'])) {
			return $structure;
		}

		require_once 'modules/Quotes/helpers/QuoteBaService.php';
		$termsField = $structure['LBL_TERMS_INFORMATION']['terms_conditions'];
		$recordModel = $this->getRecord();

		if (empty($recordModel) || !$recordModel->getId()) {
			// Modern Quotes create: user nhập điều khoản thủ công — không auto-fill từ Settings T&C.
			$termsField->set('fieldvalue', '');
		} else {
			$termsField->set(
				'fieldvalue',
				Quotes_QuoteBaService_Helper::stripSignatureFromTermsHtml($termsField->get('fieldvalue'))
			);
		}

		return $structure;
	}
}
