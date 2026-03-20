<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Contacts_ListView_Model extends Vtiger_ListView_Model {

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

		$SMSNotifierModuleModel = Vtiger_Module_Model::getInstance('SMSNotifier');
		if($SMSNotifierModuleModel && $currentUserModel->hasModulePermission($SMSNotifierModuleModel->getId())) {
			$massActionLink = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_SEND_SMS',
				'linkurl' => 'javascript:Vtiger_List_Js.triggerSendSms("index.php?module='.$this->getModule()->getName().'&view=MassActionAjax&mode=showSendSMSForm","SMSNotifier");',
				'linkicon' => ''
			);
			$massActionLinks['LISTVIEWMASSACTION'][] = Vtiger_Link_Model::getInstanceFromValues($massActionLink);
		}
		
		$moduleModel = $this->getModule();
		if($currentUserModel->hasModuleActionPermission($moduleModel->getId(), 'EditView')) {
			$massActionLink = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_TRANSFER_OWNERSHIP',
				'linkurl' => 'javascript:Vtiger_List_Js.triggerTransferOwnership("index.php?module='.$moduleModel->getName().'&view=MassActionAjax&mode=transferOwnership")',
				'linkicon' => ''
			);
			$massActionLinks['LISTVIEWMASSACTION'][] = Vtiger_Link_Model::getInstanceFromValues($massActionLink);
		}

		return $massActionLinks;
	}

	/**
	 * Function to get the list of listview links for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associate array of Link Type to List of Vtiger_Link_Model instances
	 */
	function getListViewLinks($linkParams) {
		$links = parent::getListViewLinks($linkParams);

		$index=0;
		foreach($links['LISTVIEWBASIC'] as $link) {
			if($link->linklabel == 'Send SMS') {
				unset($links['LISTVIEWBASIC'][$index]);
			}
			$index++;
		}
		return $links;
	}

	/**
	 * Enforce tag-based visibility in Contacts ListView (highest priority).
	 * Non-privileged users can only see records tagged with their role name.
	 */
	function getQuery() {
		$listQuery = parent::getQuery();

		$currentUser = Users_Record_Model::getCurrentUserModel();
		require_once 'modules/Contacts/models/TagAccessHelper.php';

		if (TagAccessHelper::isPrivilegedUser($currentUser)) {
			error_log('[TagACL][Contacts] getQuery() privileged userId=' . $currentUser->getId() .
				' isAdmin=' . ($currentUser->isAdminUser() ? '1' : '0') .
				' role=' . $currentUser->getUserRoleName());
			return $listQuery;
		}

		$tags = TagAccessHelper::getUserAccessTags($currentUser);
		$isPriv = TagAccessHelper::isPrivilegedUser($currentUser);
		$isAdmin = $currentUser->isAdminUser() ? '1' : '0';
		$roleName = $currentUser->getUserRoleName();

		if (empty($tags)) {
			// Secure default: show nothing
			error_log('[TagACL][Contacts] getQuery() userId=' . $currentUser->getId() .
				' role=' . $roleName . ' isAdmin=' . $isAdmin . ' isPrivileged=' . ($isPriv ? '1' : '0') .
				' mappedTags=[] => DENY_ALL');
			$pos = stripos($listQuery, ' where ');
			$listQuery .= ($pos !== false) ? ' AND 1=0' : ' WHERE 1=0';
			return $listQuery;
		}

		// Escape literals for safe inline SQL (ListView executes without bound params)
		$tagLiterals = array();
		foreach ($tags as $t) {
			$t = Vtiger_Util_Helper::escapeSqlString((string)$t);
			$tagLiterals[] = "'" . $t . "'";
		}
		$inList = implode(',', $tagLiterals);

		$exists = " EXISTS (
			SELECT 1
			FROM vtiger_freetagged_objects fto
			INNER JOIN vtiger_freetags ft ON ft.id = fto.tag_id
			WHERE fto.object_id = vtiger_contactdetails.contactid
			  AND fto.module = 'Contacts'
			  AND ft.tag IN ({$inList})
		) ";

		$pos = stripos($listQuery, ' where ');
		if ($pos !== false) {
			$listQuery .= " AND {$exists}";
		} else {
			$listQuery .= " WHERE {$exists}";
		}

		error_log('[TagACL][Contacts] getQuery() userId=' . $currentUser->getId() .
			' role=' . $roleName .
			' mappedTags=' . json_encode($tags, JSON_UNESCAPED_UNICODE) .
			' isAdmin=' . $isAdmin .
			' isPrivileged=' . ($isPriv ? '1' : '0') .
			' sql=' . $listQuery);

		return $listQuery;
	}
}