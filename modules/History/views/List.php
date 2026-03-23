<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * Tools > History page (app=TOOLS) system-wide audit log (modtracker).
 *
 * The repository currently ships `History` as a lightweight placeholder tab.
 * The default vtiger ListView stack expects an entity module and can trigger
 * "Permission to perform the operation is denied for name : History".
 *
 * So we fully handle rendering here and read audit data from vtiger ModTracker.
 */
class History_List_View extends Vtiger_Index_View {
	/**
	 * Exec/bypass helper.
	 */
	protected $toolsHistoryUserTeam = null;

	protected function isToolsContext(Vtiger_Request $request) {
		return strtoupper((string) $request->get('app')) === 'TOOLS';
	}

	public function requiresPermission(Vtiger_Request $request) {
		return array();
	}

	public function checkPermission(Vtiger_Request $request) {
		return true;
	}

	protected function preProcessTplName(Vtiger_Request $request) {
		// Use list-view wrapper to match standard CRM UI.
		return 'ListViewPreProcess.tpl';
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		parent::preProcess($request, $display);
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('ListViewPostProcess.tpl', $request->getModule());
		// Skip Vtiger_Index_View's IndexPostProcess wrapper.
		Vtiger_Basic_View::postProcess($request);
	}

	public function validateRequest(Vtiger_Request $request) {
		// Bypass module read permissions for placeholder module.
		// Data is scoped to internal Orders via ModTracker query and app=TOOLS check.
	}

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		if (!$this->isToolsContext($request)) {
			$viewer->view('OperationNotPermitted.tpl', 'Vtiger');
			return;
		}

