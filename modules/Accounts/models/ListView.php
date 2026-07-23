<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Accounts_ListView_Model extends Vtiger_ListView_Model {

	/**
	 * Modern Organizations list (Sales / Marketing / Support): fixed column set.
	 */
	private function isModernOrganizationsListRequest() {
		$app = '';
		if (!empty($_REQUEST['app'])) {
			$app = strtoupper((string) $_REQUEST['app']);
		}
		return in_array($app, array('SALES', 'MARKETING', 'SUPPORT'), true);
	}

	private function getModernOrganizationsListFieldOrder() {
		// Tuibao list: số HĐ, tên công ty, SĐT, địa chỉ, phụ trách
		return array('tb_contract_no', 'accountname', 'phone', 'tb_store_address', 'assigned_user_id');
	}

	/**
	 * Headers alone are not enough — QueryGenerator must SELECT these columns
	 * or list cells stay empty.
	 */
	private function syncModernListQueryFields() {
		if (!$this->isModernOrganizationsListRequest()) {
			return;
		}
		$queryGenerator = $this->get('query_generator');
		if (!$queryGenerator) {
			return;
		}
		$fields = $this->getModernOrganizationsListFieldOrder();
		$fields[] = 'id';
		$fields[] = 'starred';
		$queryGenerator->setFields($fields);
	}

	public function getListViewHeaders() {
		$headers = parent::getListViewHeaders();
		if (!$this->isModernOrganizationsListRequest()) {
			return $headers;
		}

		$this->syncModernListQueryFields();

		$module = $this->getModule();
		$filtered = array();
		foreach ($this->getModernOrganizationsListFieldOrder() as $fieldName) {
			if (isset($headers[$fieldName])) {
				$filtered[$fieldName] = $headers[$fieldName];
			} else {
				$fieldInstance = Vtiger_Field_Model::getInstance($fieldName, $module);
				if ($fieldInstance && in_array((int) $fieldInstance->get('presence'), array(0, 2), true)) {
					$fieldInstance->set('listViewRawFieldName', $fieldInstance->get('column'));
					$filtered[$fieldName] = $fieldInstance;
				}
			}
		}

		$labelOverrides = array(
			'tb_contract_no' => 'Số hợp đồng',
			'accountname' => 'Tên công ty',
			'phone' => 'Số điện thoại',
			'tb_store_address' => 'Địa chỉ',
			'assigned_user_id' => 'Phụ trách',
		);
		foreach ($filtered as $fieldName => $fieldInstance) {
			if (isset($labelOverrides[$fieldName])) {
				$fieldInstance->set('label', $labelOverrides[$fieldName]);
				$fieldInstance->set('fieldlabel', $labelOverrides[$fieldName]);
			}
		}
		return $filtered;
	}

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
		if(!empty($SMSNotifierModuleModel) && $currentUserModel->hasModulePermission($SMSNotifierModuleModel->getId())) {
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
	 * Enforce tag-based visibility in Accounts/Organizations ListView (highest priority).
	 * Non-privileged users can only see records tagged with their role name.
	 */
	function getQuery() {
		$this->syncModernListQueryFields();
		$listQuery = parent::getQuery();

		$currentUser = Users_Record_Model::getCurrentUserModel();
		require_once 'modules/Contacts/models/TagAccessHelper.php';

		if (TagAccessHelper::isPrivilegedUser($currentUser)) {
			error_log('[TagACL][Accounts] getQuery() privileged userId=' . $currentUser->getId() .
				' isAdmin=' . ($currentUser->isAdminUser() ? '1' : '0') .
				' role=' . $currentUser->getUserRoleName());
			return $listQuery;
		}

		$tags = TagAccessHelper::getUserAccessTags($currentUser);
		$isPriv = TagAccessHelper::isPrivilegedUser($currentUser);
		$isAdmin = $currentUser->isAdminUser() ? '1' : '0';
		$roleName = $currentUser->getUserRoleName();

		if (empty($tags)) {
			error_log('[TagACL][Accounts] getQuery() userId=' . $currentUser->getId() .
				' role=' . $roleName . ' isAdmin=' . $isAdmin . ' isPrivileged=' . ($isPriv ? '1' : '0') .
				' mappedTags=[] => DENY_ALL');
			$pos = stripos($listQuery, ' where ');
			$listQuery .= ($pos !== false) ? ' AND 1=0' : ' WHERE 1=0';
			return $listQuery;
		}

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
			WHERE fto.object_id = vtiger_account.accountid
			  AND fto.module = 'Accounts'
			  AND ft.tag IN ({$inList})
		) ";

		$pos = stripos($listQuery, ' where ');
		if ($pos !== false) {
			$listQuery .= " AND {$exists}";
		} else {
			$listQuery .= " WHERE {$exists}";
		}

		error_log('[TagACL][Accounts] getQuery() userId=' . $currentUser->getId() .
			' role=' . $roleName .
			' mappedTags=' . json_encode($tags, JSON_UNESCAPED_UNICODE) .
			' isAdmin=' . $isAdmin .
			' isPrivileged=' . ($isPriv ? '1' : '0') .
			' sql=' . $listQuery);

		return $listQuery;
	}

	/**
	 * Paginate by distinct accountid first, then load full rows for those ids.
	 * Avoids LIMIT on joined duplicate rows (list repeating / missing orgs vs export).
	 */
	public function getListViewEntries($pagingModel) {
		$db = PearDatabase::getInstance();

		$moduleName = $this->getModule()->get('name');
		$moduleFocus = CRMEntity::getInstance($moduleName);
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$queryGenerator = $this->get('query_generator');
		$listViewContoller = $this->get('listview_controller');

		$searchParams = $this->get('search_params');
		if (empty($searchParams)) {
			$searchParams = array();
		}
		$glue = '';
		if (php7_count($queryGenerator->getWhereFields()) > 0 && (php7_count($searchParams)) > 0) {
			$glue = QueryGenerator::$AND;
		}
		$queryGenerator->parseAdvFilterList($searchParams, $glue);

		$searchKey = $this->get('search_key');
		$searchValue = $this->get('search_value');
		$operator = $this->get('operator');
		if (!empty($searchKey)) {
			$queryGenerator->addUserSearchConditions(array(
				'search_field' => $searchKey,
				'search_text' => $searchValue,
				'operator' => $operator
			));
		}

		$orderBy = $this->getForSql('orderby');
		$sortOrder = $this->getForSql('sortorder');
		$orderByFieldModel = null;

		if (!empty($orderBy)) {
			$queryGenerator = $this->get('query_generator');
			$fieldModels = $queryGenerator->getModuleFields();
			$orderByFieldModel = $fieldModels[$orderBy];
			if ($orderByFieldModel && ($orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::REFERENCE_TYPE ||
					$orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::OWNER_TYPE)) {
				$queryGenerator->addWhereField($orderBy);
			}
		}

		$listQuery = $this->getQuery();

		$sourceModule = $this->get('src_module');
		if (!empty($sourceModule)) {
			if (method_exists($moduleModel, 'getQueryByModuleField')) {
				$overrideQuery = $moduleModel->getQueryByModuleField(
					$sourceModule,
					$this->get('src_field'),
					$this->get('src_record'),
					$listQuery,
					$this->get('relationId')
				);
				if (!empty($overrideQuery)) {
					$listQuery = $overrideQuery;
				}
			}
		}

		$orderClause = '';
		if (!empty($orderBy) && $orderByFieldModel) {
			if ($orderBy == 'roleid' && $moduleName == 'Users') {
				$orderClause = ' ORDER BY vtiger_role.rolename ' . $sortOrder;
			} else {
				$orderClause = ' ORDER BY ' . $queryGenerator->getOrderByColumn($orderBy) . ' ' . $sortOrder;
			}
			if ($orderBy == 'first_name' && $moduleName == 'Users') {
				$orderClause .= ' , last_name ' . $sortOrder . ' ,  email1 ' . $sortOrder;
			}
		} elseif (empty($orderBy) && empty($sortOrder) && $moduleName != 'Users') {
			$orderClause = ' ORDER BY vtiger_crmentity.modifiedtime DESC';
		}

		$startIndex = $pagingModel->getStartIndex();
		$pageLimit = $pagingModel->getPageLimit();

		$viewid = ListViewSession::getCurrentView($moduleName);
		if (empty($viewid)) {
			$viewid = $pagingModel->get('viewid');
		}
		$_SESSION['lvs'][$moduleName][$viewid]['start'] = $pagingModel->get('page');

		$accountIds = $this->getDistinctPagedAccountIds($listQuery, $orderClause, $startIndex, $pageLimit + 1);
		if (empty($accountIds)) {
			$pagingModel->set('nextPageExists', false);
			ListViewSession::setSessionQuery($moduleName, $listQuery . $orderClause, $viewid);
			return array();
		}

		$hasNext = php7_count($accountIds) > $pageLimit;
		if ($hasNext) {
			array_pop($accountIds);
		}
		$pagingModel->set('nextPageExists', $hasNext);

		$idList = implode(',', array_map('intval', $accountIds));
		$idFilter = ' vtiger_account.accountid IN (' . $idList . ') ';
		if (stripos($listQuery, ' where ') !== false) {
			$listQuery .= ' AND ' . $idFilter;
		} else {
			$listQuery .= ' WHERE ' . $idFilter;
		}
		$listQuery .= $orderClause;

		ListViewSession::setSessionQuery($moduleName, $listQuery, $viewid);

		$listResult = $db->pquery($listQuery, array());
		$listViewEntries = $listViewContoller->getListViewRecords($moduleFocus, $moduleName, $listResult);

		$listViewRecordModels = array();
		$rawById = array();
		$rows = $db->num_rows($listResult);
		for ($i = 0; $i < $rows; $i++) {
			$rawData = $db->query_result_rowdata($listResult, $i);
			$recordId = isset($rawData['accountid']) ? (int) $rawData['accountid'] : 0;
			if ($recordId <= 0 && isset($rawData['crmid'])) {
				$recordId = (int) $rawData['crmid'];
			}
			if ($recordId > 0 && !isset($rawById[$recordId])) {
				$rawById[$recordId] = $rawData;
			}
		}

		foreach ($accountIds as $recordId) {
			$recordId = (int) $recordId;
			if (!isset($listViewEntries[$recordId]) || !isset($rawById[$recordId])) {
				continue;
			}
			$record = $listViewEntries[$recordId];
			$record['id'] = $recordId;
			$listViewRecordModels[$recordId] = $moduleModel->getRecordFromArray($record, $rawById[$recordId]);
		}

		$pagingModel->calculatePageRange($listViewRecordModels);
		return $listViewRecordModels;
	}

	/**
	 * @return int[]
	 */
	private function getDistinctPagedAccountIds($listQuery, $orderClause, $startIndex, $limit) {
		$db = PearDatabase::getInstance();
		$fromPos = stripos($listQuery, ' from ');
		if ($fromPos === false) {
			return array();
		}
		$fromAndWhere = substr($listQuery, $fromPos);
		$idSql = 'SELECT DISTINCT vtiger_account.accountid' . $fromAndWhere . $orderClause
			. ' LIMIT ' . (int) $startIndex . ',' . (int) $limit;
		$idResult = $db->pquery($idSql, array());
		$ids = array();
		$rows = $db->num_rows($idResult);
		for ($i = 0; $i < $rows; $i++) {
			$ids[] = (int) $db->query_result($idResult, $i, 'accountid');
		}
		return $ids;
	}
}