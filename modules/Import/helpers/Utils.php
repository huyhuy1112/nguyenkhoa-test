<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */
//required for auto detecting file endings for files create in mac
if (version_compare(PHP_VERSION, '8.1.0') <= 0) {
	ini_set("auto_detect_line_endings", true);
}

class Import_Utils_Helper {

	static $AUTO_MERGE_NONE = 0;
	static $AUTO_MERGE_IGNORE = 1;
	static $AUTO_MERGE_OVERWRITE = 2;
	static $AUTO_MERGE_MERGEFIELDS = 3;

	static $supportedFileEncoding = array(
		'UTF-8' => 'UTF-8',
		'ISO-8859-1' => 'ISO-8859-1',
		'CP1258' => 'CP1258',
	);
	static $supportedDelimiters = array(','=>'comma', ';'=>'semicolon', '|'=> 'Pipe', '^'=>'Caret');
	static $supportedFileExtensions = array('csv','vcf');

	public static function getSupportedFileExtensions() {
		return self::$supportedFileExtensions;
	}

	public static function getSupportedFileEncoding() {
		return self::$supportedFileEncoding;
	}

	public static function getSupportedDelimiters() {
		return self::$supportedDelimiters;
	}

	public static function getAutoMergeTypes($moduleName) {
		$mergeTypes = array(self::$AUTO_MERGE_IGNORE => 'Skip');
		if (Users_Privileges_Model::isPermitted($moduleName, 'EditView')) {
			$mergeTypes[self::$AUTO_MERGE_OVERWRITE]		= 'Overwrite';
			$mergeTypes[self::$AUTO_MERGE_MERGEFIELDS]	= 'Merge';
		}
		return $mergeTypes;
	}

	public static function getMaxUploadSize() {
		global $upload_maxsize;
		return $upload_maxsize;
	}

	public static function getImportDirectory() {
		global $import_dir;
		$importDir = dirname(__FILE__). '/../../../'.$import_dir;
		return $importDir;
	}

	public static function getImportFilePath($user) {
		$importDirectory = self::getImportDirectory();
		return $importDirectory. "IMPORT_".$user->id;
	}


	public static function getFileReaderInfo($type) {
		$configReader = new Import_Config_Model();
		$importTypeConfig = $configReader->get('importTypes');
		if(isset($importTypeConfig[$type])) {
			return $importTypeConfig[$type];
		}
		return null;
	}

	public static function getFileReader($request, $user) {
		$fileReaderInfo = self::getFileReaderInfo($request->get('type'));
		if(!empty($fileReaderInfo)) {
			require_once $fileReaderInfo['classpath'];
			$fileReader = new $fileReaderInfo['reader'] ($request, $user);
		} else {
			$fileReader = null;
		}
		return $fileReader;
	}

	public static function getDbTableName($user) {
		$configReader = new Import_Config_Model();
		$userImportTablePrefix = $configReader->get('userImportTablePrefix');

		$tableName = $userImportTablePrefix;
		if(method_exists($user, 'getId')){
			$tableName .= $user->getId();
		} else {
			$tableName .= $user->id;
		}
		return $tableName;
	}

	public static function showErrorPage($errorMessage, $errorDetails=false, $customActions=false) {
		$viewer = new Vtiger_Viewer();

		$viewer->assign('ERROR_MESSAGE', $errorMessage);
		$viewer->assign('ERROR_DETAILS', $errorDetails);
		$viewer->assign('CUSTOM_ACTIONS', $customActions);
		$viewer->assign('MODULE','Import');

		$viewer->view('ImportError.tpl', 'Import');
	}

	public static function showImportLockedError($lockInfo) {
		$moduleName = getTabModuleName($lockInfo['tabid']);
		$userName = getUserFullName($lockInfo['userid']);
		$errorMessage = sprintf("%s is importing %s. Please try after some time.",$userName, $moduleName);
		self::showErrorPage($errorMessage);
	}

	public static function showImportTableBlockedError($moduleName, $user) {

		$errorMessage = vtranslate('ERR_UNIMPORTED_RECORDS_EXIST', 'Import');
		$customActions = array('LBL_CLEAR_DATA' => "location.href='index.php?module={$moduleName}&view=Import&mode=clearCorruptedData'");

		self::showErrorPage($errorMessage, '', $customActions);
	}

