<?php
/* ***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ************************************************************************************/

require_once 'modules/Leads/models/ConvertService.php';

class LeadHandler extends VTEventHandler {

	function handleEvent($eventName, $entityData) {
		if ($eventName !== 'vtiger.lead.convertlead') {
			return;
		}
		if (!$entityData || !is_object($entityData)) {
			return;
		}
		$leadWsId = $entityData->getId();
		if (!$leadWsId) {
			return;
		}
		$parts = vtws_getIdComponents($leadWsId);
		$leadId = isset($parts[1]) ? (int)$parts[1] : 0;
		if ($leadId <= 0) {
			return;
		}
		$entityIds = isset($entityData->entityIds) ? $entityData->entityIds : array();
		if (empty($entityIds) || !is_array($entityIds)) {
			return;
		}
		$targets = array();
		foreach ($entityIds as $module => $wsId) {
			if (!$wsId) {
				continue;
			}
			$idParts = vtws_getIdComponents($wsId);
			$crmId = isset($idParts[1]) ? (int)$idParts[1] : 0;
			if ($crmId > 0) {
				$targets[$module] = $crmId;
			}
		}
		if (!empty($targets)) {
			Leads_ConvertService::transferLeadTags($leadId, $targets);
		}
	}
}
