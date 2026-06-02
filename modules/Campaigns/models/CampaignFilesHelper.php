<?php
/**
 * Campaign description files via vtiger attachment storage (vtiger_attachments + vtiger_seattachmentsrel).
 * NOT Documents module UI; NOT custom folders/tables.
 */

class Campaigns_CampaignFiles_Helper {

	const SETYPE = 'Campaigns Attachment';

	protected static $allowed = array(
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png' => 'image/png',
		'doc' => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'xls' => 'application/vnd.ms-excel',
		'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
	);

	/** Explicit deny even if upload tricks MIME/browser (complements vtiger sanitizeUploadFileName). */
	protected static $blockedExtensions = array(
		'php', 'phtml', 'php3', 'php4', 'php5', 'phar',
		'js', 'jsp', 'jsx',
		'html', 'htm', 'xhtml',
		'exe', 'bat', 'cmd', 'com', 'dll', 'msi', 'scr',
		'sh', 'cgi', 'pl', 'py',
		'asp', 'aspx',
	);

	public static function isBlockedExtension($filename) {
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		return $ext !== '' && in_array($ext, self::$blockedExtensions, true);
	}

	public static function isAllowedExtension($filename) {
		if (self::isBlockedExtension($filename)) {
			return false;
		}
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		return $ext !== '' && isset(self::$allowed[$ext]);
	}

	public static function mimeForExtension($filename) {
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		return isset(self::$allowed[$ext]) ? self::$allowed[$ext] : 'application/octet-stream';
	}

	public static function isImageFilename($filename) {
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		return in_array($ext, array('jpg', 'jpeg', 'png'), true);
	}

	/**
	 * Process $_FILES['campaign_files'] after Campaign entity save (save_module).
	 */
	public static function processUploads($campaignId) {
		global $upload_badext, $current_user;

		$campaignId = (int) $campaignId;
		if ($campaignId <= 0 || empty($_FILES['campaign_files'])) {
			return;
		}

		if (!Users_Privileges_Model::isPermitted('Campaigns', 'EditView', $campaignId)) {
			return;
		}

		$db = PearDatabase::getInstance();
		$resOwn = $db->pquery(
			'SELECT smownerid FROM vtiger_crmentity WHERE crmid = ? AND deleted = 0',
			array($campaignId)
		);
		$ownerid = ($resOwn && $db->num_rows($resOwn) > 0) ? (int) $db->query_result($resOwn, 0, 'smownerid') : (int) $current_user->id;
		if ($ownerid <= 0) {
			$ownerid = (int) $current_user->id;
		}

		$files = self::normalizeCampaignFilesArray($_FILES['campaign_files']);
		foreach ($files as $file) {
			self::saveOneAttachment($campaignId, $file, $ownerid);
		}
	}

	/**
	 * Mirror CRMEntity::uploadAndSaveFile inserts without deleting prior attachments (multi-file).
	 */
	protected static function saveOneAttachment($campaignId, array $file_details, $ownerid) {
		global $adb, $current_user, $upload_badext;
		if (empty($adb)) {
			$adb = PearDatabase::getInstance();
		}

		if (empty($file_details['tmp_name']) || !is_uploaded_file($file_details['tmp_name'])) {
			return false;
		}

		$file_name = isset($file_details['name']) ? $file_details['name'] : 'file';
		$binFile = sanitizeUploadFileName($file_name, $upload_badext);
		if ($binFile === '' || !self::isAllowedExtension($binFile)) {
			return false;
		}

		$filename = ltrim(basename(' ' . $binFile));
		$filetmp_name = $file_details['tmp_name'];
		$filetype = !empty($file_details['type']) ? $file_details['type'] : '';
		if ($filetype === '' && function_exists('mime_content_type')) {
			$detected = @mime_content_type($filetmp_name);
			if (is_string($detected) && $detected !== '') {
				$filetype = $detected;
			}
		}
		if ($filetype === '') {
			$filetype = self::mimeForExtension($filename);
		}

		$date_var = date('Y-m-d H:i:s');
		$current_id = $adb->getUniqueID('vtiger_crmentity');
		$upload_file_path = decideFilePath();
		$encryptFileName = Vtiger_Util_Helper::getEncryptedFileName($binFile);

		$destPath = $upload_file_path . $current_id . '_' . $encryptFileName;
		$upload_status = @copy($filetmp_name, $destPath);
		if (!$upload_status) {
			return false;
		}

		$desc = '';
		$sql1 = 'INSERT INTO vtiger_crmentity (crmid,smcreatorid,smownerid,setype,description,createdtime,modifiedtime) VALUES (?,?,?,?,?,?,?)';
		$params1 = array(
			$current_id,
			$current_user->id,
			$ownerid,
			self::SETYPE,
			$desc,
			$adb->formatDate($date_var, true),
			$adb->formatDate($date_var, true),
		);
		$adb->pquery($sql1, $params1);

		$sql2 = 'INSERT INTO vtiger_attachments(attachmentsid, name, description, type, path, storedname) VALUES(?,?,?,?,?,?)';
		$params2 = array($current_id, $filename, $desc, $filetype, $upload_file_path, $encryptFileName);
		$adb->pquery($sql2, $params2);

		$sql3 = 'INSERT INTO vtiger_seattachmentsrel(crmid, attachmentsid) VALUES(?,?)';
		$adb->pquery($sql3, array($campaignId, $current_id));

		return true;
	}