	public static function isUserImportBlocked($user) {
		$adb = PearDatabase::getInstance();
		$tableName = self::getDbTableName($user);

		if(Vtiger_Utils::CheckTable($tableName)) {
			$result = $adb->pquery('SELECT 1 FROM '.$tableName.' WHERE status = ?',  array(Import_Data_Action::$IMPORT_RECORD_NONE));
			if($adb->num_rows($result) > 0) {
				return true;
			}
		}
		return false;
	}

	public static function clearUserImportInfo($user) {
		$adb = PearDatabase::getInstance();
		$tableName = self::getDbTableName($user);

		$adb->pquery('DROP TABLE IF EXISTS '.$tableName, array());
		Import_Lock_Action::unLock($user);
		Import_Queue_Action::removeForUser($user);
	}

	public static function getAssignedToUserList($module) {
		$current_user = Users_Record_Model::getCurrentUserModel();
		$cache = Vtiger_Cache::getInstance();
		if($cache->getUserList($module,$current_user->id)){
			return $cache->getUserList($module,$current_user->id);
		} else {
			$userList = get_user_array(FALSE, "Active", $current_user->id);
			$cache->setUserList($module,$userList,$current_user->id);
			return $userList;
		}
	}

	public static function getAssignedToGroupList($module) {
		$current_user = Users_Record_Model::getCurrentUserModel();
		$cache = Vtiger_Cache::getInstance();
		if($cache->getGroupList($module,$current_user->id)){
			return $cache->getGroupList($module,$current_user->id);
		} else {
			$groupList = get_group_array(FALSE, "Active", $current_user->id);
			$cache->setGroupList($module,$groupList,$current_user->id);
			return $groupList;
		}
	}

	public static function hasAssignPrivilege($moduleName, $assignToUserId) {
		$assignableUsersList = self::getAssignedToUserList($moduleName);
		if(array_key_exists($assignToUserId, $assignableUsersList)) {
			return true;
		}
		$assignableGroupsList = self::getAssignedToGroupList($moduleName);
		if(array_key_exists($assignToUserId, $assignableGroupsList)) {
			return true;
		}
		return false;
	}

	public static function validateFileUpload($request) {
		$current_user = Users_Record_Model::getCurrentUserModel();

		$uploadMaxSize = self::getMaxUploadSize();
		$importDirectory = self::getImportDirectory();
		$temporaryFileName = self::getImportFilePath($current_user);

		// Make repeated imports resilient: ensure import directory exists and stale temp file is removed.
		if (!file_exists($importDirectory)) {
			@mkdir($importDirectory, 0777, true);
		}
		@unlink($temporaryFileName);

		if($_FILES['import_file']['error']) {
			$request->set('error_message', self::fileUploadErrorMessage($_FILES['import_file']['error']));
			return false;
		}
		if(!is_uploaded_file($_FILES['import_file']['tmp_name'])) {
			$request->set('error_message', vtranslate('LBL_FILE_UPLOAD_FAILED', 'Import'));
			return false;
		}
		if ($_FILES['import_file']['size'] > $uploadMaxSize) {
			$request->set('error_message', vtranslate('LBL_IMPORT_ERROR_LARGE_FILE', 'Import').
												 $uploadMaxSize.' '.vtranslate('LBL_IMPORT_CHANGE_UPLOAD_SIZE', 'Import'));
			return false;
		}
		if(!is_writable($importDirectory)) {
			$request->set('error_message', vtranslate('LBL_IMPORT_DIRECTORY_NOT_WRITABLE', 'Import').' ('.$importDirectory.')');
			return false;
		}

		if ($request->get('type') == "ics" || $request->get('type') == "vcf") {
			$fileCopied = move_uploaded_file($_FILES['import_file']['tmp_name'], $temporaryFileName);
		} else {
			require_once 'modules/Import/helpers/ExcelConverter.php';
			$uploadedPath = $_FILES['import_file']['tmp_name'];
			$uploadedName = $_FILES['import_file']['name'];
			if (Import_ExcelConverter_Helper::isExcelUpload($uploadedName)) {
				$csvTemp = $uploadedPath . '.csv';
				$converted = Import_ExcelConverter_Helper::convertToCsv(
					$uploadedPath,
					$csvTemp,
					$request->get('delimiter') ? $request->get('delimiter') : ','
				);
				if (!$converted) {
					$request->set('error_message', vtranslate('LBL_INVALID_FILE', 'Import'));
					return false;
				}
				$request->set('type', 'csv');
				$detectedEncoding = self::neutralizeAndMoveFile(
					$csvTemp,
					$temporaryFileName,
					$request->get('delimiter') ? $request->get('delimiter') : ','
				);
				$fileCopied = ($detectedEncoding !== false);
				if ($fileCopied) {
					$request->set('file_encoding', 'UTF-8');
				}
				@unlink($csvTemp);
			} else {
				$detectedEncoding = self::neutralizeAndMoveFile(
					$uploadedPath,
					$temporaryFileName,
					$request->get('delimiter') ? $request->get('delimiter') : ','
				);
				$fileCopied = ($detectedEncoding !== false);
				if ($fileCopied) {
					$request->set('file_encoding', 'UTF-8');
				}
			}
		}
		if(!$fileCopied) {
			$request->set('error_message', vtranslate('LBL_IMPORT_FILE_COPY_FAILED', 'Import'));
			return false;
		}
		$fileReader = Import_Utils_Helper::getFileReader($request, $current_user);

		if($fileReader == null) {
			$request->set('error_message', vtranslate('LBL_INVALID_FILE', 'Import'));
			return false;
		}

		$hasHeader = $fileReader->hasHeader();
		$firstRow = $fileReader->getFirstRowData($hasHeader);
		if($firstRow === false) {
			$request->set('error_message', vtranslate('LBL_NO_ROWS_FOUND', 'Import'));
			return false;
		}
		return true;
	}

