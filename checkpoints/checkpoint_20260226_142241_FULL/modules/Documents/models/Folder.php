<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Documents_Folder_Model extends Vtiger_Base_Model {

	/**
	 * Function returns duplicate record status of the module
	 * @return true if duplicate records exists else false
	 */
	public function checkDuplicate() {
		$db = PearDatabase::getInstance();
		$folderName = $this->getName();
		$folderId = $this->getId();
		//added folder id check to support folder edit feature
		$result = $db->pquery("SELECT 1 FROM vtiger_attachmentsfolder WHERE foldername = ? AND folderid != ?", array($folderName, $folderId));
		$num_rows = $db->num_rows($result);
		if ($num_rows > 0) {
			return true;
		}
		return false;
	}

	/**
	 * Function returns whether documents are exist or not in that folder
	 * @return true if exists else false
	 */
	public function hasDocuments() {
		$db = PearDatabase::getInstance();
		$folderId = $this->getId();

		$result = $db->pquery("SELECT 1 FROM vtiger_notes
						INNER JOIN vtiger_attachmentsfolder ON vtiger_attachmentsfolder.folderid = vtiger_notes.folderid
						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
						WHERE vtiger_attachmentsfolder.folderid = ?
						AND vtiger_attachmentsfolder.foldername != 'Default'
						AND vtiger_crmentity.deleted = 0", array($folderId));
		$num_rows = $db->num_rows($result);
		if ($num_rows>0) {
			return true;
		}
		return false;
	}

	/**
	 * Function to add the new folder
	 * @return Documents_Folder_Model
	 */
	public function save() {
		$db = PearDatabase::getInstance();
		$folderName = $this->getName();
		$folderDesc = $this->get('description');

		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$currentUserId = $currentUserModel->getId();

		if($this->get('mode') != 'edit') {
			$result = $db->pquery("SELECT max(sequence) AS max, max(folderid) AS max_folderid FROM vtiger_attachmentsfolder", array());
			$sequence = $db->query_result($result, 0, 'max') + 1;
			$folderId = $db->query_result($result,0,'max_folderid') + 1;
			$params = array($folderId,$folderName, $folderDesc, $currentUserId, $sequence);

			$db->pquery("INSERT INTO vtiger_attachmentsfolder(folderid,foldername, description, createdby, sequence) VALUES(?, ?, ?, ?, ?)", $params);

			$this->set('sequence', $sequence);
			$this->set('createdby', $currentUserId);
			$this->set('folderid',$folderId);
		} else {
			$db->pquery('UPDATE vtiger_attachmentsfolder SET foldername=?, description=? WHERE folderid=?', array($folderName, $folderDesc, $this->getId()));
		}

		return $this;
	}

	/**
	 * Function to delete existing folder.
	 * Xóa sharing trước, sau đó xóa folder. Chỉ xóa khi folder không có document (gọi từ action đã kiểm tra).
	 * @return Documents_Folder_Model
	 */
	public function delete() {
		$db = PearDatabase::getInstance();
		$folderId = (int) $this->getId();
		if ($folderId <= 0) return $this;

		$folder = $db->pquery("SELECT foldername FROM vtiger_attachmentsfolder WHERE folderid = ?", array($folderId));
		if ($db->num_rows($folder) === 0) return $this;

		$foldername = $db->query_result($folder, 0, 'foldername');
		if ($foldername === 'Default' || $foldername === 'Google Drive' || $foldername === 'Dropbox') {
			return $this;
		}

		$t = $db->pquery("SHOW TABLES LIKE 'vtiger_documentfolder_sharing'", array());
		if ($db->num_rows($t) > 0) {
			$db->pquery("DELETE FROM vtiger_documentfolder_sharing WHERE folderid = ?", array($folderId));
		}

		$db->pquery("DELETE FROM vtiger_attachmentsfolder WHERE folderid = ?", array($folderId));
		return $this;
	}

	/**
	 * Function return an instance of Folder Model
	 * @return Documents_Folder_Model
	 */
	public static function getInstance() {
		return new self();
	}

	/**
	 * Function returns an instance of Folder Model
	 * @param foldername
	 * @return Documents_Folder_Model
	 */
	public static function getInstanceById($folderId) {
		$db = PearDatabase::getInstance();
		$folderModel = Documents_Folder_Model::getInstance();

		$result = $db->pquery("SELECT * FROM vtiger_attachmentsfolder WHERE folderid = ?", array($folderId));
		$num_rows = $db->num_rows($result);
		if ($num_rows > 0) {
			$values = $db->query_result_rowdata($result, 0);
			$folderModel->setData($values);
		}
		return $folderModel;
	}

	/**
	 * Function returns an instance of Folder Model
	 * @param <Array> row
	 * @return Documents_Folder_Model
	 */
	public static function getInstanceByArray($row) {
		$folderModel = Documents_Folder_Model::getInstance();
		return $folderModel->setData($row);
	}

	/**
	 * Function returns Folder's Delete url
	 * @return <String> - Delete Url
	 */
	public function getDeleteUrl() {
		$folderName = $this->getName();
		return "index.php?module=Documents&action=Folder&mode=delete&foldername=$folderName";
	}

	/**
	 * Function to get the id of the folder
	 * @return <Number>
	 */
	public function getId() {
		return $this->get('folderid');
	}

	/**
	 * Function to get the name of the folder
	 * @return <String>
	 */
	public function getName() {
		return $this->get('foldername');
	}

	/**
	 * Function to get the description of the folder
	 * @return <String>
	 */
	function getDescription() {
		return $this->get('description');
	}

	/**
	 * Function to get info array while saving a folder
	 * @return Array  info array
	 */
	public function getInfoArray() {
		return array(
			'folderName'=> $this->getName(),
			'folderid'	=> $this->getId(),
			'folderDesc'=> $this->getDescription()
		);
	}

	/**
	 * Kiểm tra user/group có quyền xem folder không.
	 * Admin: luôn có quyền. Folder Default: tất cả. Folder khác: creator + shared users/groups.
	 */
	public static function userCanAccessFolder($folderId, $userId) {
		$userModel = Users_Record_Model::getCurrentUserModel();
		if ($userModel->isAdminUser()) return true;

		$db = PearDatabase::getInstance();
		$t = $db->pquery("SHOW TABLES LIKE 'vtiger_documentfolder_sharing'", array());
		if ($db->num_rows($t) === 0) return true;
		$folder = $db->pquery("SELECT folderid, foldername, createdby FROM vtiger_attachmentsfolder WHERE folderid = ?", array($folderId));
		if ($db->num_rows($folder) === 0) return false;

		$foldername = $db->query_result($folder, 0, 'foldername');
		$createdby = $db->query_result($folder, 0, 'createdby');
		if ($foldername === 'Default') return true;
		if ((int)$createdby === (int)$userId) return true;

		$groupIds = Vtiger_Util_Helper::getGroupsIdsForUsers($userId);

		$sh = $db->pquery("SELECT sharetype, shareid FROM vtiger_documentfolder_sharing WHERE folderid = ?", array($folderId));
		for ($i = 0; $i < $db->num_rows($sh); $i++) {
			$st = $db->query_result($sh, $i, 'sharetype');
			$sid = (int) $db->query_result($sh, $i, 'shareid');
			if ($st === 'Users' && $sid === (int)$userId) return true;
			if ($st === 'Groups' && in_array($sid, $groupIds)) return true;
		}
		return false;
	}

	/**
	 * Lấy danh sách folder user được phép xem
	 */
	public static function getAccessibleFolders() {
		$db = PearDatabase::getInstance();
		$t = $db->pquery("SHOW TABLES LIKE 'vtiger_documentfolder_sharing'", array());
		if ($db->num_rows($t) === 0) {
			return self::getAllFoldersStatic();
		}
		$all = self::getAllFoldersStatic();
		$userModel = Users_Record_Model::getCurrentUserModel();
		if ($userModel->isAdminUser()) return $all;

		$result = array();
		foreach ($all as $f) {
			if (self::userCanAccessFolder($f->getId(), $userModel->getId())) {
				$result[] = $f;
			}
		}
		return $result;
	}

	public static function getAllFoldersStatic() {
		$db = PearDatabase::getInstance();
		$res = $db->pquery('SELECT * FROM vtiger_attachmentsfolder ORDER BY sequence', array());
		$list = array();
		for ($i = 0; $i < $db->num_rows($res); $i++) {
			$list[] = Documents_Folder_Model::getInstanceByArray($db->query_result_rowdata($res, $i));
		}
		return $list;
	}

	/**
	 * Lưu sharing: shared_user_ids, shared_group_ids
	 */
	public function saveSharing($sharedUserIds = array(), $sharedGroupIds = array()) {
		$db = PearDatabase::getInstance();
		$t = $db->pquery("SHOW TABLES LIKE 'vtiger_documentfolder_sharing'", array());
		if ($db->num_rows($t) === 0) return $this;
		$fid = $this->getId();
		$db->pquery("DELETE FROM vtiger_documentfolder_sharing WHERE folderid = ?", array($fid));
		foreach ((array)$sharedUserIds as $uid) {
			if ((int)$uid > 0) {
				$db->pquery("INSERT INTO vtiger_documentfolder_sharing (folderid, sharetype, shareid) VALUES (?, 'Users', ?)", array($fid, (int)$uid));
			}
		}
		foreach ((array)$sharedGroupIds as $gid) {
			if ((int)$gid > 0) {
				$db->pquery("INSERT INTO vtiger_documentfolder_sharing (folderid, sharetype, shareid) VALUES (?, 'Groups', ?)", array($fid, (int)$gid));
			}
		}
		return $this;
	}

	/**
	 * Lấy sharing hiện tại
	 */
	public function getSharing() {
		$db = PearDatabase::getInstance();
		$t = $db->pquery("SHOW TABLES LIKE 'vtiger_documentfolder_sharing'", array());
		if ($db->num_rows($t) === 0) return array('users' => array(), 'groups' => array());
		$res = $db->pquery("SELECT sharetype, shareid FROM vtiger_documentfolder_sharing WHERE folderid = ?", array($this->getId()));
		$users = array(); $groups = array();
		for ($i = 0; $i < $db->num_rows($res); $i++) {
			$st = $db->query_result($res, $i, 'sharetype');
			$sid = $db->query_result($res, $i, 'shareid');
			if ($st === 'Users') $users[] = $sid;
			else $groups[] = $sid;
		}
		return array('users' => $users, 'groups' => $groups);
	}

}
?>
