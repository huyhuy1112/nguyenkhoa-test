<?php
/*+***********************************************************************************
 * Modern Potentials list — SALES UI (maps vtiger Potentials + freetags).
 *************************************************************************************/

class Potentials_ModernService {

	const MODULE = 'Potentials';

	public static function listPotentials($userId = null) {
		global $current_user;
		if ($userId === null) {
			$userId = (int)$current_user->id;
		}
		$adb = PearDatabase::getInstance();
		$sql = "SELECT p.potentialid, p.potentialname, p.sales_stage, p.closingdate, p.amount,
				p.leadsource, p.order_category, p.related_to, p.contact_id,
				ce.smownerid, ce.createdtime, ce.modifiedtime,
				acc.accountname,
				cd.firstname AS contact_firstname, cd.lastname AS contact_lastname
			FROM vtiger_potential p
			INNER JOIN vtiger_crmentity ce ON ce.crmid = p.potentialid AND ce.deleted = 0
			LEFT JOIN vtiger_account acc ON acc.accountid = p.related_to
			LEFT JOIN vtiger_contactdetails cd ON cd.contactid = p.contact_id
			ORDER BY ce.modifiedtime DESC, p.potentialid DESC";
		$res = $adb->pquery($sql, array());
		$rows = array();
		$potentialIds = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$row = $adb->query_result_rowdata($res, $i);
			$potentialIds[] = (int)$row['potentialid'];
			$rows[] = $row;
		}
		$tagsByPotential = self::getTagsForPotentialIds($potentialIds, $userId);
		$out = array();
		foreach ($rows as $row) {
			$potentialId = (int)$row['potentialid'];
			$out[] = self::composeCacheRow($row, $tagsByPotential[$potentialId] ?? array());
		}
		return $out;
	}

	protected static function composeCacheRow(array $row, array $tags) {
		$potentialId = (int)$row['potentialid'];
		$ownerName = self::getOwnerLabel((int)$row['smownerid']);
		$contactName = trim(decode_html((string)$row['contact_firstname']) . ' ' . decode_html((string)$row['contact_lastname']));
		$accountName = decode_html((string)$row['accountname']);
		$modified = !empty($row['modifiedtime']) ? date('c', strtotime($row['modifiedtime'])) : date('c');
		$closing = !empty($row['closingdate']) ? $row['closingdate'] : '';

		return array(
			'id' => (string)$potentialId,
			'crmid' => $potentialId,
			'name' => decode_html((string)$row['potentialname']),
			'sales_stage' => decode_html((string)$row['sales_stage']),
			'closingdate' => $closing,
			'amount' => (float)$row['amount'],
			'leadsource' => decode_html((string)$row['leadsource']),
			'order_category' => decode_html((string)$row['order_category']),
			'account' => ($accountName === '' || $accountName === '-') ? '' : $accountName,
			'contact' => ($contactName === '' || $contactName === '.') ? '' : $contactName,
			'owner' => $ownerName,
			'tags' => array_values($tags),
			'last_touch' => $modified,
		);
	}

	protected static function getTagsForPotentialIds(array $potentialIds, $userId = null) {
		if (empty($potentialIds)) {
			return array();
		}
		global $current_user;
		if ($userId === null) {
			$userId = (int)$current_user->id;
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT fo.object_id, t.tag
			 FROM vtiger_freetagged_objects fo
			 INNER JOIN vtiger_freetags t ON t.id = fo.tag_id
			 WHERE fo.module = ? AND fo.object_id IN (" . generateQuestionMarks($potentialIds) . ")
			 ORDER BY fo.tagged_on ASC",
			array_merge(array(self::MODULE), $potentialIds)
		);
		$map = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$potentialId = (int)$adb->query_result($res, $i, 'object_id');
			$tag = decode_html($adb->query_result($res, $i, 'tag'));
			if (!isset($map[$potentialId])) {
				$map[$potentialId] = array();
			}
			$map[$potentialId][] = $tag;
		}
		return $map;
	}

	protected static function getOwnerLabel($userId) {
		$userId = (int)$userId;
		if ($userId <= 0) {
			return '';
		}
		try {
			$user = Users_Record_Model::getInstanceById($userId, 'Users');
			$label = trim((string)$user->get('first_name') . ' ' . (string)$user->get('last_name'));
			if ($label === '') {
				$label = (string)$user->get('userlabel');
			}
			return decode_html($label);
		} catch (Exception $e) {
			return '';
		}
	}

	public static function listAssignableUsers() {
		$userModel = Users_Record_Model::getCurrentUserModel();
		$assignableUsers = $userModel->getAccessibleUsersForModule(self::MODULE);
		if (!is_array($assignableUsers)) {
			$assignableUsers = array();
		}
		$userOptions = array();
		foreach ($assignableUsers as $userId => $label) {
			$userId = (int)$userId;
			if ($userId <= 0) {
				continue;
			}
			try {
				$userRecord = Users_Record_Model::getInstanceById($userId, 'Users');
				$userName = (string)$userRecord->get('user_name');
				if ($userName === '') {
					continue;
				}
				$userOptions[] = array(
					'id' => $userId,
					'user_name' => $userName,
					'label' => decode_html($label),
				);
			} catch (Exception $e) {
				continue;
			}
		}
		return $userOptions;
	}
}
