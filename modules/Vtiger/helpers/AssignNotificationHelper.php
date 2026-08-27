<?php
/*+***********************************************************************************
 * Shared: notify owner on new record / reassignment (respects user channel prefs).
 *************************************************************************************/

require_once 'data/VTEntityDelta.php';
require_once 'modules/Vtiger/models/NotificationService.php';

class Vtiger_AssignNotificationHelper {

	/**
	 * @param string $eventName
	 * @param VTEntityData $entityData
	 * @param string $moduleName
	 * @param string $nameField field name on entity (or empty → callback)
	 * @param string $labelPrefix e.g. "Lead", "Opportunity"
	 * @param callable|null $nameResolver function($entityData, $recordId): string
	 */
	public static function handleAfterSave($eventName, $entityData, $moduleName, $nameField, $labelPrefix, $nameResolver = null) {
		global $adb, $log;

		if ($eventName !== 'vtiger.entity.aftersave.final') {
			return;
		}
		if (!$entityData || $entityData->getModuleName() !== $moduleName) {
			return;
		}

		try {
			$recordId = $entityData->getId();
			if (empty($recordId)) {
				return;
			}

			$ownerResult = $adb->pquery('SELECT smownerid FROM vtiger_crmentity WHERE crmid = ?', array($recordId));
			if (!$ownerResult || $adb->num_rows($ownerResult) === 0) {
				return;
			}
			$newOwnerId = (int) $adb->query_result($ownerResult, 0, 'smownerid');
			if ($newOwnerId <= 0) {
				return;
			}

			$userCheck = $adb->pquery('SELECT id FROM vtiger_users WHERE id = ? AND status = ?', array($newOwnerId, 'Active'));
			if (!$userCheck || $adb->num_rows($userCheck) === 0) {
				return;
			}

			$isNew = method_exists($entityData, 'isNew') ? $entityData->isNew() : false;
			// aftersave.final often has id already set → isNew() false on create.
			// Create has no beforesave oldEntity snapshot in VTEntityDelta.
			if (!$isNew) {
				$deltaProbe = new VTEntityDelta();
				$oldEntity = null;
				try {
					$oldEntity = $deltaProbe->getOldEntity($moduleName, $recordId);
				} catch (Exception $e) {
					$oldEntity = null;
				}
				if (empty($oldEntity)) {
					$timeRes = $adb->pquery(
						'SELECT createdtime, modifiedtime FROM vtiger_crmentity WHERE crmid = ?',
						array($recordId)
					);
					if ($timeRes && $adb->num_rows($timeRes) > 0) {
						$created = (string) $adb->query_result($timeRes, 0, 'createdtime');
						$modified = (string) $adb->query_result($timeRes, 0, 'modifiedtime');
						$ct = $created ? strtotime($created) : 0;
						$mt = $modified ? strtotime($modified) : 0;
						// New record: created≈modified, or created within last 2 minutes.
						if ($ct > 0 && (( $mt > 0 && abs($mt - $ct) <= 5) || (time() - $ct) <= 120)) {
							$isNew = true;
						}
					}
				}
			}
			$shouldNotify = false;

			if ($isNew) {
				$shouldNotify = true;
			} else {
				$delta = new VTEntityDelta();
				$changes = $delta->getEntityDelta($moduleName, $recordId);
				if (!empty($changes) && isset($changes['assigned_user_id'])) {
					$oldOwnerId = isset($changes['assigned_user_id']['oldValue']) ? $changes['assigned_user_id']['oldValue'] : null;
					$newOwnerIdFromDelta = isset($changes['assigned_user_id']['currentValue']) ? $changes['assigned_user_id']['currentValue'] : null;
					if (!empty($newOwnerIdFromDelta) && strpos((string) $newOwnerIdFromDelta, 'x') !== false) {
						$parts = explode('x', (string) $newOwnerIdFromDelta);
						$newOwnerIdFromDelta = isset($parts[1]) ? $parts[1] : $parts[0];
					}
					if ($oldOwnerId != $newOwnerIdFromDelta && !empty($newOwnerIdFromDelta)) {
						$shouldNotify = true;
						$newOwnerId = (int) $newOwnerIdFromDelta;
					}
				}
			}

			if (!$shouldNotify) {
				return;
			}

			// Skip duplicate create/assign ping within 2 minutes for same record+user.
			$dup = $adb->pquery(
				"SELECT id FROM vtiger_notifications
				 WHERE userid = ? AND module = ? AND recordid = ?
				   AND created_at >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
				 LIMIT 1",
				array($newOwnerId, $moduleName, $recordId)
			);
			if ($dup && $adb->num_rows($dup) > 0) {
				return;
			}

			$displayName = '';
			if (is_callable($nameResolver)) {
				$displayName = (string) call_user_func($nameResolver, $entityData, $recordId);
			} elseif ($nameField !== '') {
				$displayName = trim((string) $entityData->get($nameField));
			}
			if ($displayName === '') {
				try {
					$names = getEntityName($moduleName, array($recordId));
					if (is_array($names) && !empty($names[$recordId])) {
						$displayName = $names[$recordId];
					}
				} catch (Exception $e) {
					// ignore
				}
			}
			if ($displayName === '') {
				$displayName = $labelPrefix . ' #' . $recordId;
			}

			$message = $isNew
				? ('Có ' . $labelPrefix . ' mới được giao cho bạn: ' . $displayName)
				: ('Bạn được giao ' . $labelPrefix . ': ' . $displayName);

			Vtiger_NotificationService::createIfEnabled(
				$newOwnerId,
				$moduleName,
				$recordId,
				$message,
				'assign',
				Vtiger_NotificationService::channelForModule($moduleName, 'assign')
			);
		} catch (Exception $e) {
			if ($log) {
				$log->error('[AssignNotificationHelper] ' . $moduleName . ': ' . $e->getMessage());
			}
		}
	}
}
