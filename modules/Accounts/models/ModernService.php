<?php
/*+***********************************************************************************
 * Modern Accounts (KH NQ tiềm năng) — list + tags for Leads-like SALES UI.
 *************************************************************************************/

class Accounts_ModernService {

	const MODULE = 'Accounts';

	protected static $allowedTags = array(
		'ca_nhan', 'cong_ty',
		'moi_quen', 'da_co_quan_he', 'co_quan', 'chuan_bi_mo', 'gia_dinh',
		'chua_mqbh', 'da_tg_free', 'da_tg_fb1', 'thu_3', 'pcth', 'van_hanh', 'mkt', 'lop_khac',
		'tiem_nang', 'mua_lan_dau', 'mua_lai', 'mua_on_dinh', 'dang_cham_soc',
		'dang_tu_van', 'kh_can_nhac', 'khong_mua', 'ngung_mua',
		'nhuong_quyen', 'da_ky_quy',
		'vang', 'bac', 'dong',
		'facebook', 'tiktok', 'website', 'zalo', 'other', 'other_source',
	);

	public static function listAccounts($userId = null) {
		global $current_user;
		if ($userId === null) {
			$userId = (int) $current_user->id;
		}
		$adb = PearDatabase::getInstance();
		$sql = "SELECT a.accountid, a.accountname, a.phone, a.email1, a.account_no,
				a.tb_store_address, a.tb_party_b_name, a.tb_party_b_phone,
				ce.smownerid, ce.createdtime, ce.modifiedtime, ce.description
			FROM vtiger_account a
			INNER JOIN vtiger_crmentity ce ON ce.crmid = a.accountid AND ce.deleted = 0
			ORDER BY ce.createdtime DESC, a.accountid DESC";
		$res = $adb->pquery($sql, array());
		$rows = array();
		$ids = array();
		$n = $adb->num_rows($res);
		for ($i = 0; $i < $n; $i++) {
			$row = $adb->query_result_rowdata($res, $i);
			$ids[] = (int) $row['accountid'];
			$rows[] = $row;
		}
		$tagsById = self::getTagsForIds($ids, $userId);
		$out = array();
		foreach ($rows as $row) {
			$id = (int) $row['accountid'];
			$tags = isset($tagsById[$id]) ? $tagsById[$id] : array();
			$out[] = self::composeRow($row, $tags);
		}
		return $out;
	}

	public static function getAccount($accountId, $userId = null) {
		global $current_user;
		if ($userId === null) {
			$userId = (int) $current_user->id;
		}
		$accountId = (int) $accountId;
		if ($accountId <= 0) {
			return null;
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT a.accountid, a.accountname, a.phone, a.email1, a.account_no,
				a.tb_store_address, a.tb_party_b_name, a.tb_party_b_phone,
				ce.smownerid, ce.createdtime, ce.modifiedtime, ce.description
			FROM vtiger_account a
			INNER JOIN vtiger_crmentity ce ON ce.crmid = a.accountid AND ce.deleted = 0
			WHERE a.accountid = ?",
			array($accountId)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return null;
		}
		$row = $adb->query_result_rowdata($res, 0);
		$tagsMap = self::getTagsForIds(array($accountId), $userId);
		$tags = isset($tagsMap[$accountId]) ? $tagsMap[$accountId] : array();
		return self::composeRow($row, $tags);
	}

	public static function saveTags($accountId, array $tagNames, $userId = null) {
		global $current_user;
		$accountId = (int) $accountId;
		if ($accountId <= 0) {
			throw new Exception('Record not found.');
		}
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'EditView', $accountId)
			&& !Users_Privileges_Model::isPermitted(self::MODULE, 'DetailView', $accountId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}
		if ($userId === null) {
			$userId = (int) $current_user->id;
		}
		require_once 'modules/Vtiger/models/Tag.php';

		$clean = array();
		$allowed = array_flip(self::$allowedTags);
		foreach ($tagNames as $name) {
			$key = self::normalizeTagKey($name);
			if ($key === '' || !isset($allowed[$key])) {
				continue;
			}
			$clean[] = $key;
		}
		$clean = array_values(array_unique($clean));

