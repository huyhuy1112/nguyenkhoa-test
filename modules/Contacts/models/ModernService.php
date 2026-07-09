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
		$out = array();
		require_once 'modules/Contacts/helpers/ContactTagCatalog.php';
		foreach ($rows as $row) {
			$contactId = (int)$row['contactid'];
			$rawTags = $tagsByContact[$contactId] ?? array();
			$tags = Contacts_ContactTagCatalog::filterTagNames($rawTags);
			$out[] = self::composeCacheRow($row, $tags);
		}
		return $out;
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
}
