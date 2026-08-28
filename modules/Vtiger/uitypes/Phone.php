<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_Phone_UIType extends Vtiger_Base_UIType {

	/**
	 * Function to get the Template name for the current UI Type object
	 * @return <String> - Template Name
	 */
	public function getTemplateName() {
		return 'uitypes/Phone.tpl';
	}

	/**
	 * Function to get the Detailview template name for the current UI Type Object
	 * @return <String> - Template Name
	 */
	public function getDetailViewTemplateName() {
		return 'uitypes/PhoneDetailView.tpl';
	}

	/**
	 * Display value: "xxxx xxx xxx" for 10-digit VN phones.
	 *
	 * @param mixed $value
	 * @param mixed $record
	 * @param mixed $recordInstance
	 * @return string
	 */
	public function getDisplayValue($value, $record = false, $recordInstance = false) {
		require_once 'modules/Vtiger/helpers/MkPhoneFormat.php';
		return Vtiger_MkPhoneFormat_Helper::formatDisplay($value);
	}

	/**
	 * Edit view display (initial value in input) — same grouping for UX.
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function getEditViewDisplayValue($value) {
		require_once 'modules/Vtiger/helpers/MkPhoneFormat.php';
		return Vtiger_MkPhoneFormat_Helper::formatDisplay($value);
	}

	/**
	 * Persist digits-only so DB stays clean; UI re-formats on load.
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function getDBInsertValue($value) {
		require_once 'modules/Vtiger/helpers/MkPhoneFormat.php';
		$digits = Vtiger_MkPhoneFormat_Helper::digitsOnly($value);
		return $digits !== '' ? $digits : $value;
	}
}
