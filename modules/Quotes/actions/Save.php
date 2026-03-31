<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class Quotes_Save_Action extends Inventory_Save_Action {
	protected function normalizeOpportunityNameForQuoteSubject($name) {
		$name = trim((string) $name);
		// remove leading YYMMDD- (e.g. 260313-)
		$name = preg_replace('/^\d{6}-/', '', $name);
		$name = trim($name);
		if ($name === '') return '';
		if (!preg_match('/^TDB Quo-/i', $name)) {
			$name = 'TDB Quo-' . $name;
		}
		return $name;
	}

	public function saveRecord($request) {
		// Auto-fill subject from Opportunity name if subject is empty.
		// This does not change any inventory/tax math; it only ensures a consistent Quote subject.
		try {
			$subject = trim((string) $request->get('subject'));
			if ($subject === '') {
				$potentialId = (int) $request->get('potential_id');
				if ($potentialId > 0) {
					$potential = Vtiger_Record_Model::getInstanceById($potentialId, 'Potentials');
					$oppName = '';
					if ($potential) {
						// In vtiger, Opportunity name field is typically "potentialname"
						$oppName = (string) $potential->get('potentialname');
						if ($oppName === '') {
							$oppName = (string) $potential->getName();
						}
					}
					$oppName = $this->normalizeOpportunityNameForQuoteSubject($oppName);
					if ($oppName !== '') {
						$request->set('subject', $oppName);
					}
				}
			}
		} catch (Exception $e) {
			// If anything goes wrong, fall back to default save behavior.
		}

		return parent::saveRecord($request);
	}
}
