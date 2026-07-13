<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Potentials_ListView_Model extends Vtiger_ListView_Model {

	/**
	 * Function to get the list of Mass actions for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associative array of Link type to List of  Vtiger_Link_Model instances for Mass Actions
	 */
	public function getListViewMassActions($linkParams) {
		$massActionLinks = parent::getListViewMassActions($linkParams);

		$currentUserModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$emailModuleModel = Vtiger_Module_Model::getInstance('Emails');

		if($currentUserModel->hasModulePermission($emailModuleModel->getId())) {
			$massActionLink = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_SEND_EMAIL',
				'linkurl' => 'javascript:Vtiger_List_Js.triggerSendEmail("index.php?module='.$this->getModule()->getName().'&view=MassActionAjax&mode=showComposeEmailForm&step=step1","Emails");',
				'linkicon' => ''
			);
			$massActionLinks['LISTVIEWMASSACTION'][] = Vtiger_Link_Model::getInstanceFromValues($massActionLink);
		}

		return $massActionLinks;
	}

	/**
	 * Enrich popup rows with customer display name + phone (from related Contact).
	 */
	public function getListViewEntries($pagingModel) {
		$entries = parent::getListViewEntries($pagingModel);
		$this->enrichPopupCustomerMeta($entries);
		return $entries;
	}

	/**
	 * @param Vtiger_Record_Model[] $entries
	 */
	protected function enrichPopupCustomerMeta($entries) {
		if (empty($entries) || !is_array($entries)) {
			return;
		}
		$view = isset($_REQUEST['view']) ? (string) $_REQUEST['view'] : '';
		if ($view !== 'Popup' && $view !== 'PopupAjax') {
			return;
		}

		$ids = array_map('intval', array_keys($entries));
		$ids = array_values(array_filter($ids));
		if (!$ids) {
			return;
		}

		$db = PearDatabase::getInstance();
		$placeholders = generateQuestionMarks($ids);
		$sql = "SELECT p.potentialid, p.potentialname, p.contact_id, p.related_to,
				acc.accountname,
				cd.firstname, cd.lastname, cd.mobile, cd.phone
			FROM vtiger_potential p
			INNER JOIN vtiger_crmentity ce ON ce.crmid = p.potentialid AND ce.deleted = 0
			LEFT JOIN vtiger_account acc ON acc.accountid = p.related_to
			LEFT JOIN vtiger_contactdetails cd ON cd.contactid = p.contact_id
			WHERE p.potentialid IN ($placeholders)";
		$result = $db->pquery($sql, $ids);
		if (!$result) {
			return;
		}

		$metaById = array();
		$rowCount = $db->num_rows($result);
		for ($i = 0; $i < $rowCount; $i++) {
			$pid = (int) $db->query_result($result, $i, 'potentialid');
			$contactName = trim(
				decode_html((string) $db->query_result($result, $i, 'firstname')) . ' ' .
				decode_html((string) $db->query_result($result, $i, 'lastname'))
			);
			if ($contactName === '.' || $contactName === '') {
				$contactName = '';
			}
			$accountName = trim(decode_html((string) $db->query_result($result, $i, 'accountname')));
			$title = trim(decode_html((string) $db->query_result($result, $i, 'potentialname')));
			$customerName = $contactName !== '' ? $contactName : ($accountName !== '' ? $accountName : $title);
			$mobile = trim((string) $db->query_result($result, $i, 'mobile'));
			$phone = trim((string) $db->query_result($result, $i, 'phone'));
			$displayPhone = $mobile !== '' ? $mobile : $phone;
			$metaById[$pid] = array(
				'mk_customer_name' => $customerName,
				'mk_phone' => $displayPhone,
			);
		}

		foreach ($entries as $recordId => $recordModel) {
			if (!is_object($recordModel) || !isset($metaById[(int) $recordId])) {
				continue;
			}
			$meta = $metaById[(int) $recordId];
			$raw = $recordModel->getRawData();
			if (!is_array($raw)) {
				$raw = array();
			}
			$raw['mk_customer_name'] = $meta['mk_customer_name'];
			$raw['mk_phone'] = $meta['mk_phone'];
			$recordModel->setRawData($raw);
			$recordModel->set('mk_customer_name', $meta['mk_customer_name']);
			$recordModel->set('mk_phone', $meta['mk_phone']);
		}
	}
}
?>
