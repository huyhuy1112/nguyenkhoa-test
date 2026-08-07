<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Quotes_ListView_Model extends Inventory_ListView_Model {

	public function getListViewEntries($pagingModel) {
		$listViewRecordModels = parent::getListViewEntries($pagingModel);
		if (empty($listViewRecordModels)) {
			return $listViewRecordModels;
		}

		foreach ($listViewRecordModels as $recordId => $recordModel) {
			$corrected = $this->resolveDisplayGrandTotal($recordModel);
			if ($corrected === null) {
				continue;
			}
			$formatted = CurrencyField::convertToUserFormat($corrected, null, true);
			$recordModel->set('hdnGrandTotal', $formatted);
			$recordModel->set('total', $formatted);
			$listViewRecordModels[$recordId] = $recordModel;
		}

		require_once 'modules/Vtiger/helpers/MkSalesCustomerName.php';
		foreach ($listViewRecordModels as $recordId => $recordModel) {
			$listViewRecordModels[$recordId] = Vtiger_MkSalesCustomerName_Helper::applyListCustomerColumn($recordModel);
		}

		$this->applyListPhoneEmailColumns($listViewRecordModels);

		return $listViewRecordModels;
	}

	/**
	 * Batch-load SĐT / Email for SALES list (mk_list_phone, mk_list_email).
	 * Order: mk_customer_* on quote → contact mobile/phone/email → account phone/email1.
	 *
	 * @param array $listViewRecordModels
	 */
	protected function applyListPhoneEmailColumns(array &$listViewRecordModels) {
		$ids = array();
		foreach (array_keys($listViewRecordModels) as $id) {
			$id = (int) $id;
			if ($id > 0) {
				$ids[] = $id;
			}
		}
		$ids = array_values(array_unique($ids));
		if (empty($ids)) {
			return;
		}

		$db = PearDatabase::getInstance();
		$placeholders = generateQuestionMarks($ids);

		// Detect optional BA columns on quotes.
		$hasMkPhone = $this->quotesTableHasColumn($db, 'mk_customer_phone');
		$hasMkEmail = $this->quotesTableHasColumn($db, 'mk_customer_email');

		$selectExtra = '';
		if ($hasMkPhone) {
			$selectExtra .= ', q.mk_customer_phone';
		}
		if ($hasMkEmail) {
			$selectExtra .= ', q.mk_customer_email';
		}

		$quoteMeta = array();
		$contactIds = array();
		$accountIds = array();
		$potentialIds = array();

		$rs = $db->pquery(
			"SELECT q.quoteid, q.contactid, q.accountid, q.potentialid {$selectExtra}
			 FROM vtiger_quotes q
			 WHERE q.quoteid IN ({$placeholders})",
			$ids
		);
		if ($rs) {
			while ($row = $db->fetchByAssoc($rs)) {
				$qid = (int) (isset($row['quoteid']) ? $row['quoteid'] : 0);
				if ($qid <= 0) {
					continue;
				}
				$contactId = (int) (isset($row['contactid']) ? $row['contactid'] : 0);
				$accountId = (int) (isset($row['accountid']) ? $row['accountid'] : 0);
				$potentialId = (int) (isset($row['potentialid']) ? $row['potentialid'] : 0);
				$mkPhone = $hasMkPhone ? trim(decode_html((string) (isset($row['mk_customer_phone']) ? $row['mk_customer_phone'] : ''))) : '';
				$mkEmail = $hasMkEmail ? trim(decode_html((string) (isset($row['mk_customer_email']) ? $row['mk_customer_email'] : ''))) : '';

				// Prefer contact id already resolved onto the list model by customer-name helper.
				$model = isset($listViewRecordModels[$qid]) ? $listViewRecordModels[$qid] : null;
				if ($model) {
					$modelContact = (int) $model->get('contact_id');
					if ($modelContact > 0) {
						$contactId = $modelContact;
					}
				}

				$quoteMeta[$qid] = array(
					'contact_id' => $contactId,
					'account_id' => $accountId,
					'potential_id' => $potentialId,
					'mk_phone' => $mkPhone,
					'mk_email' => $mkEmail,
				);
				if ($contactId > 0) {
					$contactIds[] = $contactId;
				}
				if ($accountId > 0) {
					$accountIds[] = $accountId;
				}
				if ($potentialId > 0 && $contactId <= 0) {
					$potentialIds[] = $potentialId;
				}
			}
		}

		// Resolve contacts from potentials when quote has no contactid.
		$potentialToContact = array();
		$potentialIds = array_values(array_unique($potentialIds));
		if (!empty($potentialIds)) {
			$prs = $db->pquery(
				'SELECT potentialid, contact_id, related_to FROM vtiger_potential WHERE potentialid IN (' . generateQuestionMarks($potentialIds) . ')',
				$potentialIds
			);
			if ($prs) {
				while ($prow = $db->fetchByAssoc($prs)) {
					$pid = (int) (isset($prow['potentialid']) ? $prow['potentialid'] : 0);
					$cid = (int) (isset($prow['contact_id']) ? $prow['contact_id'] : 0);
					$aid = (int) (isset($prow['related_to']) ? $prow['related_to'] : 0);
					if ($pid > 0) {
						$potentialToContact[$pid] = array('contact_id' => $cid, 'account_id' => $aid);
						if ($cid > 0) {
							$contactIds[] = $cid;
						}
						if ($aid > 0) {
							$accountIds[] = $aid;
						}
					}
				}
			}
			foreach ($quoteMeta as $qid => $meta) {
				if ($meta['contact_id'] <= 0 && $meta['potential_id'] > 0 && isset($potentialToContact[$meta['potential_id']])) {
					$resolved = $potentialToContact[$meta['potential_id']];
					if ((int) $resolved['contact_id'] > 0) {
						$quoteMeta[$qid]['contact_id'] = (int) $resolved['contact_id'];
					}
					if ($meta['account_id'] <= 0 && (int) $resolved['account_id'] > 0) {
						$quoteMeta[$qid]['account_id'] = (int) $resolved['account_id'];
					}
				}
			}
		}

		$contactIds = array_values(array_unique(array_filter($contactIds)));
		$accountIds = array_values(array_unique(array_filter($accountIds)));

		$contactMap = array();
		if (!empty($contactIds)) {
			$crs = $db->pquery(
				'SELECT contactid, mobile, phone, email FROM vtiger_contactdetails WHERE contactid IN (' . generateQuestionMarks($contactIds) . ')',
				$contactIds
			);
			if ($crs) {
				while ($crow = $db->fetchByAssoc($crs)) {
					$cid = (int) (isset($crow['contactid']) ? $crow['contactid'] : 0);
					if ($cid <= 0) {
						continue;
					}
					$mobile = trim(decode_html((string) (isset($crow['mobile']) ? $crow['mobile'] : '')));
					$phone = trim(decode_html((string) (isset($crow['phone']) ? $crow['phone'] : '')));
					$email = trim(decode_html((string) (isset($crow['email']) ? $crow['email'] : '')));
					$contactMap[$cid] = array(
						'phone' => $mobile !== '' ? $mobile : $phone,
						'email' => $email,
					);
				}
			}
		}

		$accountMap = array();
		if (!empty($accountIds)) {
			$ars = $db->pquery(
				'SELECT accountid, phone, email1, email2 FROM vtiger_account WHERE accountid IN (' . generateQuestionMarks($accountIds) . ')',
				$accountIds
			);
			if ($ars) {
				while ($arow = $db->fetchByAssoc($ars)) {
					$aid = (int) (isset($arow['accountid']) ? $arow['accountid'] : 0);
					if ($aid <= 0) {
						continue;
					}
					$phone = trim(decode_html((string) (isset($arow['phone']) ? $arow['phone'] : '')));
					$email1 = trim(decode_html((string) (isset($arow['email1']) ? $arow['email1'] : '')));
					$email2 = trim(decode_html((string) (isset($arow['email2']) ? $arow['email2'] : '')));
					$accountMap[$aid] = array(
						'phone' => $phone,
						'email' => $email1 !== '' ? $email1 : $email2,
					);
				}
			}
		}

		foreach ($listViewRecordModels as $recordId => $recordModel) {
			$qid = (int) $recordId;
			$meta = isset($quoteMeta[$qid]) ? $quoteMeta[$qid] : array(
				'contact_id' => 0,
				'account_id' => 0,
				'potential_id' => 0,
				'mk_phone' => '',
				'mk_email' => '',
			);

			$phone = $meta['mk_phone'];
			$email = $meta['mk_email'];

			$cid = (int) $meta['contact_id'];
			$aid = (int) $meta['account_id'];

			if ($phone === '' && $cid > 0 && isset($contactMap[$cid])) {
				$phone = $contactMap[$cid]['phone'];
			}
			if ($email === '' && $cid > 0 && isset($contactMap[$cid])) {
				$email = $contactMap[$cid]['email'];
			}
			if ($phone === '' && $aid > 0 && isset($accountMap[$aid])) {
				$phone = $accountMap[$aid]['phone'];
			}
			if ($email === '' && $aid > 0 && isset($accountMap[$aid])) {
				$email = $accountMap[$aid]['email'];
			}

			require_once 'modules/Vtiger/helpers/MkPhoneFormat.php';
			if ($phone !== '') {
				$phone = Vtiger_MkPhoneFormat_Helper::formatDisplay($phone);
			}

			$recordModel->set('mk_list_phone', $phone !== '' ? $phone : '—');
			$recordModel->set('mk_list_email', $email !== '' ? $email : '—');
			$listViewRecordModels[$recordId] = $recordModel;
		}
	}

	/**
	 * @param PearDatabase $db
	 * @param string $column
	 * @return bool
	 */
	protected function quotesTableHasColumn($db, $column) {
		static $cache = array();
		$column = (string) $column;
		if (isset($cache[$column])) {
			return $cache[$column];
		}
		$cache[$column] = false;
		try {
			$rs = $db->pquery('SHOW COLUMNS FROM vtiger_quotes LIKE ?', array($column));
			$cache[$column] = ($rs && $db->num_rows($rs) > 0);
		} catch (Exception $e) {
			$cache[$column] = false;
		}
		return $cache[$column];
	}

	/**
	 * When quote header total is out of scale with line items, scale by line/header subtotal.
	 */
	protected function resolveDisplayGrandTotal(Vtiger_Record_Model $recordModel) {
		$recordId = (int) $recordModel->getId();
		if ($recordId <= 0) {
			return null;
		}

		$db = PearDatabase::getInstance();
		$headerResult = $db->pquery(
			'SELECT subtotal, total FROM vtiger_quotes WHERE quoteid = ?',
			array($recordId)
		);
		if (!$headerResult || $db->num_rows($headerResult) === 0) {
			return null;
		}
		$headerSubTotal = (float) $db->query_result($headerResult, 0, 'subtotal');
		$headerTotal = (float) $db->query_result($headerResult, 0, 'total');

		$lineResult = $db->pquery(
			'SELECT COALESCE(SUM(quantity * listprice), 0) AS line_subtotal FROM vtiger_inventoryproductrel WHERE id = ?',
			array($recordId)
		);
		$lineSubTotal = (float) $db->query_result($lineResult, 0, 'line_subtotal');
		if ($lineSubTotal <= 0) {
			return null;
		}
		if ($headerSubTotal <= 0) {
			return $lineSubTotal;
		}
		if ($lineSubTotal > ($headerSubTotal * 50)) {
			// Prefer header when lines look absurd (corrupted duplicate), never amplify.
			if ($headerTotal > 0) {
				return $headerTotal;
			}
			return $headerSubTotal;
		}
		if ($headerTotal > 0 && $headerTotal < ($lineSubTotal * 0.5)) {
			return $lineSubTotal;
		}

		return null;
	}

	/**
	 * List scope: all | franchise (Báo giá khách hàng nhượng quyền).
	 */
	public function getQuery() {
		$listQuery = parent::getQuery();
		$scope = strtolower(trim((string) $this->get('mk_quote_scope')));
		if ($scope === 'franchise' || $scope === 'nhuong_quyen') {
			$listQuery = $this->appendFranchiseQuoteFilter($listQuery);
		}
		return $listQuery;
	}

	/**
	 * Only quotes created from module Khách hàng nhượng quyền (ServiceContracts):
	 * mk_servicecontract_id > 0 and source SC still active.
	 *
	 * @param string $listQuery
	 * @return string
	 */
	protected function appendFranchiseQuoteFilter($listQuery) {
		$listQuery = (string) $listQuery;
		if ($listQuery === '' || stripos($listQuery, 'mk_qt_franchise_filter') !== false) {
			return $listQuery;
		}

		require_once 'modules/Quotes/helpers/QuoteBaService.php';
		if (!Quotes_QuoteBaService_Helper::ensureServiceContractLinkColumn()) {
			// Column missing — show empty franchise list rather than leaking other quotes.
			$fragment = ' AND 1=0 /* mk_qt_franchise_filter:no_column */ ';
		} else {
			$fragment = ' AND (
			/* mk_qt_franchise_filter */
			vtiger_quotes.mk_servicecontract_id IS NOT NULL
			AND vtiger_quotes.mk_servicecontract_id > 0
			AND EXISTS (
				SELECT 1
				FROM vtiger_servicecontracts sc
				INNER JOIN vtiger_crmentity sce
					ON sce.crmid = sc.servicecontractsid AND sce.deleted = 0
				WHERE sc.servicecontractsid = vtiger_quotes.mk_servicecontract_id
			)
		) ';
		}

		if (preg_match('/\sORDER\s+BY\s/i', $listQuery)) {
			return preg_replace('/\sORDER\s+BY\s/i', $fragment . ' ORDER BY ', $listQuery, 1);
		}
		if (preg_match('/\sGROUP\s+BY\s/i', $listQuery)) {
			return preg_replace('/\sGROUP\s+BY\s/i', $fragment . ' GROUP BY ', $listQuery, 1);
		}
		if (preg_match('/\sLIMIT\s+/i', $listQuery)) {
			return preg_replace('/\sLIMIT\s+/i', $fragment . ' LIMIT ', $listQuery, 1);
		}
		return $listQuery . $fragment;
	}
}
