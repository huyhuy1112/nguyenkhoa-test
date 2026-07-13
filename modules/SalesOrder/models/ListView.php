<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class SalesOrder_ListView_Model extends Inventory_ListView_Model {
	/** Set to true temporarily to log team mapping to storage/logs/tools_orders_debug.log */
	const TOOLS_ORDERS_DEBUG_LOG = true;

	public function getListViewEntries($pagingModel) {
		$listViewRecordModels = parent::getListViewEntries($pagingModel);
		if (empty($listViewRecordModels)) {
			return $listViewRecordModels;
		}
		if (strtoupper((string) ($_REQUEST['app'] ?? '')) === 'SALES') {
			require_once 'modules/Vtiger/helpers/MkSalesCustomerName.php';
			$warehouseNames = $this->loadWarehouseNamesBySalesOrderIds(array_keys($listViewRecordModels));
			foreach ($listViewRecordModels as $recordId => $recordModel) {
				$corrected = $this->resolveDisplayGrandTotal($recordModel);
				if ($corrected !== null) {
					$formatted = CurrencyField::convertToUserFormat($corrected, null, true);
					$recordModel->set('hdnGrandTotal', $formatted);
					$recordModel->set('total', $formatted);
				}
				$listViewRecordModels[$recordId] = Vtiger_MkSalesCustomerName_Helper::applyListCustomerColumn($recordModel);
				$whName = isset($warehouseNames[(int) $recordId]) ? $warehouseNames[(int) $recordId] : '';
				$listViewRecordModels[$recordId]->set('mk_warehouse_name', $whName !== '' ? $whName : '—');
			}
		}
		return $listViewRecordModels;
	}

	/**
	 * Map salesorderid => warehouse display name (from linked goods issue).
	 *
	 * @param array $salesOrderIds
	 * @return array
	 */
	protected function loadWarehouseNamesBySalesOrderIds(array $salesOrderIds) {
		$ids = array();
		foreach ($salesOrderIds as $id) {
			$id = (int) $id;
			if ($id > 0) {
				$ids[] = $id;
			}
		}
		$ids = array_values(array_unique($ids));
		if (empty($ids)) {
			return array();
		}
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT gi.salesorder_id, gi.warehouse_id, gi.storage_location
			 FROM vtiger_goodsissue gi
			 WHERE gi.deleted = 0 AND gi.salesorder_id IN (' . generateQuestionMarks($ids) . ')
			 ORDER BY gi.issueid DESC',
			$ids
		);
		$map = array();
		require_once 'modules/Warehouse/helpers/WarehouseRegistry.php';
		if ($rs) {
			while ($row = $db->fetchByAssoc($rs)) {
				$soId = (int) (isset($row['salesorder_id']) ? $row['salesorder_id'] : 0);
				if ($soId <= 0 || isset($map[$soId])) {
					continue;
				}
				$whId = trim((string) (isset($row['warehouse_id']) ? $row['warehouse_id'] : ''));
				$name = $whId !== '' ? Warehouse_Registry::getName($whId) : '';
				if ($name === '') {
					$name = trim(decode_html((string) (isset($row['storage_location']) ? $row['storage_location'] : '')));
				}
				if ($name !== '') {
					$map[$soId] = $name;
				}
			}
		}
		return $map;
	}

	/**
	 * List "Tổng cộng" must match inline panel bottom:
	 * (tiền hàng − CK + thuế + ship + điều chỉnh) − khách đã trả.
	 * After copy, DB total often equals subtotal (missing tax) — recompute like Detail view.
	 */
	protected function resolveDisplayGrandTotal(Vtiger_Record_Model $recordModel) {
		$recordId = (int) $recordModel->getId();
		if ($recordId <= 0) {
			return null;
		}

		$db = PearDatabase::getInstance();
		$headerResult = $db->pquery(
			'SELECT subtotal, total, pre_tax_total, discount_amount, discount_percent,
			        s_h_amount, adjustment, received
			 FROM vtiger_salesorder WHERE salesorderid = ?',
			array($recordId)
		);
		if (!$headerResult || $db->num_rows($headerResult) === 0) {
			return null;
		}

		$headerSubTotal = (float) $db->query_result($headerResult, 0, 'subtotal');
		$headerTotal = (float) $db->query_result($headerResult, 0, 'total');
		$preTax = (float) $db->query_result($headerResult, 0, 'pre_tax_total');
		$discount = (float) $db->query_result($headerResult, 0, 'discount_amount');
		$discountPct = (float) $db->query_result($headerResult, 0, 'discount_percent');
		$shipping = (float) $db->query_result($headerResult, 0, 's_h_amount');
		$adjustment = (float) $db->query_result($headerResult, 0, 'adjustment');
		$paid = (float) $db->query_result($headerResult, 0, 'received');
		if ($paid < 0) {
			$paid = 0;
		}

		$lineResult = $db->pquery(
			'SELECT COALESCE(SUM(quantity * listprice), 0) AS line_subtotal FROM vtiger_inventoryproductrel WHERE id = ?',
			array($recordId)
		);
		$lineSubTotal = (float) $db->query_result($lineResult, 0, 'line_subtotal');

		$subTotal = $headerSubTotal;
		if ($lineSubTotal > 0) {
			if ($headerSubTotal <= 0) {
				$subTotal = $lineSubTotal;
			} elseif ($lineSubTotal > ($headerSubTotal * 50)) {
				// Corrupted duplicate lines — keep header.
				$subTotal = $headerSubTotal;
			} elseif ($headerSubTotal > ($lineSubTotal * 50)) {
				$subTotal = $lineSubTotal;
			} else {
				$subTotal = $lineSubTotal;
			}
		}

		if ($subTotal <= 0) {
			if ($headerTotal > 0) {
				$remaining = $headerTotal - $paid;
				return $remaining < 0 ? 0.0 : $remaining;
			}
			return null;
		}

		if ($discount <= 0 && $discountPct > 0) {
			$discount = $subTotal * $discountPct / 100;
		}

		$vatPercent = 8.0;
		$mkVat = (float) $recordModel->get('mk_vat_percent');
		if ($mkVat > 0 && $mkVat <= 100) {
			$vatPercent = $mkVat;
		}

		$base = $subTotal - $discount + $shipping + $adjustment;
		$tax = 0.0;
		if ($preTax > 0 && $headerTotal > $preTax) {
			$derived = $headerTotal - $preTax;
			if ($derived <= ($subTotal * 0.5)) {
				$tax = $derived;
			}
		}
		if ($tax <= 0 && $headerTotal > $base) {
			$derived = $headerTotal - $base;
			if ($derived <= ($subTotal * 0.5)) {
				$tax = $derived;
			}
		}
		// Copy / bad save often stores total == subtotal (no tax). Match inline panel VAT.
		if ($tax <= 0) {
			$tax = round(($subTotal - $discount) * $vatPercent / 100);
		}
		if ($tax > ($subTotal * 0.5)) {
			$tax = round(($subTotal - $discount) * $vatPercent / 100);
		}

		$grand = $base + $tax;
		// Prefer a sensible stored grand total when it already includes tax.
		if ($headerTotal > ($subTotal + 1) && $headerTotal <= ($subTotal * 2)) {
			$grand = $headerTotal;
		}

		$remaining = $grand - $paid;
		if ($remaining < 0) {
			$remaining = 0;
		}
		return $remaining;
	}

	protected function isToolsOrdersContext() {
		return strtoupper((string) ($_REQUEST['app'] ?? '')) === 'TOOLS';
	}

	/**
	 * Picklist values for team_group (must match DB exactly).
	 * @return array
	 */
	protected function getTeamGroupPicklistValues() {
		return array('MKT', 'Sale', 'Support', 'Other');
	}

	/**
	 * Map user groups + role text to a team_group picklist value.
	 *
	 * Priority rules (strongest first):
	 * 1) Role-based mapping (exact first, then keyword inference).
	 * 2) Group-based mapping (exact first, then keyword inference).
	 * 3) Keyword fallback on combined text.
	 *
	 * Returned value is canonical: MKT|Sale|Support|Other.
	 */
	public function getUserTeam() {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if ($currentUser->isAdminUser()) {
			$this->toolsOrdersDebugLog('getUserTeam: admin -> null (see all)');
			return null;
		}

		$db = PearDatabase::getInstance();
		$groupSignals = array();
		$roleSignals = array();
		$groupIds = Vtiger_Util_Helper::getGroupsIdsForUsers($currentUser->getId());

		if (!empty($groupIds)) {
			$placeholders = generateQuestionMarks($groupIds);
			$result = $db->pquery("SELECT groupname FROM vtiger_groups WHERE groupid IN ($placeholders)", $groupIds);
			$count = $db->num_rows($result);
			for ($i = 0; $i < $count; $i++) {
				$groupSignals[] = (string) $db->query_result($result, $i, 'groupname');
			}
		}

		$roleId = $currentUser->get('roleid');
		if (!empty($roleId)) {
			$roleResult = $db->pquery('SELECT rolename FROM vtiger_role WHERE roleid = ?', array($roleId));
			if ($db->num_rows($roleResult)) {
				$roleSignals[] = (string) $db->query_result($roleResult, 0, 'rolename');
			}
		}

		$joined = trim(implode(' ', array_merge($roleSignals, $groupSignals)));
		if ($this->isExecutiveVisibilityRole($joined)) {
			$this->toolsOrdersDebugLog(
				'getUserTeam: executive role detected -> null (see all). user=' . $currentUser->getId()
				. ' roleSignals=' . json_encode($roleSignals)
				. ' groupSignals=' . json_encode($groupSignals)
			);
			return null;
		}

		$allowed = $this->getTeamGroupPicklistValues();
		$userId = $currentUser->getId();

		// 1) ROLE mapping
		$roleText = trim(implode(' ', $roleSignals));
		foreach ($roleSignals as $raw) {
			$t = trim($raw);
			if ($t !== '' && in_array($t, $allowed, true)) {
				$this->toolsOrdersDebugLog('getUserTeam: ROLE exact match user=' . $userId . ' role="' . $t . '" -> ' . $t . ' matched=ROLE_EXACT');
				return $t;
			}
		}
		if ($roleText !== '') {
			$roleTeam = $this->inferTeamFromRoleText($roleText);
			$this->toolsOrdersDebugLog('getUserTeam: ROLE inferred user=' . $userId . ' roleText="' . $roleText . '" -> ' . $roleTeam . ' matched=ROLE_INFER roleSignals=' . json_encode($roleSignals) . ' groupSignals=' . json_encode($groupSignals));
			if (in_array($roleTeam, $allowed, true)) {
				return $roleTeam;
			}
		}

		// 2) GROUP mapping
		foreach ($groupSignals as $raw) {
			$t = trim($raw);
			if ($t !== '' && in_array($t, $allowed, true)) {
				$this->toolsOrdersDebugLog('getUserTeam: GROUP exact match user=' . $userId . ' group="' . $t . '" -> ' . $t . ' matched=GROUP_EXACT roleSignals=' . json_encode($roleSignals));
				return $t;
			}
		}

		if (!empty($groupSignals)) {
			$groupJoined = trim(implode(' ', $groupSignals));
			$groupTeam = $this->inferTeamFromText($groupJoined);
			$this->toolsOrdersDebugLog('getUserTeam: GROUP inferred user=' . $userId . ' groupJoined="' . $groupJoined . '" -> ' . $groupTeam . ' matched=GROUP_INFER roleSignals=' . json_encode($roleSignals) . ' groupSignals=' . json_encode($groupSignals));
			if (in_array($groupTeam, $allowed, true)) {
				return $groupTeam;
			}
		}

		// 3) Keyword fallback
		$team = $this->inferTeamFromText($joined);
		$this->toolsOrdersDebugLog('getUserTeam: FALLBACK inferred user=' . $userId . ' text="' . $joined . '" -> ' . $team . ' matched=FALLBACK');
		return $team;
	}

	protected function isExecutiveVisibilityRole($text) {
		return (bool) preg_match('/\b(ceo|chief executive|vice president|organization|administrator)\b|giám đốc|tổng giám đốc/iu', (string) $text);
	}

	/**
	 * @param string $text
	 * @return string one of MKT|Sale|Support|Other
	 */
	protected function inferTeamFromText($text) {
		$t = $text;
		// MKT / marketing (before generic "sale" to reduce overlap with mixed titles)
		if (preg_match('/\b(mkt|marketing)\b/iu', $t)) {
			return 'MKT';
		}
		// Sale / Sales — word boundaries avoid matching "...sale" inside unrelated tokens
		if (preg_match('/\b(sales|sale)\b/iu', $t)) {
			return 'Sale';
		}
		if (preg_match('/\b(support)\b/iu', $t)) {
			return 'Support';
		}
		return 'Other';
	}

	/**
	 * Role inference: must prefer Sale over MKT so that Sales roles are not overridden
	 * by Marketing groups.
	 */
	protected function inferTeamFromRoleText($text) {
		$t = (string) $text;
		if (preg_match('/\b(sales|sale)\b/iu', $t)) {
			return 'Sale';
		}
		if (preg_match('/\b(mkt|marketing)\b/iu', $t)) {
			return 'MKT';
		}
		if (preg_match('/\b(support)\b/iu', $t)) {
			return 'Support';
		}
		return 'Other';
	}

	protected function toolsOrdersDebugLog($message) {
		if (!self::TOOLS_ORDERS_DEBUG_LOG) {
			return;
		}
		$dir = dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
		if (!is_dir($dir)) {
			@mkdir($dir, 0755, true);
		}
		$line = date('Y-m-d H:i:s') . ' [ListView] ' . $message . "\n";
		@file_put_contents($dir . DIRECTORY_SEPARATOR . 'tools_orders_debug.log', $line, FILE_APPEND);
	}

	public function getQuery() {
		$listQuery = parent::getQuery();
		if (!$this->isToolsOrdersContext()) {
			return $listQuery;
		}

		$userTeam = $this->getUserTeam();
		if ($userTeam === null) {
			return $listQuery;
		}

		$this->toolsOrdersDebugLog('getQuery: userTeam=' . $userTeam);
		$escapedTeam = PearDatabase::getInstance()->sql_escape_string($userTeam);
		// Custom internal-order fields were created on vtiger_salesorder table (not vtiger_salesordercf).
		$sqlClause = " AND vtiger_salesorder.team_group = '" . $escapedTeam . "'";
		$this->toolsOrdersDebugLog('getQuery: sqlClause=' . $sqlClause);
		return $listQuery . $sqlClause;
	}
}