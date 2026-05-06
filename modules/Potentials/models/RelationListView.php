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
	 * Append AND condition into the SQL main predicate, before GROUP BY / ORDER BY if present.
	 * Avoids appending after GROUP BY/ORDER BY (invalid SQL).
	 *
	 * @param string $query
	 * @param string $conditionSql SQL fragment without leading AND (e.g. "DATE(vtiger_activity.due_date) = ?")
	 * @return string
	 */
	private function appendConditionBeforeGroupOrder($query, $conditionSql) {
		$conditionSql = trim((string) $conditionSql);
		if ($conditionSql === '') {
			return $query;
		}
		$snippet = ' AND ' . $conditionSql;
		$bestPos = null;
		if (preg_match('/\bGROUP BY\b/i', $query, $m, PREG_OFFSET_CAPTURE)) {
			$bestPos = $m[0][1];
		}
		if (preg_match('/\bORDER BY\b/i', $query, $m, PREG_OFFSET_CAPTURE)) {
			$p = $m[0][1];
			if ($bestPos === null || $p < $bestPos) {
				$bestPos = $p;
			}
		}
		if ($bestPos !== null) {
			return substr($query, 0, $bestPos) . $snippet . substr($query, $bestPos);
		}
		return $query . $snippet;
	}

	/**
	 * Non-empty fragment after WHERE from EnhancedQueryGenerator::getWhereClause(), or ''.
	 *
	 * @param object $queryGenerator EnhancedQueryGenerator instance
	 * @return string
	 */
	private function extractQueryGeneratorWhereFragment($queryGenerator) {
		$full = $queryGenerator->getWhereClause();
		if ($full === '' || stripos($full, 'WHERE') === false) {
			return '';
		}
		$parts = explode('WHERE', $full, 2);
		if (!isset($parts[1])) {
			return '';
		}
		return trim($parts[1]);
	}

	/**
	 * Normalize Calendar related-list date_start / due_date filter value to YYYY-MM-DD.
	 * Accepts user date_format (e.g. MM-DD-YYYY), YYYY-MM-DD, comma ranges (first day), datetime prefixes.
	 *
	 * @param string $rawValue
	 * @return string|null YYYY-MM-DD, or null if value cannot be parsed
	 */
	private function potentialsNormalizeCalendarDateFilterToYmd($rawValue) {
		$rawValue = trim((string) $rawValue);
		if ($rawValue === '') {
			return null;
		}
		$first = $rawValue;
		if (strpos($first, ',') !== false) {
			$first = trim(explode(',', $first, 2)[0]);
		}
		if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $first, $m)) {
			return $m[1];
		}
		$user = Users_Record_Model::getCurrentUserModel();
		$vf = strtolower((string) $user->get('date_format'));
		if ($vf === '') {
			$vf = 'dd-mm-yyyy';
		}
		$phpFmt = str_replace(array('yyyy', 'yy', 'mm', 'dd'), array('Y', 'y', 'm', 'd'), $vf);
		$dt = DateTime::createFromFormat($phpFmt, $first);
		if ($dt instanceof DateTime) {
			$errors = DateTime::getLastErrors();
			if ($errors === false || (empty($errors['warning_count']) && empty($errors['error_count']))) {
				return $dt->format('Y-m-d');
			}
		}
		$ts = strtotime($first);
		if ($ts !== false) {
			return date('Y-m-d', $ts);
		}
		return null;
	}

	/**
	 * Run parent getEntries with a temporary whereCondition (restores after).
	 *
	 * @param Vtiger_Paging_Model $pagingModel
	 * @param array $whereCondition
	 * @return array
	 */
	private function getEntriesWithTemporaryWhereCondition($pagingModel, $whereCondition) {
		$saved = $this->get('whereCondition');
		$this->set('whereCondition', $whereCondition);
		$result = parent::getEntries($pagingModel);
		$this->set('whereCondition', $saved);
		return $result;
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

		// Potentials → Calendar: normalize due_date to core QueryGenerator-friendly same-day range (no custom SQL path).
		if ($relationModuleName === 'Calendar' && is_array($whereCondition) && isset($whereCondition['due_date']) && is_array($whereCondition['due_date'])) {
			$wc = $whereCondition;
			$raw = isset($wc['due_date'][2]) ? $wc['due_date'][2] : '';
			$raw = is_string($raw) ? trim($raw) : '';
			if ($raw === '') {
				unset($wc['due_date']);
				return $this->getEntriesWithTemporaryWhereCondition($pagingModel, $wc);
			}
			$ymd = $this->potentialsNormalizeCalendarDateFilterToYmd($raw);
			if ($ymd === null || $ymd === '') {
				unset($wc['due_date']);
				return $this->getEntriesWithTemporaryWhereCondition($pagingModel, $wc);
			}
			$wc['due_date'][1] = 'bw';
			$dueType = isset($wc['due_date'][3]) ? $wc['due_date'][3] : '';
			if ($dueType === 'datetime') {
				$wc['due_date'][2] = $ymd . ' 00:00:00,' . $ymd . ' 23:59:59';
			} else {
				$wc['due_date'][2] = $ymd . ',' . $ymd;
			}
			return $this->getEntriesWithTemporaryWhereCondition($pagingModel, $wc);
		}

		// Potentials → Quotes related list:
		// Reference-display filters:
		// - contact_id: match by quote_contact firstname/lastname (LIKE)
		// - account_id: match by vtiger_account.accountname (LIKE)
		// - potential_id: match by vtiger_potential.potentialname (LIKE)
		// We remove those keys from normal QueryGenerator conditions to avoid conflicting reference behavior.
		if ($relationModuleName === 'Quotes' && is_array($whereCondition) && !empty($whereCondition)) {
			$customKeys = array('contact_id', 'account_id', 'potential_id');
			$remainingWhereCondition = $whereCondition;

			$contactSearch = '';
			$accountSearch = '';
			$potentialSearch = '';

			if (isset($whereCondition['contact_id']) && is_array($whereCondition['contact_id'])) {
				$contactSearch = isset($whereCondition['contact_id'][2]) ? trim((string)$whereCondition['contact_id'][2]) : '';
				unset($remainingWhereCondition['contact_id']);
			}
			if (isset($whereCondition['account_id']) && is_array($whereCondition['account_id'])) {
				$accountSearch = isset($whereCondition['account_id'][2]) ? trim((string)$whereCondition['account_id'][2]) : '';
				unset($remainingWhereCondition['account_id']);
			}
			if (isset($whereCondition['potential_id']) && is_array($whereCondition['potential_id'])) {
				$potentialSearch = isset($whereCondition['potential_id'][2]) ? trim((string)$whereCondition['potential_id'][2]) : '';
				unset($remainingWhereCondition['potential_id']);
			}

			$hasAnyValue = ($contactSearch !== '' || $accountSearch !== '' || $potentialSearch !== '');
			if (!$hasAnyValue) {
				// If no real search value, don't apply custom SQL; keep default parent behavior for other filters.
				return $this->getEntriesWithTemporaryWhereCondition($pagingModel, $remainingWhereCondition);
			}

			$db = PearDatabase::getInstance();
			$relatedColumnFields = $relationModule->getConfigureRelatedListFields();
			if (php7_count($relatedColumnFields) <= 0) {
				$relatedColumnFields = $relationModule->getRelatedListFields();
			}

			$query = $this->getRelationQuery();
			$params = array();

			// Helper: inject LEFT JOIN before first WHERE.
			$injectJoinBeforeWhere = function($query, $joinSql, $needle) {
				$needle = (string)$needle;
				if ($needle !== '' && stripos($query, $needle) !== false) {
					return $query;
				}
				$pos = stripos($query, ' WHERE ');
				if ($pos !== false) {
					return substr($query, 0, $pos) . ' ' . $joinSql . ' ' . substr($query, $pos);
				}
				return $query . ' ' . $joinSql;
			};

			// Inject joins + WHERE predicates for reference display-name filters.
			if ($contactSearch !== '') {
				$query = $injectJoinBeforeWhere(
					$query,
					'LEFT JOIN vtiger_contactdetails AS quote_contact ON vtiger_quotes.contactid = quote_contact.contactid',
					'quote_contact'
				);
				$likeVal = '%' . $contactSearch . '%';
				$conditionSql = "(quote_contact.firstname LIKE ? OR quote_contact.lastname LIKE ? OR CONCAT(TRIM(quote_contact.firstname), ' ', TRIM(quote_contact.lastname)) LIKE ?)";
				$query = $this->appendConditionBeforeGroupOrder($query, $conditionSql);
				$params[] = $likeVal;
				$params[] = $likeVal;
				$params[] = $likeVal;
			}
			if ($accountSearch !== '') {
				$query = $injectJoinBeforeWhere(
					$query,
					'LEFT JOIN vtiger_account AS quote_account ON vtiger_quotes.accountid = quote_account.accountid',
					'quote_account'
				);
				$likeVal = '%' . $accountSearch . '%';
				$conditionSql = "quote_account.accountname LIKE ?";
				$query = $this->appendConditionBeforeGroupOrder($query, $conditionSql);
				$params[] = $likeVal;
			}
			if ($potentialSearch !== '') {
				$query = $injectJoinBeforeWhere(
					$query,
					'LEFT JOIN vtiger_potential AS quote_potential ON vtiger_quotes.potentialid = quote_potential.potentialid',
					'quote_potential'
				);
				$likeVal = '%' . $potentialSearch . '%';
				$conditionSql = "quote_potential.potentialname LIKE ?";
				$query = $this->appendConditionBeforeGroupOrder($query, $conditionSql);
				$params[] = $likeVal;
			}

			// Add remaining normal filters using QueryGenerator (excluding custom keys).
			if (!empty($remainingWhereCondition)) {
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
				$fragment = $this->extractQueryGeneratorWhereFragment($queryGenerator);
				if ($fragment !== '') {
					$query = $this->appendConditionBeforeGroupOrder($query, $fragment);
				}
			}

			// Order/paging (keep same structure as parent).
			$startIndex = $pagingModel->getStartIndex();
			$pageLimit = $pagingModel->getPageLimit();
			$orderBy = $this->getForSql('orderby');
			$sortOrder = $this->getForSql('sortorder');

			if ($orderBy) {
				$orderByFieldModuleModel = $relationModule->getFieldByColumn($orderBy);
				if ($orderByFieldModuleModel && $orderByFieldModuleModel->isReferenceField()) {
					$queryComponents = preg_split('/ where /i', $query);
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
			} elseif (empty($orderBy) && empty($sortOrder) && $relationModuleName != "Users") {
				$query .= ' ORDER BY vtiger_crmentity.modifiedtime DESC';
			}

			$limitQuery = $query . ' LIMIT ' . $startIndex . ',' . $pageLimit;
			try {
				$result = $db->pquery($limitQuery, $params);
			} catch (Exception $e) {
				// Never blank the related list: fallback to parent query if our custom SQL fails.
				return parent::getEntries($pagingModel);
			}
			if ($result === false || $result === null) {
				return parent::getEntries($pagingModel);
			}

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
			$nextPageLimitResult = $db->pquery($nextLimitQuery, $params);
			if ($db->num_rows($nextPageLimitResult) > 0) {
				$pagingModel->set('nextPageExists', true);
			} else {
				$pagingModel->set('nextPageExists', false);
			}
			$pagingModel->set('_relatedlistcount', php7_count($relatedRecordList));

			return $relatedRecordList;
		}

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

		$params = array();

		if (!empty($remainingWhereCondition)) {
			$currentUser = Users_Record_Model::getCurrentUserModel();
			$queryGenerator = new EnhancedQueryGenerator($relationModuleName, $currentUser);
			$queryGenerator->setFields(array_values($relatedColumnFields));

			foreach ($remainingWhereCondition as $fieldName => $fieldValue) {
				if (!is_array($fieldValue)) {
					continue;
				}
				$comparator = $fieldValue[1];
				$searchValue = $fieldValue[2];
				$type = isset($fieldValue[3]) ? $fieldValue[3] : '';
				if ($type === 'time') {
					$searchValue = Vtiger_Time_UIType::getTimeValueWithSeconds($searchValue);
				}

				$queryGenerator->addCondition($fieldName, $searchValue, $comparator, "AND");
			}

			$fragment = $this->extractQueryGeneratorWhereFragment($queryGenerator);
			if ($fragment !== '') {
				$query = $this->appendConditionBeforeGroupOrder($query, $fragment);
			}
		}

		// Organization Name filter on Accounts.accountname (not raw account_id).
		$accountComparator = $accountComparator ? strtolower($accountComparator) : 'c';
		if ($accountComparator === 'e') {
			$query = $this->appendConditionBeforeGroupOrder($query, 'vtiger_account.accountname = ?');
			$params[] = $accountSearchValue;
		} else {
			$query = $this->appendConditionBeforeGroupOrder($query, 'vtiger_account.accountname LIKE ?');
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
		$nextPageLimitResult = $db->pquery($nextLimitQuery, $params);
		$pagingModel->set('nextPageExists', ($db->num_rows($nextPageLimitResult) > 0));
		$pagingModel->set('_relatedlistcount', php7_count($relatedRecordList));

		return $relatedRecordList;
	}

}
?>