		$viewer->assign('LISTVIEW_MODULE_TITLE', 'System Activity Audit Log');
		$history = $this->getSystemActivityHistoryRows($request);
		$viewer->assign('HISTORY_ROWS', $history['rows']);
		$viewer->assign('HISTORY_USERS', $history['users']);
		$viewer->assign('HISTORY_MODULES', $history['modules']);
		$viewer->view('ListViewContents.tpl', 'History');
	}

	protected function getSystemActivityHistoryRows(Vtiger_Request $request) {
		require_once 'modules/ModTracker/ModTracker.php';
		require_once 'modules/SalesOrder/models/ListView.php';

		$db = PearDatabase::getInstance();
		$start = (int) $request->get('start', 0);
		$limit = (int) $request->get('limit', 50);
		if ($limit <= 0) $limit = 50;

		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$currentUserId = (int) $currentUserModel->getId();

		// Modules meaningful for audit (modtracker module names must match vtiger tab names).
		$auditModules = array(
			'SalesOrder',
			'Invoice',
			'Contacts',
			'Accounts',
			'Campaigns',
			'ProjectTask',
			'Calendar',
			'HelpDesk',
		);

		// Determine bypass scope: Admin/CEO/Executive can see all.
		$bypassAll = false;
		if ($currentUserModel->isAdminUser()) {
			$bypassAll = true;
		} else {
			// Exec visibility heuristic: reuse same regex approach as Tools > Orders (no query changes).
			$roleSignals = array();
			$groupSignals = array();
			try {
				$roleId = (int) $currentUserModel->get('roleid');
				if ($roleId > 0) {
					$roleResult = $db->pquery('SELECT rolename FROM vtiger_role WHERE roleid = ?', array($roleId));
					if ($db->num_rows($roleResult)) {
						$roleSignals[] = (string) $db->query_result($roleResult, 0, 'rolename');
					}
				}

				$groupIds = Vtiger_Util_Helper::getGroupsIdsForUsers($currentUserId);
				if (!empty($groupIds)) {
					$placeholders = generateQuestionMarks($groupIds);
					$result = $db->pquery("SELECT groupname FROM vtiger_groups WHERE groupid IN ($placeholders)", $groupIds);
					$count = $db->num_rows($result);
					for ($i = 0; $i < $count; $i++) {
						$groupSignals[] = (string) $db->query_result($result, $i, 'groupname');
					}
				}
			} catch (Throwable $e) {
				// Ignore and fall back to non-bypass.
			}
			$joined = trim(implode(' ', array_merge($roleSignals, $groupSignals)));
			$bypassAll = (bool) preg_match('/\b(ceo|chief executive|vice president|organization|administrator)\b|giám đốc|tổng giám đốc/iu', (string) $joined);
		}

		// SalesOrder team filter (same logic as Tools > Orders).
		$userTeam = null;
		try {
			$salesOrderListModel = new SalesOrder_ListView_Model();
			$userTeam = $salesOrderListModel->getUserTeam();
		} catch (Throwable $e) {
			$userTeam = null;
		}

		// Safety: non-admin, non-executive should never end up with a "no filter" team.
		if (!$bypassAll && $userTeam === null) {
			$userTeam = 'Other';
		}
		$this->toolsHistoryUserTeam = $userTeam;

		// Only include ModTracker status kinds we can label well.
		$allowedBasicStatuses = array(ModTracker::$CREATED, ModTracker::$UPDATED, ModTracker::$DELETED, ModTracker::$RESTORED);
		$basicIn = implode(',', array_fill(0, count($allowedBasicStatuses), '?'));
		$audModulesIn = implode(',', array_fill(0, count($auditModules), '?'));

		$internalStatuses = array('Pending', 'Approved', 'Rejected');
		$internalIn = implode(',', array_fill(0, count($internalStatuses), '?'));

		$params = array_values($auditModules);
		$params = array_merge($params, $allowedBasicStatuses);

		$sql = "
			SELECT
				b.id,
				b.crmid,
				b.module,
				b.whodid,
				b.changedon,
				b.status,
				ce.label AS record_label,
				u.user_name,
				u.first_name,
				u.last_name
			FROM vtiger_modtracker_basic b
			LEFT JOIN vtiger_crmentity ce ON ce.crmid = b.crmid
			LEFT JOIN vtiger_users u ON u.id = b.whodid
			LEFT JOIN vtiger_salesorder so ON so.salesorderid = b.crmid
			WHERE b.module IN ($audModulesIn)
				AND b.status IN ($basicIn)
		";

		if (!$bypassAll) {
			$sql .= "
				AND (
					(
						b.module = 'SalesOrder'
						AND so.internal_order_status IN ($internalIn)
					 ";
			// internal statuses
			$params = array_merge($params, $internalStatuses);

			if ($userTeam !== null) {
				$sql .= " AND so.team_group = ? ";
				$params[] = $userTeam;
			}

			$sql .= "
					)
					OR
					(
						b.module <> 'SalesOrder'
						AND b.whodid = ?
					)
				)
			";
			$params[] = $currentUserId;
		}

		$sql .= " ORDER BY b.changedon DESC LIMIT ?, ?";
		$params[] = $start;
		$params[] = $limit;

		$result = $db->pquery($sql, $params);
		$basicRows = array();
		$basicIds = array();
		while ($row = $db->fetchByAssoc($result)) {
			$basicRows[] = $row;
			$basicIds[] = (int) $row['id'];
		}

		if (empty($basicIds)) {
			return array('rows' => array(), 'users' => array(), 'modules' => array());
		}

		// ModTracker details (field changes)
		$detailRowsByBasicId = array();
		$in = generateQuestionMarks($basicIds);
		$detailSql = "SELECT id, fieldname, prevalue, postvalue FROM vtiger_modtracker_detail WHERE id IN ($in)";
		$detailResult = $db->pquery($detailSql, $basicIds);
		while ($d = $db->fetchByAssoc($detailResult)) {
			$id = (int) $d['id'];
			if (!isset($detailRowsByBasicId[$id])) $detailRowsByBasicId[$id] = array();
			$detailRowsByBasicId[$id][] = $d;
		}

		// Map users referenced in details (best-effort for readability).
		$userRefFields = array('approved_by', 'assigned_user_id', 'smownerid', 'owner');
		$userMap = array();
		$allUserIds = array();
		foreach ($basicRows as $br) {
			if (!empty($br['whodid']) && is_numeric($br['whodid'])) {
				$allUserIds[(int) $br['whodid']] = true;
			}
		}
		foreach ($detailRowsByBasicId as $basicId => $details) {
			foreach ($details as $d) {
				$field = (string) $d['fieldname'];
				if (!in_array($field, $userRefFields, true)) continue;
				if (is_numeric($d['prevalue'])) $allUserIds[(int) $d['prevalue']] = true;
				if (is_numeric($d['postvalue'])) $allUserIds[(int) $d['postvalue']] = true;
			}
		}

		if (!empty($allUserIds)) {
			$userIds = array_keys($allUserIds);
			$uIn = generateQuestionMarks($userIds);
			$userSql = "SELECT id, user_name, first_name, last_name FROM vtiger_users WHERE id IN ($uIn)";
			$userRes = $db->pquery($userSql, $userIds);
			while ($ur = $db->fetchByAssoc($userRes)) {
				$fullName = trim((string) $ur['first_name'] . ' ' . (string) $ur['last_name']);
				if ($fullName === '') $fullName = (string) $ur['user_name'];
				$userMap[(int) $ur['id']] = $fullName;
			}
		}

		// Field labels (SalesOrder internal audit)
		$fieldLabels = array(
			'internal_order_status' => 'Status',
			'approved_by' => 'Approved By',
			'approval_note' => 'Approval Note',
			'team_group' => 'Team Group',
			'purpose' => 'Purpose',
			'internal_cost' => 'Cost',
			'needed_time' => 'Needed Time',
		);
		$allowedSalesOrderFields = array_keys($fieldLabels);

		$historyUsers = array();
		$historyModules = array();
		$rows = array();

		foreach ($basicRows as $br) {
			$basicId = (int) $br['id'];
			$crmid = (int) $br['crmid'];
			$module = (string) $br['module'];

			$displayUser = trim((string) $br['first_name'] . ' ' . (string) $br['last_name']);
			if ($displayUser === '') $displayUser = (string) $br['user_name'];

			$userId = isset($br['whodid']) && is_numeric($br['whodid']) ? (int) $br['whodid'] : 0;
			$historyUsers[$userId] = $displayUser;
			$historyModules[$module] = $module;

			$detailList = isset($detailRowsByBasicId[$basicId]) ? $detailRowsByBasicId[$basicId] : array();

			$action = 'Edited';
			$status = (string) $br['status'];
			if ($status === (string) ModTracker::$CREATED) {
				$action = 'Created';
			} elseif ($status === (string) ModTracker::$DELETED) {
				$action = 'Deleted';
			} elseif ($status === (string) ModTracker::$RESTORED) {
				$action = 'Restored';
			} elseif ($status === (string) ModTracker::$UPDATED) {
				if ($module === 'SalesOrder') {
					$statusPost = null;
					$statusChanged = false;
					foreach ($detailList as $d) {
						if (!isset($d['fieldname']) || (string) $d['fieldname'] !== 'internal_order_status') continue;
						$statusPost = trim((string) $d['postvalue']);
						$statusChanged = (trim((string) $d['prevalue']) !== $statusPost);
						break;
					}
					if (strcasecmp((string) $statusPost, 'Approved') === 0) {
						$action = 'Approved';
					} elseif (strcasecmp((string) $statusPost, 'Rejected') === 0) {
						$action = 'Rejected';
					} elseif ($statusChanged) {
						$action = 'Status changed';
					} else {
						$action = 'Edited';
					}
				} else {
					$isStatusChanged = false;
					foreach ($detailList as $d) {
						$field = (string) ($d['fieldname'] ?? '');
						if ($field === '') continue;
						if (stripos($field, 'status') === false) continue;
						$pre = trim((string) ($d['prevalue'] ?? ''));
						$post = trim((string) ($d['postvalue'] ?? ''));
						if ($pre !== '' && $post !== '' && $pre !== $post) {
							$isStatusChanged = true;
							break;
						}
					}
					$action = $isStatusChanged ? 'Status changed' : 'Edited';
				}
			}

			// Changed fields (readable, limited)
			$details = array();
			if ($module === 'SalesOrder') {
				$maxPieces = 7;
				foreach ($detailList as $d) {
					$field = (string) $d['fieldname'];
					if (!in_array($field, $allowedSalesOrderFields, true)) continue;

					$label = $fieldLabels[$field] ?? $field;
					$preRaw = $d['prevalue'] ?? '';
					$postRaw = $d['postvalue'] ?? '';

					$pre = is_numeric($preRaw) && in_array($field, array('approved_by'), true) ? ($userMap[(int) $preRaw] ?? $preRaw) : (string) $preRaw;
					$post = is_numeric($postRaw) && in_array($field, array('approved_by'), true) ? ($userMap[(int) $postRaw] ?? $postRaw) : (string) $postRaw;

					$pre = trim((string) $pre);
					$post = trim((string) $post);

					if ($pre !== '' && $post !== '' && $pre !== $post) {
						$details[] = $label . ': ' . $pre . ' -> ' . $post;
					} elseif ($post !== '') {
						$details[] = $label . ': ' . $post;
					}

					if (count($details) >= $maxPieces) break;
				}
			} else {
				$maxPieces = 6;
				foreach ($detailList as $d) {
					$field = (string) ($d['fieldname'] ?? '');
					if ($field === '') continue;

					$pre = trim((string) ($d['prevalue'] ?? ''));
					$post = trim((string) ($d['postvalue'] ?? ''));

					if ($pre === $post || ($pre === '' && $post === '')) continue;

					$label = ucwords(str_replace('_', ' ', $field));

					// Best-effort: map user refs to names.
					if (in_array($field, $userRefFields, true)) {
						if (is_numeric($pre)) $pre = $userMap[(int) $pre] ?? $pre;
						if (is_numeric($post)) $post = $userMap[(int) $post] ?? $post;
					}

					if ($pre !== '' && $post !== '') {
						$details[] = $label . ': ' . $pre . ' -> ' . $post;
					} else {
						$details[] = $label . ': ' . ($post !== '' ? $post : $pre);
					}

					if (count($details) >= $maxPieces) break;
				}
			}

			$detailStr = implode('; ', $details);
			if ($detailStr === '') $detailStr = '-';

			$recordLabel = trim((string) ($br['record_label'] ?? ''));
			if ($recordLabel === '') $recordLabel = '(Untitled Record)';

			$changedOn = isset($br['changedon']) ? (string) $br['changedon'] : '';

			$rows[] = array(
				'id' => $basicId,
				'time' => $changedOn,
				'userId' => $userId,
				'user' => $displayUser,
				'module' => $module,
				'action' => $action,
				'recordLabel' => $recordLabel,
				'recordId' => $crmid,
				'detailUrl' => 'index.php?module=' . $module . '&view=Detail&record=' . $crmid . '&app=TOOLS',
				'details' => $detailStr,
			);
		}

		// Stable sort for dropdowns.
		ksort($historyUsers);
		$historyModulesList = array_values(array_unique(array_values($historyModules)));
		sort($historyModulesList);

		return array(
			'rows' => $rows,
			'users' => $historyUsers,
			'modules' => $historyModulesList,
		);
	}
}
?>