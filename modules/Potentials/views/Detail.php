<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Potentials_Detail_View extends Vtiger_Detail_View {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('showRelatedRecords');
	}

	protected function ensureSalesApp(Vtiger_Request $request) {
		require_once 'modules/Potentials/helpers/SalesAppGuard.php';
		Potentials_SalesAppGuard::enforce($request);
	}

	protected function assignSalesApp(Vtiger_Request $request) {
		require_once 'modules/Potentials/helpers/SalesAppGuard.php';
		Potentials_SalesAppGuard::assignViewer($request, $this->getViewer($request));
	}

	protected function assignLinkedLeadAddress(Vtiger_Request $request, $recordId) {
		$recordId = (int)$recordId;
		if ($recordId <= 0) {
			return;
		}
		require_once 'modules/Leads/models/ConvertService.php';
		$viewer = $this->getViewer($request);
		$fullAddress = '';
		$leadId = Leads_ConvertService::getLinkedLeadIdByPotential($recordId);
		if ($leadId) {
			$adb = PearDatabase::getInstance();
			$res = $adb->pquery(
				"SELECT district, address_line, area FROM bace_lead_profile WHERE leadid = ?",
				array((int)$leadId)
			);
			if ($res && $adb->num_rows($res) > 0) {
				$district = decode_html((string)$adb->query_result($res, 0, 'district'));
				$addressLine = decode_html((string)$adb->query_result($res, 0, 'address_line'));
				$area = decode_html((string)$adb->query_result($res, 0, 'area'));
				$parts = array();
				if ($district !== '') {
					$parts[] = $district;
				}
				if ($addressLine !== '') {
					$parts[] = $addressLine;
				}
				$fullAddress = implode(', ', $parts);
				if ($fullAddress === '' && $area !== '') {
					$fullAddress = $area;
				}
			}
		}
		if ($fullAddress === '') {
			try {
				$potential = Vtiger_Record_Model::getInstanceById($recordId, 'Potentials');
				$contactId = (int)$potential->get('contact_id');
				if ($contactId > 0) {
					$contact = Vtiger_Record_Model::getInstanceById($contactId, 'Contacts');
					$street = trim((string)$contact->get('mailingstreet'));
					$city = trim((string)$contact->get('mailingcity'));
					$state = trim((string)$contact->get('mailingstate'));
					$parts = array_filter(array($street, $city, $state));
					$fullAddress = implode(', ', $parts);
				}
			} catch (Exception $e) {
				// ignore
			}
		}
		$viewer->assign('MK_OPP_FULL_ADDRESS', $fullAddress);
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->ensureSalesApp($request);
		$recordId = (int)$request->get('record');
		if ($recordId > 0 && strtolower((string)$request->get('view')) === 'detail') {
			require_once 'modules/Leads/models/ConvertService.php';
			Leads_ConvertService::ensurePotentialTagsFromLead($recordId);
			$this->assignLinkedLeadAddress($request, $recordId);
		}
		parent::preProcess($request, false);
		$this->assignSalesApp($request);
		if ($display) {
			$this->preProcessDisplay($request);
		}
	}
	/**
	 * Function to get activities
	 * @param Vtiger_Request $request
	 * @return <List of activity models>
	 */
	public function getActivities(Vtiger_Request $request) {
		$moduleName = 'Calendar';
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if($currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
			$moduleName = $request->getModule();
			$recordId = $request->get('record');

			$pageNumber = $request->get('page');
			if(empty ($pageNumber)) {
				$pageNumber = 1;
			}
			$pagingModel = new Vtiger_Paging_Model();
			$pagingModel->set('page', $pageNumber);
			$pagingModel->set('limit', 10);

			if(!$this->record) {
				$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
			}
			$recordModel = $this->record->getRecord();
			$moduleModel = $recordModel->getModule();

			$relatedActivities = $moduleModel->getCalendarActivities('', $pagingModel, 'all', $recordId);

			$viewer = $this->getViewer($request);
			$viewer->assign('RECORD', $recordModel);
			$viewer->assign('MODULE_NAME', $moduleName);
			$viewer->assign('PAGING_MODEL', $pagingModel);
			$viewer->assign('PAGE_NUMBER', $pageNumber);
			$viewer->assign('ACTIVITIES', $relatedActivities);

			return $viewer->view('RelatedActivities.tpl', $moduleName, true);
		}
	}
}