	/**
	 * To remove carriage return(\r) in end of every line and make the file neutral
	 * @param type $uploadedFileName
	 * @param type $temporaryFileName
	 * @return boolean
	 */
	public static function resolveCsvEncodingAlias($encoding) {
		$map = array(
			'WINDOWS-1258' => 'Windows-1258',
			'Windows-1258' => 'Windows-1258',
			'CP1258' => 'Windows-1258',
			'Windows-1252' => 'Windows-1252',
			'ISO-8859-1' => 'ISO-8859-1',
		);
		return isset($map[$encoding]) ? $map[$encoding] : $encoding;
	}

	public static function decodeCsvSample($sample, $encoding) {
		if (!is_string($sample) || $sample === '') {
			return null;
		}
		$encoding = self::resolveCsvEncodingAlias($encoding);
		if ($encoding === 'UTF-8') {
			if (function_exists('mb_check_encoding') && !mb_check_encoding($sample, 'UTF-8')) {
				return null;
			}
			return $sample;
		}
		$iconvFrom = ($encoding === 'Windows-1258') ? 'CP1258' : $encoding;
		if (function_exists('iconv')) {
			$converted = @iconv($iconvFrom, 'UTF-8//IGNORE', $sample);
			if ($converted !== false) {
				return $converted;
			}
		}
		if (function_exists('mb_convert_encoding')) {
			$mbFrom = $encoding;
			if ($encoding === 'Windows-1258') {
				$mbFrom = 'ISO-8859-1';
			}
			$converted = @mb_convert_encoding($sample, 'UTF-8', $mbFrom);
			if ($converted !== false) {
				return $converted;
			}
		}
		return null;
	}

	public static function scoreVietnameseText($text) {
		if (!is_string($text) || $text === '') {
			return 0;
		}
		$questionMarks = substr_count($text, '?');
		$replacementChars = substr_count($text, "\xEF\xBF\xBD");
		$vietChars = 0;
		if (preg_match_all('/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđÀÁẠẢÃÂẦẤẬẨẪĂẰẮẶẲẴÈÉẸẺẼÊỀẾỆỂỄÌÍỊỈĨÒÓỌỎÕÔỒỐỘỔỖƠỜỚỢỞỠÙÚỤỦŨƯỪỨỰỬỮỲÝỴỶỸĐ]/u', $text, $matches)) {
			$vietChars = count($matches[0]);
		}
		return ($vietChars * 4) - ($questionMarks * 3) - ($replacementChars * 6);
	}

