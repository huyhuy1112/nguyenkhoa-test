<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Contacts_Save_Action extends Vtiger_Save_Action {

	public function process(Vtiger_Request $request) {
		//To stop saveing the value of salutation as '--None--'
		$salutationType = $request->get('salutationtype');
		if ($salutationType === '--None--') {
			$request->set('salutationtype', '');
		}
		require_once 'modules/Contacts/helpers/PhoneField.php';
		Contacts_PhoneField_Helper::sanitizeRequest($request);
		Contacts_PhoneField_Helper::validateRequest($request);
		parent::process($request);
	}

	public function saveRecord($request) {
		$recordModel = parent::saveRecord($request);
		$this->applyModernListExtras($request, $recordModel);
		return $recordModel;
	}

	/**
	 * Persist list-parity extras from modern Create/Edit (tags).
	 */
	protected function applyModernListExtras(Vtiger_Request $request, $recordModel) {
		if (!$recordModel) {
			return;
		}
		$recordId = (int) $recordModel->getId();
		if ($recordId <= 0) {
			return;
		}
		$tagsRaw = $request->get('mk_tags');
		if ($tagsRaw === null || $tagsRaw === '') {
			return;
		}
		try {
			require_once 'modules/Contacts/models/ModernService.php';
			if (is_string($tagsRaw)) {
				$decoded = json_decode($tagsRaw, true);
				$tagsRaw = is_array($decoded) ? $decoded : preg_split('/\s*,\s*/', $tagsRaw);
			}
			if (!is_array($tagsRaw)) {
				$tagsRaw = array();
			}
			Contacts_ModernService::saveTags($recordId, $tagsRaw);
		} catch (Exception $e) {
			global $log;
			if ($log) {
				$log->error('Contacts mk_tags save: ' . $e->getMessage());
			}
		}
	}
}