		$existing = Vtiger_Tag_Model::getAllAccessible($userId, self::MODULE, $accountId);
		$existingByName = array();
		$existingIds = array();
		foreach ($existing as $tagModel) {
			$name = decode_html((string) $tagModel->getName());
			$existingByName[strtolower($name)] = (int) $tagModel->getId();
			$existingIds[] = (int) $tagModel->getId();
		}
		$targetIds = array();
		foreach ($clean as $name) {
			$lk = strtolower($name);
			if (isset($existingByName[$lk])) {
				$targetIds[] = $existingByName[$lk];
				continue;
			}
			$tagModel = Vtiger_Tag_Model::getInstanceByName($name, $userId);
			if ($tagModel) {
				$targetIds[] = (int) $tagModel->getId();
				continue;
			}
			$newTag = new Vtiger_Tag_Model();
			$newTag->setName($name)->setType(Vtiger_Tag_Model::PUBLIC_TYPE);
			$targetIds[] = (int) $newTag->create();
		}
		$targetIds = array_values(array_unique(array_filter($targetIds)));
		$toAdd = array_diff($targetIds, $existingIds);
		$toRemove = array_diff($existingIds, $targetIds);
		if (!empty($toAdd)) {
			Vtiger_Tag_Model::saveForRecord($accountId, $toAdd, $userId, self::MODULE);
		}
		if (!empty($toRemove)) {
			Vtiger_Tag_Model::deleteForRecord($accountId, $toRemove, $userId, self::MODULE);
		}
		$tagsMap = self::getTagsForIds(array($accountId), $userId);
		$raw = isset($tagsMap[$accountId]) ? array_values($tagsMap[$accountId]) : array();
		return array(
			'success' => true,
			'tags' => $raw,
			'account' => self::getAccount($accountId, $userId),
		);
	}

	public static function listAssignableUsers() {
		$userModel = Users_Record_Model::getCurrentUserModel();
		$assignable = $userModel->getAccessibleUsersForModule(self::MODULE);
		if (!is_array($assignable)) {
			return array();
		}
		$out = array();
		foreach ($assignable as $userId => $label) {
			$userId = (int) $userId;
			if ($userId <= 0) {
				continue;
			}
			$out[] = array(
				'id' => $userId,
				'label' => decode_html($label),
			);
		}
		return $out;
	}

	protected static function composeRow(array $row, array $tags) {
		$id = (int) $row['accountid'];
		$name = self::decodeText(isset($row['accountname']) ? $row['accountname'] : '');
		$phone = self::decodeText(isset($row['phone']) ? $row['phone'] : '');
		if ($phone === '' && !empty($row['tb_party_b_phone'])) {
			$phone = self::decodeText($row['tb_party_b_phone']);
		}
		$address = self::decodeText(isset($row['tb_store_address']) ? $row['tb_store_address'] : '');
		$created = '';
		if (!empty($row['createdtime'])) {
			$ts = strtotime($row['createdtime']);
			if ($ts) {
				$created = date('c', $ts);
			}
		}
		$modified = '';
		if (!empty($row['modifiedtime'])) {
			$ts = strtotime($row['modifiedtime']);
			if ($ts) {
				$modified = date('c', $ts);
			}
		}
		return array(
			'id' => (string) $id,
			'crmid' => $id,
			'name' => $name,
			'phone' => $phone,
			'email' => self::decodeText(isset($row['email1']) ? $row['email1'] : ''),
			'account_no' => self::decodeText(isset($row['account_no']) ? $row['account_no'] : ''),
			'address' => $address,
			'area' => '',
			'owner' => self::getOwnerLabel(isset($row['smownerid']) ? (int) $row['smownerid'] : 0),
			'owner_id' => isset($row['smownerid']) ? (int) $row['smownerid'] : 0,
			'tags' => array_values($tags),
			'createdtime' => $created,
			'last_touch' => $modified ?: $created,
			'next_action' => '',
			'notes' => self::decodeText(isset($row['description']) ? $row['description'] : ''),
		);
	}

	protected static function getTagsForIds(array $ids, $userId = null) {
		if (empty($ids)) {
			return array();
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT fo.object_id, t.tag
			 FROM vtiger_freetagged_objects fo
			 INNER JOIN vtiger_freetags t ON t.id = fo.tag_id
			 WHERE fo.module = ? AND fo.object_id IN (" . generateQuestionMarks($ids) . ")
			 ORDER BY fo.tagged_on ASC",
			array_merge(array(self::MODULE), $ids)
		);
		$map = array();
		$n = $adb->num_rows($res);
		for ($i = 0; $i < $n; $i++) {
			$oid = (int) $adb->query_result($res, $i, 'object_id');
			$tag = decode_html($adb->query_result($res, $i, 'tag'));
			if (!isset($map[$oid])) {
				$map[$oid] = array();
			}
			$map[$oid][] = $tag;
		}
		return $map;
	}

	protected static function normalizeTagKey($name) {
		$name = trim(decode_html((string) $name));
		if ($name === '') {
			return '';
		}
		if (isset($name[0]) && $name[0] === '#') {
			$name = substr($name, 1);
		}
		$key = strtolower($name);
		$key = preg_replace('/[^a-z0-9_]+/', '_', $key);
		$key = trim($key, '_');
		$aliases = array(
			'gold' => 'vang',
			'silver' => 'bac',
			'bronze' => 'dong',
			'da_co_quan' => 'co_quan',
			'chua_co_quan' => 'chuan_bi_mo',
			'individual' => 'ca_nhan',
			'company' => 'cong_ty',
		);
		if (isset($aliases[$key])) {
			return $aliases[$key];
		}
		return $key;
	}

	protected static function getOwnerLabel($userId) {
		$userId = (int) $userId;
		if ($userId <= 0) {
			return '';
		}
		try {
			$user = Users_Record_Model::getInstanceById($userId, 'Users');
			$label = trim((string) $user->get('first_name') . ' ' . (string) $user->get('last_name'));
			if ($label === '') {
				$label = (string) $user->get('userlabel');
			}
			if ($label === '') {
				$label = (string) $user->get('user_name');
			}
			return decode_html($label);
		} catch (Exception $e) {
			return '';
		}
	}

	protected static function decodeText($raw) {
		return decode_html(trim((string) $raw));
	}
}