	public static function detectCsvEncoding($filePath) {
		$sample = @file_get_contents($filePath, false, null, 0, 262144);
		if ($sample === false || $sample === '') {
			return 'UTF-8';
		}
		if (substr($sample, 0, 3) === "\xEF\xBB\xBF") {
			return 'UTF-8';
		}

		$candidates = array('UTF-8', 'Windows-1258', 'Windows-1252', 'ISO-8859-1');
		$bestEncoding = 'ISO-8859-1';
		$bestScore = -999999;

		foreach ($candidates as $encoding) {
			$decoded = self::decodeCsvSample($sample, $encoding);
			if ($decoded === null) {
				continue;
			}
			$repaired = self::repairVietnameseExportText($decoded);
			$score = self::scoreVietnameseText($repaired);
			if ($score > $bestScore) {
				$bestScore = $score;
				$bestEncoding = $encoding;
			}
		}

		return self::resolveCsvEncodingAlias($bestEncoding);
	}

	/**
	 * Repair common Vietnamese text loss from vtiger Latin-1/Windows CSV exports (? placeholders).
	 */
	public static function repairVietnameseExportText($text) {
		if (!is_string($text) || $text === '' || strpos($text, '?') === false) {
			return $text;
		}

		static $rules = null;
		if ($rules === null) {
			$rules = array(
				'TH??NG M?I' => 'THƯƠNG MẠI',
				'TH??NG' => 'THƯƠNG',
				'S?N XU??T' => 'SẢN XUẤT',
				'S?N XU?T' => 'SẢN XUẤT',
				'XU??T' => 'XUẤT',
				'XU?T' => 'XUẤT',
				'K? THU??T' => 'KỸ THUẬT',
				'K? THU?T' => 'KỸ THUẬT',
				'C??NG NGH??' => 'CÔNG NGHỆ',
				'C?NG NGH?' => 'CÔNG NGHỆ',
				'C? PH??N' => 'CỔ PHẦN',
				'C? PH?N' => 'CỔ PHẦN',
				'GI??I PHÁP' => 'GIẢI PHÁP',
				'GI?I PHÁP' => 'GIẢI PHÁP',
				'??U T??' => 'ĐẦU TƯ',
				'??U T?' => 'ĐẦU TƯ',
				'?NG D?NG' => 'ỨNG DỤNG',
				'VÀ D?NG' => 'VÀ DỤNG',
				'XÂY D?NG' => 'XÂY DỰNG',
				' D?NG' => ' DỰNG',
				'NGHI??P' => 'NGHIỆP',
				'NGHI?P' => 'NGHIỆP',
				'CH??NG' => 'CHỐNG',
				'CH?NG' => 'CHỐNG',
				'LIÊN K?T' => 'LIÊN KẾT',
				'LIÊN K??T' => 'LIÊN KẾT',
				'TO??N C???U' => 'TOÀN CẦU',
				'TO?N C?U' => 'TOÀN CẦU',
				'??I H???NG' => 'ĐẠI HỒNG',
				'??I H?NG' => 'ĐẠI HỒNG',
				'??I H?NG PHÁT' => 'ĐẠI HỒNG PHÁT',
				'C? KHÍ' => 'CƠ KHÍ',
				'LO?I' => 'LOẠI',
				'MI?N' => 'MIỀN',
				'Vi??t Nam' => 'Việt Nam',
				'Vi?t Nam' => 'Việt Nam',
				'Ph??ng ??c Nhu?n' => 'Phường Đốc Nhuận',
				'Phan ??ng L?u' => 'Phan Đăng Lưu',
				'??ng L?u' => 'Đăng Lưu',
				'Ph??ng' => 'Phường',
				'??c Nhu?n' => 'Đốc Nhuận',
				'Thành ph? H? Chí Minh' => 'Thành phố Hồ Chí Minh',
				'Th??nh ph?' => 'Thành phố',
				'Thành ph?' => 'Thành phố',
				'H? Chí' => 'Hồ Chí',
				'??i?n Bi?n' => 'Điện Biên',
				'??i?n' => 'Điện',
				'???ng s?' => 'Đường số',
				'???ng' => 'Đường',
				'H?? N?I' => 'HÀ NỘI',
				'H? N?I' => 'HÀ NỘI',
				'HÀ N?I' => 'HÀ NỘI',
				'R??U' => 'RƯỢU',
				'R??U ' => 'RƯỢU ',
				'VI?N' => 'VIỆN',
				'VI?T NAM' => 'VIỆT NAM',
				'V? TR?' => 'VŨ TRỤ',
				'V? TR? ' => 'VŨ TRỤ ',
				'D?CH V?' => 'DỊCH VỤ',
				'VÀ D?CH V?' => 'VÀ DỊCH VỤ',
				'D? ÁN' => 'DỰ ÁN',
				'D? án' => 'Dự án',
				'C?A' => 'CỦA',
				'C?A PH?P' => 'CỦA PHÁP',
				'PH?P' => 'PHÁP',
				'M?T THÀNH VIÊN' => 'MỘT THÀNH VIÊN',
				'TR??NG ??I H?C' => 'TRƯỜNG ĐẠI HỌC',
				'??I H?C' => 'ĐẠI HỌC',
				'QU?C T?' => 'QUỐC TẾ',
				'QU?C' => 'QUỐC',
				'S? LOBI' => 'SÀ LOBI',
				'MR. QU?' => 'MR. QUÍ',
				'QU?' => 'QUÍ',
				'TOÀN C?U' => 'TOÀN CẦU',
				'?p Trung' => 'Ấp Trung',
				'B?? ?i?n' => 'Bà Điển',
			);
			uksort($rules, function ($a, $b) {
				return strlen($b) - strlen($a);
			});
		}

		foreach ($rules as $broken => $fixed) {
			if (strpos($text, $broken) !== false) {
				$text = str_replace($broken, $fixed, $text);
			}
		}
		return $text;
	}

