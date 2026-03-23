<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class SalesOrder_Save_Action extends Inventory_Save_Action {
	/** Set to true temporarily to log status/permission to storage/logs/tools_orders_debug.log */
	const TOOLS_ORDERS_DEBUG_LOG = true;

	protected function isToolsOrdersContext(Vtiger_Request $request) {
		$app = strtoupper((string) $request->get('app'));
		$appName = strtoupper((string) $request->get('appName'));
		return $app === 'TOOLS' || strpos($appName, 'TOOLS') !== false;
	}

	/**
	 * True if this user may change internal_order_status (admin or manager-like role).
	 */
	protected function canChangeInternalOrderStatus(Users_Record_Model $currentUser) {
		if ($this->isVtigerAdminUser($currentUser)) {
			return true;
		}
		$db = PearDatabase::getInstance();
		$roleId = $currentUser->get('roleid');
		if (empty($roleId)) {
			return false;
		}
		$result = $db->pquery('SELECT rolename FROM vtiger_role WHERE roleid = ?', array($roleId));
		if (!$db->num_rows($result)) {
			return false;
		}
		$roleName = strtolower((string) $db->query_result($result, 0, 'rolename'));
		return (bool) preg_match('/\b(manager|management|head|director|leader|administrator|supervisor)\b|admin|ceo|trưởng|giám đốc/iu', $roleName);
	}

	/**
	 * vtiger_users.is_admin is usually 'on'; some deployments use 1/yes.
	 */
	protected function isVtigerAdminUser(Users_Record_Model $currentUser) {
		if ((int) $currentUser->getId() === 1) {
			return true;
		}
		if (method_exists($currentUser, 'isAdminUser') && $currentUser->isAdminUser()) {
			return true;
		}
		$flag = $currentUser->get('is_admin');
		if ($flag === 'on' || $flag === '1' || $flag === 1 || $flag === true) {
			return true;
		}
		return false;
	}

	/**
	 * Normalize user input into canonical picklist values.
	 * Ensures DB values match: MKT|Sale|Support|Other
	 */
	protected function normalizeTeamGroup($teamFromRequest) {
		if ($teamFromRequest === null) {
			return null;
		}
		$t = trim((string) $teamFromRequest);
		if ($t === '') {
			return null;
		}
		$allowed = array('MKT', 'Sale', 'Support', 'Other');
		foreach ($allowed as $canon) {
			if (strcasecmp($canon, $t) === 0) {
				return $canon;
			}
		}
		if (preg_match('/\b(mkt|marketing)\b/iu', $t)) {
			return 'MKT';
		}
		if (preg_match('/\b(sales|sale)\b/iu', $t)) {
			return 'Sale';
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
		$dir = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
		if (!is_dir($dir)) {
			@mkdir($dir, 0755, true);
		}
		$line = date('Y-m-d H:i:s') . ' [Save] ' . $message . "\n";
		@file_put_contents($dir . DIRECTORY_SEPARATOR . 'tools_orders_debug.log', $line, FILE_APPEND);
	}

	/**
	 * Return validation message for Tools Orders, or null when valid.
	 */
	protected function getToolsOrdersValidationError(Vtiger_Request $request) {
		if (!$this->isToolsOrdersContext($request)) {
			return null;
		}
		$status = trim((string) $request->get('internal_order_status'));
		$approvalNote = trim((string) $request->get('approval_note'));
		if ($status === 'Rejected' && $approvalNote === '') {
			return 'Approval Note is required when status is Rejected.';
		}
		return null;
	}

	public function process(Vtiger_Request $request) {
		$validationError = $this->getToolsOrdersValidationError($request);
		if ($validationError !== null) {
			$this->toolsOrdersDebugLog('validation_error=' . $validationError);
			if ($request->isAjax()) {
				$response = new Vtiger_Response();
				$response->setError('Validation Error', $validationError, $validationError);
				$response->emit();
				return;
			}

			$moduleName = $request->getModule();
			$recordId = $request->get('record');
			$redirectUrl = 'index.php?module=' . urlencode($moduleName) . '&view=Edit&app=TOOLS';
			if (!empty($recordId)) {
				$redirectUrl .= '&record=' . urlencode($recordId);
			}
			$redirectUrl .= '&validation_error=' . urlencode($validationError);
			header('Location: ' . $redirectUrl);
			exit;
		}

		parent::process($request);
	}

	protected function getRecordModelFromRequest(Vtiger_Request $request) {
		$recordModel = parent::getRecordModelFromRequest($request);
		if (!$this->isToolsOrdersContext($request)) {
			return $recordModel;
		}

		$currentUser = Users_Record_Model::getCurrentUserModel();
		$recordId = (int) $request->get('record');
		$isCreate = $recordId <= 0;

		// Internal Orders do not use inventory line items.
		if (method_exists($recordModel, 'getEntity')) {
			$entity = $recordModel->getEntity();
			if ($entity) {
				$entity->isLineItemUpdate = false;
			}
		}
		$request->set('totalProductCount', 0);
		$_REQUEST['totalProductCount'] = 0;

		// Ensure custom fields are applied from POST (parent may skip nulls; picklists must be explicit).
		$statusFromRequest = $request->get('internal_order_status');
		if ($statusFromRequest !== null && $statusFromRequest !== '') {
			$recordModel->set('internal_order_status', $statusFromRequest);
		}
		$teamFromRequest = $request->get('team_group');
		$teamNormalized = $this->normalizeTeamGroup($teamFromRequest);
		if ($teamNormalized !== null) {
			$recordModel->set('team_group', $teamNormalized);
		}
		$this->toolsOrdersDebugLog(
			'user=' . $currentUser->getId() . ' record=' . $recordId
			. ' status_from_request=' . var_export($statusFromRequest, true)
			. ' team_from_request=' . var_export($teamFromRequest, true)
			. ' team_normalized=' . var_export($teamNormalized, true)
			. ' team_saved_before_save=' . var_export($recordModel->get('team_group'), true)
			. ' canChangeStatus=' . ($this->canChangeInternalOrderStatus($currentUser) ? '1' : '0')
			. ' isAdmin=' . ($this->isVtigerAdminUser($currentUser) ? '1' : '0')
		);

		if ($isCreate) {
			$recordModel->set('internal_order_status', 'Pending');
			$recordModel->set('created_user_id', $currentUser->getId());
		} else {
			$oldRecord = Vtiger_Record_Model::getInstanceById($recordId, 'SalesOrder');
			$oldStatus = (string) $oldRecord->get('internal_order_status');
			$newStatus = (string) $recordModel->get('internal_order_status');

			if (!$this->canChangeInternalOrderStatus($currentUser)) {
				$recordModel->set('internal_order_status', $oldStatus);
				$newStatus = $oldStatus;
				$this->toolsOrdersDebugLog('status frozen: non-privileged user old=' . $oldStatus);
			}

			if ($newStatus === 'Approved' || $newStatus === 'Rejected') {
				$recordModel->set('approved_by', $currentUser->getId());
			}
			// Rejected note validation handled in process() to return clean form message.
		}

		return $recordModel;
	}
}
