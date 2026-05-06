<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Potentials_RelationListView_Model extends Vtiger_RelationListView_Model {

	public function getCreateViewUrl() {
		$createViewUrl = parent::getCreateViewUrl();
		$relationModel = $this->getRelationModel();
		$relatedModuleModel = $relationModel->getRelationModuleModel();
		$relatedModuleName = $relatedModuleModel->getName();

		if (in_array($relatedModuleName, array('Quotes', 'SalesOrder'))) {
			$parentRecordModel = $this->getParentRecordModel();
			$createViewUrl .= '&account_id='.$parentRecordModel->get('related_to').'&contact_id='.$parentRecordModel->get('contact_id');
		}
		return $createViewUrl;
	}

	/**
	 * Potentials → Contacts related list:
	 * ensure "Organisation/Organization Name" (Contacts.account_id) inline filter searches by
	 * Accounts.accountname (display name), not by raw account id.
	 *
	 * Scope: only when related module is Contacts and whereCondition contains account_id.
	 */
	public function getEntries($pagingModel) {
		$relationModule = $this->getRelationModel()->getRelationModuleModel();
		$relationModuleName = $relationModule->get('name');

		$whereCondition = $this->get('whereCondition');

		if ($relationModuleName !== 'Contacts' || !is_array($whereCondition) || empty($whereCondition)) {
			return parent::getEntries($pagingModel);
		}

		// Only handle Organization/Organisation Name filter (Contacts.account_id) here.
		$accountComparator = null;
		$accountSearchValue = null;
		$remainingWhereCondition = $whereCondition;
		foreach ($whereCondition as $fieldName => $fieldValue) {
			if ($fieldName === 'account_id' && is_array($fieldValue)) {
				$accountComparator = isset($fieldValue[1]) ? $fieldValue[1] : null;
				$accountSearchValue = isset($fieldValue[2]) ? $fieldValue[2] : null;
				unset($remainingWhereCondition[$fieldName]);
				break;
			}
		}

		if ($accountSearchValue === null || $accountSearchValue === '') {
			// No Organization Name filter -> use standard behavior.
			return parent::getEntries($pagingModel);
		}

		// Copy of parent::getEntries() whereCondition block, with Contacts.account_id handled via manual join to vtiger_account.
		$db = PearDatabase::getInstance();

		$relatedColumnFields = $relationModule->getConfigureRelatedListFields();
		if (php7_count($relatedColumnFields) <= 0) {
			$relatedColumnFields = $relationModule->getRelatedListFields();
		}

		$query = $this->getRelationQuery();

		// Ensure vtiger_account join exists for filtering by accountname.
		// Relation query for Contacts includes vtiger_contactdetails, so this join is safe.
		$queryParts = preg_split('/ WHERE /i', $query, 2);
		if (php7_count($queryParts) == 2) {
			$fromPart = $queryParts[0];
			$wherePart = $queryParts[1];
			if (stripos($fromPart, 'vtiger_account') === false) {
				$fromPart .= ' LEFT JOIN vtiger_account ON vtiger_contactdetails.accountid = vtiger_account.accountid ';
			}
			$query = $fromPart . ' WHERE ' . $wherePart;
		}

		$currentUser = Users_Record_Model::getCurrentUserModel();
		$queryGenerator = new EnhancedQueryGenerator($relationModuleName, $currentUser);
		$queryGenerator->setFields(array_values($relatedColumnFields));

		foreach ($remainingWhereCondition as $fieldName => $fieldValue) {
			if (!is_array($fieldValue)) continue;
			$comparator = $fieldValue[1];
			$searchValue = $fieldValue[2];
			$type = isset($fieldValue[3]) ? $fieldValue[3] : '';
			if ($type === 'time') {
				$searchValue = Vtiger_Time_UIType::getTimeValueWithSeconds($searchValue);
			}

			$queryGenerator->addCondition($fieldName, $searchValue, $comparator, "AND");
		}

		$whereQuerySplit = explode("WHERE", $queryGenerator->getWhereClause());
		$query .= " AND " . $whereQuerySplit[1];

		// Apply Organization Name filter directly on Accounts.accountname.
		// Comparator 'c' (contains) should behave as LIKE %value%.
		$params = array();
		$accountComparator = $accountComparator ? strtolower($accountComparator) : 'c';
		if ($accountComparator === 'e') {
			$query .= ' AND vtiger_account.accountname = ?';
			$params[] = $accountSearchValue;
		} else {
			$query .= ' AND vtiger_account.accountname LIKE ?';
			$params[] = '%' . $accountSearchValue . '%';
		}

		$startIndex = $pagingModel->getStartIndex();
		$pageLimit = $pagingModel->getPageLimit();

		$orderBy = $this->getForSql('orderby');
		$sortOrder = $this->getForSql('sortorder');

		// Keep parent ordering logic by delegating to parent when no explicit sort is set.
		if ($orderBy) {
			$orderByFieldModuleModel = $relationModule->getFieldByColumn($orderBy);
			if ($orderByFieldModuleModel && $orderByFieldModuleModel->isReferenceField()) {
				$queryComponents = $split = preg_split('/ where /i', $query);
				$selectAndFromClause = $queryComponents[0];
				$whereConditionSql = $queryComponents[1];
				$qualifiedOrderBy = 'vtiger_crmentity' . $orderByFieldModuleModel->get('column');
				$selectAndFromClause .= ' LEFT JOIN vtiger_crmentity AS ' . $qualifiedOrderBy . ' ON ' .
					$orderByFieldModuleModel->get('table') . '.' . $orderByFieldModuleModel->get('column') . ' = ' .
					$qualifiedOrderBy . '.crmid ';
				$query = $selectAndFromClause . ' WHERE ' . $whereConditionSql;
				$query .= ' ORDER BY ' . $qualifiedOrderBy . '.label ' . $sortOrder;
			} elseif ($orderByFieldModuleModel && $orderByFieldModuleModel->isOwnerField()) {
				$query .= ' ORDER BY COALESCE(vtiger_users.userlabel,vtiger_groups.groupname) ' . $sortOrder;
			} else {
				$qualifiedOrderBy = $orderBy;
				$orderByField = $relationModule->getFieldByColumn($orderBy);
				if ($orderByField) {
					$qualifiedOrderBy = $relationModule->getOrderBySql($qualifiedOrderBy);
				}
				$query = "$query ORDER BY $qualifiedOrderBy $sortOrder";
			}
		} else if (empty($orderBy) && empty($sortOrder) && $relationModuleName != "Users") {
			$query .= ' ORDER BY vtiger_crmentity.modifiedtime DESC';
		}

		$limitQuery = $query . ' LIMIT ' . $startIndex . ',' . $pageLimit;
		$result = $db->pquery($limitQuery, $params);
		$relatedRecordList = array();

		for ($i = 0; $i < $db->num_rows($result); $i++) {
			$row = $db->fetch_row($result, $i);
			$newRow = array();
			foreach ($row as $col => $val) {
				if (array_key_exists($col, $relatedColumnFields)) {
					$newRow[$relatedColumnFields[$col]] = $val;
				}
			}
			$record = Vtiger_Record_Model::getCleanInstance($relationModule->get('name'));
			$record->setData($newRow)->setModuleFromInstance($relationModule)->setRawData($row);
			$record->setId($row['crmid']);
			$relatedRecordList[$row['crmid']] = $record;
		}

		$pagingModel->calculatePageRange($relatedRecordList);
		$nextLimitQuery = $query . ' LIMIT ' . ($startIndex + $pageLimit) . ' , 1';
		$nextPageLimitResult = $db->pquery($nextLimitQuery, array());
		$pagingModel->set('nextPageExists', ($db->num_rows($nextPageLimitResult) > 0));
		$pagingModel->set('_relatedlistcount', php7_count($relatedRecordList));

		return $relatedRecordList;
	}

}
?>