	public static function normalizeAndConvertCsvCell($cell, $sourceEncoding) {
		$cell = self::normalizeCsvCell($cell);
		if ($sourceEncoding !== 'UTF-8') {
			$cell = self::convertCellEncoding($cell, $sourceEncoding, 'UTF-8');
		}
		return self::repairVietnameseExportText($cell);
	}

	public static function convertCellEncoding($value, $fromCharset, $toCharset = 'UTF-8') {
		if (!is_string($value) || $value === '' || strcasecmp($fromCharset, $toCharset) === 0) {
			return $value;
		}
		$fromCharset = self::resolveCsvEncodingAlias($fromCharset);
		$iconvFrom = ($fromCharset === 'Windows-1258') ? 'CP1258' : $fromCharset;
		if (function_exists('iconv')) {
			$converted = @iconv($iconvFrom, $toCharset . '//IGNORE', $value);
			if ($converted !== false) {
				return $converted;
			}
		}
		if (function_exists('mb_convert_encoding')) {
			$mbFrom = $fromCharset;
			if ($fromCharset === 'Windows-1258') {
				$mbFrom = 'ISO-8859-1';
			}
			$converted = @mb_convert_encoding($value, $toCharset, $mbFrom);
			if ($converted !== false) {
				return $converted;
			}
		}
		return $value;
	}

	public static function normalizeCsvCell($cell) {
		$cell = preg_replace('/^\xEF\xBB\xBF/', '', (string)$cell);
		$cell = trim($cell);
		if (strlen($cell) >= 2 && $cell[0] === '"' && substr($cell, -1) === '"') {
			$cell = substr($cell, 1, -1);
		}
		return $cell;
	}

	public static function neutralizeAndMoveFile($uploadedFileName, $temporaryFileName, $delimiter = ','){
		$sourceEncoding = self::detectCsvEncoding($uploadedFileName);
		$file_read = fopen($uploadedFileName, 'r');
		$file_write = fopen($temporaryFileName, 'w+');
		if (!$file_read || !$file_write) {
			if ($file_read) {
				fclose($file_read);
			}
			if ($file_write) {
				fclose($file_write);
			}
			return false;
		}
		// UTF-8 marker helps Excel/other tools; read side strips if needed.
		fwrite($file_write, "\xEF\xBB\xBF");
		while ($data = fgetcsv($file_read, 0, $delimiter)) {
			foreach ($data as $index => $cell) {
				$data[$index] = self::normalizeAndConvertCsvCell($cell, $sourceEncoding);
			}
			fputcsv($file_write, $data, $delimiter);
		}
		fclose($file_read);
		fclose($file_write);
		return $sourceEncoding;
	}

	static function fileUploadErrorMessage($error_code) {
		switch ($error_code) {
			case 1	:	$errorMessage = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
			case 2	:	$errorMessage = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
			case 3	:	$errorMessage = 'The uploaded file was only partially uploaded';
			case 4	:	$errorMessage = 'No file was uploaded';
			case 6	:	$errorMessage = 'Missing a temporary folder';
			case 7	:	$errorMessage = 'Failed to write file to disk';
			case 8	:	$errorMessage = 'File upload stopped by extension';
			default	:	$errorMessage = 'Unknown upload error';
		}
		return $errorMessage;
	}
}