	protected static function normalizeCampaignFilesArray(array $f) {
		$out = array();
		if (isset($f[0]) && is_array($f[0]) && array_key_exists('tmp_name', $f[0])) {
			foreach ($f as $item) {
				if (is_array($item) && !empty($item['tmp_name']) && isset($item['error']) && $item['error'] === UPLOAD_ERR_OK) {
					$out[] = $item;
				}
			}
			return $out;
		}
		if (!isset($f['name'])) {
			return $out;
		}
		if (!is_array($f['name'])) {
			if (!empty($f['tmp_name']) && isset($f['error']) && $f['error'] === UPLOAD_ERR_OK) {
				$out[] = $f;
			}
			return $out;
		}
		$n = count($f['name']);
		for ($i = 0; $i < $n; $i++) {
			if (isset($f['error'][$i]) && $f['error'][$i] === UPLOAD_ERR_OK && !empty($f['tmp_name'][$i])) {
				$out[] = array(
					'name' => $f['name'][$i],
					'type' => isset($f['type'][$i]) ? $f['type'][$i] : '',
					'tmp_name' => $f['tmp_name'][$i],
					'error' => $f['error'][$i],
					'size' => isset($f['size'][$i]) ? $f['size'][$i] : 0,
				);
			}
		}
		return $out;
	}

	public static function getFilesForCampaign($campaignId) {
		$campaignId = (int) $campaignId;
		if ($campaignId <= 0) {
			return array();
		}
		$db = PearDatabase::getInstance();
		$query = 'SELECT vtiger_attachments.attachmentsid, vtiger_attachments.name, vtiger_attachments.type
			FROM vtiger_seattachmentsrel
			INNER JOIN vtiger_attachments ON vtiger_attachments.attachmentsid = vtiger_seattachmentsrel.attachmentsid
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_attachments.attachmentsid
				AND vtiger_crmentity.deleted = 0 AND vtiger_crmentity.setype = ?
			INNER JOIN vtiger_campaign ON vtiger_campaign.campaignid = vtiger_seattachmentsrel.crmid
			INNER JOIN vtiger_crmentity campce ON campce.crmid = vtiger_campaign.campaignid AND campce.deleted = 0
			WHERE vtiger_seattachmentsrel.crmid = ?
			ORDER BY vtiger_crmentity.createdtime ASC, vtiger_attachments.attachmentsid ASC';
		$res = $db->pquery($query, array(self::SETYPE, $campaignId));
		$list = array();
		$rows = $db->num_rows($res);
		for ($i = 0; $i < $rows; $i++) {
			$aid = (int) $db->query_result($res, $i, 'attachmentsid');
			$name = $db->query_result($res, $i, 'name');
			$list[] = array(
				'id' => $aid,
				'original_name' => $name,
				'is_image' => self::isImageFilename($name),
			);
		}
		return $list;
	}

	/**
	 * Load attachment row linked to a Campaign (permission checked by caller).
	 */
	public static function getAttachmentForDownload($attachmentsId) {
		$attachmentsId = (int) $attachmentsId;
		if ($attachmentsId <= 0) {
			return null;
		}
		$db = PearDatabase::getInstance();
		$query = 'SELECT vtiger_attachments.*, vtiger_seattachmentsrel.crmid AS campaignid
			FROM vtiger_attachments
			INNER JOIN vtiger_seattachmentsrel ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_attachments.attachmentsid
				AND vtiger_crmentity.deleted = 0 AND vtiger_crmentity.setype = ?
			INNER JOIN vtiger_campaign ON vtiger_campaign.campaignid = vtiger_seattachmentsrel.crmid
			INNER JOIN vtiger_crmentity campce ON campce.crmid = vtiger_campaign.campaignid AND campce.deleted = 0
			WHERE vtiger_attachments.attachmentsid = ?';
		$res = $db->pquery($query, array(self::SETYPE, $attachmentsId));
		if (!$res || $db->num_rows($res) < 1) {
			return null;
		}
		return $db->fetchByAssoc($res, 0);
	}

	public static function getAttachmentFilesystemPath(array $attRow) {
		if (empty($attRow['path']) || empty($attRow['attachmentsid']) || empty($attRow['storedname'])) {
			return null;
		}
		$path = $attRow['path'];
		if (substr($path, -1) !== '/' && substr($path, -1) !== '\\') {
			$path .= DIRECTORY_SEPARATOR;
		}
		return $path . $attRow['attachmentsid'] . '_' . $attRow['storedname'];
	}
}
