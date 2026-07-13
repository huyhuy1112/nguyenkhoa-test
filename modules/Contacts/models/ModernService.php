<?php
/*+***********************************************************************************
 * Modern Contacts list — SALES UI (vtiger Contacts + freetags).
 *************************************************************************************/

class Contacts_ModernService {

	const MODULE = 'Contacts';

	public static function listContacts($userId = null) {
		global $current_user;
		if ($userId === null) {
			$userId = (int)$current_user->id;
		}
		$adb = PearDatabase::getInstance();
		$sql = "SELECT cd.contactid, cd.firstname, cd.lastname, cd.title, cd.email, cd.phone, cd.mobile,
				cd.accountid, ce.smownerid, ce.createdtime, ce.modifiedtime,
				acc.accountname
			FROM vtiger_contactdetails cd
			INNER JOIN vtiger_crmentity ce ON ce.crmid = cd.contactid AND ce.deleted = 0
			LEFT JOIN vtiger_account acc ON acc.accountid = cd.accountid
			ORDER BY ce.modifiedtime DESC, cd.contactid DESC";
		$res = $adb->pquery($sql, array());
		$rows = array();
		$contactIds = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$row = $adb->query_result_rowdata($res, $i);
			$contactIds[] = (int)$row['contactid'];
			$rows[] = $row;
		}
		$tagsByContact = self::getTagsForContactIds($contactIds, $userId);
		$segmentsByContact = self::getLeadSegmentsForContactIds($contactIds);
		$out = array();
		require_once 'modules/Contacts/helpers/ContactTagCatalog.php';
		foreach ($rows as $row) {
			$contactId = (int)$row['contactid'];
			$rawTags = $tagsByContact[$contactId] ?? array();
			$segment = isset($segmentsByContact[$contactId]) ? (string)$segmentsByContact[$contactId] : '';
			if ($segment !== '' && Contacts_ContactTagCatalog::isAllowed($segment)) {
				$rawTags[] = $segment;
			}
			$tags = Contacts_ContactTagCatalog::filterTagNames($rawTags);
			$out[] = self::composeCacheRow($row, $tags);
		}
		return $out;
	}

	/**
	 * Map contactId → lead segment (co_quan / chuan_bi_mo / gia_dinh) from linked Lead profile.
	 */
	protected static function getLeadSegmentsForContactIds(array $contactIds) {
		$map = array();
		if (empty($contactIds)) {
			return $map;
		}
		$adb = PearDatabase::getInstance();
		try {
			require_once 'modules/Leads/models/ModernService.php';
			Leads_ModernService::installSchema($adb);
		} catch (Exception $e) {
			return $map;
		}
		$allowed = array('co_quan', 'chuan_bi_mo', 'gia_dinh');
		$res = $adb->pquery(
			"SELECT contact_id, segment FROM bace_lead_profile
			 WHERE contact_id IN (" . generateQuestionMarks($contactIds) . ")
			   AND contact_id > 0 AND segment IS NOT NULL AND segment <> ''
			 ORDER BY leadid DESC",
			$contactIds
		);
		if ($res) {
			for ($i = 0; $i < $adb->num_rows($res); $i++) {
				$cid = (int)$adb->query_result($res, $i, 'contact_id');
				$seg = strtolower(trim((string)$adb->query_result($res, $i, 'segment')));
				if ($cid <= 0 || isset($map[$cid])) {
					continue;
				}
				if (in_array($seg, $allowed, true)) {
					$map[$cid] = $seg;
				}
			}
		}

		$missing = array();
		foreach ($contactIds as $cid) {
			$cid = (int)$cid;
			if ($cid > 0 && !isset($map[$cid])) {
				$missing[] = $cid;
			}
		}
		if (empty($missing)) {
			return $map;
		}

		// Fallback: Lead ↔ Contact relation when contact_id chưa ghi vào profile.
		$res2 = $adb->pquery(
			"SELECT rel.relcrmid AS contact_id, p.segment
			 FROM vtiger_crmentityrel rel
			 INNER JOIN bace_lead_profile p ON p.leadid = rel.crmid
			 WHERE rel.module = 'Leads' AND rel.relmodule = 'Contacts'
			   AND rel.relcrmid IN (" . generateQuestionMarks($missing) . ")
			   AND p.segment IS NOT NULL AND p.segment <> ''
			 ORDER BY p.leadid DESC",
			$missing
		);
		if ($res2) {
			for ($i = 0; $i < $adb->num_rows($res2); $i++) {
				$cid = (int)$adb->query_result($res2, $i, 'contact_id');
				$seg = strtolower(trim((string)$adb->query_result($res2, $i, 'segment')));
				if ($cid <= 0 || isset($map[$cid])) {
					continue;
				}
				if (in_array($seg, $allowed, true)) {
					$map[$cid] = $seg;
				}
			}
		}
		return $map;
	}

	protected static function composeCacheRow(array $row, array $tags) {
		$contactId = (int)$row['contactid'];
		$first = decode_html((string)$row['firstname']);
		$last = decode_html((string)$row['lastname']);
		$name = trim($first . ' ' . $last);
		if ($name === '' || $name === '.') {
			$name = $last !== '' ? $last : ($first !== '' ? $first : '—');
		}
		$phone = decode_html((string)$row['phone']);
		if ($phone === '' || $phone === '--') {
			$phone = decode_html((string)$row['mobile']);
		}
		$email = decode_html((string)$row['email']);
		$accountName = decode_html((string)$row['accountname']);
		$modified = !empty($row['modifiedtime']) ? date('c', strtotime($row['modifiedtime'])) : date('c');

		return array(
			'id' => (string)$contactId,
			'crmid' => $contactId,
			'name' => $name,
			'firstname' => $first,
			'lastname' => $last,
			'title' => decode_html((string)$row['title']),
			'email' => ($email === '' || $email === '--') ? '' : $email,
			'phone' => ($phone === '' || $phone === '--') ? '' : $phone,
			'account' => ($accountName === '' || $accountName === '-') ? '' : $accountName,
			'owner' => self::getOwnerLabel((int)$row['smownerid']),
			'tags' => array_values($tags),
			'last_touch' => $modified,
		);
	}

	protected static function getTagsForContactIds(array $contactIds, $userId = null) {
		if (empty($contactIds)) {
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
			 WHERE fo.module = ? AND fo.object_id IN (" . generateQuestionMarks($contactIds) . ")
			 ORDER BY fo.tagged_on ASC",
			array_merge(array(self::MODULE), $contactIds)
		);
		$map = array();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$contactId = (int)$adb->query_result($res, $i, 'object_id');
			$tag = decode_html($adb->query_result($res, $i, 'tag'));
			if (!isset($map[$contactId])) {
				$map[$contactId] = array();
			}
			$map[$contactId][] = $tag;
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
		foreach ($assignableUsers as $id => $label) {
			$userOptions[] = array(
				'id' => (string)$id,
				'label' => decode_html((string)$label),
			);
		}
		return $userOptions;
	}

	public static function deleteContact($contactId) {
		$contactId = (int) $contactId;
		if ($contactId <= 0) {
			throw new Exception('Contact not found.');
		}
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'Delete', $contactId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$recordModel = Vtiger_Record_Model::getInstanceById($contactId, self::MODULE);
		$recordModel->delete();
		return true;
	}
}
