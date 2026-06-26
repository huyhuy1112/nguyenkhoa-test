<?php
/*+***********************************************************************************
 * Accounts one-step import — auto-map vtiger export CSV (EN/VN headers).
 *************************************************************************************/

class Accounts_SimpleImport_Helper {

	/** Staging column: raw Customer Code from BA Excel (before KH formatting). */
	const META_CUSTOMER_CODE = 'mk_import_customer_code';

	/** @var array<int, string> staging row id => raw customer code from file */
	public static $customerCodeByStagingId = array();

	/** @var array<int, string> created account id => formatted account_no */
	public static $customerCodeByRecordId = array();

	/** Fields to skip: use CRM defaults or need manual reference lookup. */
	public static $skipFieldNames = array(
		'assigned_user_id',
		'account_id',
		'fullname',
	);

	public static function normalizeHeader($header) {
		$header = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header);
		$header = str_replace(array("\xC2\xA0", "\t"), ' ', $header);
		$header = trim($header);
		if (strlen($header) >= 2 && $header[0] === '"' && substr($header, -1) === '"') {
			$header = substr($header, 1, -1);
		}
		$header = str_replace('"', '', $header);
		$header = preg_replace('/\s+/', ' ', $header);
		$header = mb_strtolower(trim($header), 'UTF-8');
		if (class_exists('Normalizer')) {
			$normalized = Normalizer::normalize($header, Normalizer::FORM_C);
			if (is_string($normalized) && $normalized !== '') {
				$header = $normalized;
			}
		}
		static $aliases = array(
			'organisation name' => 'organization name',
			'organisation' => 'organization name',
			'org name' => 'organization name',
			'account name' => 'organization name',
			'company code' => 'company code',
			'customer code' => 'customer code',
			'mã khách hàng' => 'customer code',
			'ma khach hang' => 'customer code',
			'khách hàng' => 'customer code',
			'khach hang' => 'customer code',
			'ma kh' => 'customer code',
			'mã kh' => 'customer code',
			'code' => 'customer code',
			'member of' => 'member of',
		);
		return isset($aliases[$header]) ? $aliases[$header] : $header;
	}

	public static function foldHeaderForMatch($header) {
		$header = self::normalizeHeader($header);
		$header = preg_replace('/\p{M}/u', '', $header);
		if (function_exists('iconv')) {
			$ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header);
			if (is_string($ascii) && $ascii !== '') {
				$header = strtolower($ascii);
			}
		}
		return $header;
	}

	public static function isPlaceholderHeaderName($header) {
		$normalized = self::normalizeHeader($header);
		if ($normalized === '' || ctype_digit($normalized)) {
			return true;
		}
		$compact = preg_replace('/\s+/', '', $normalized);
		if (preg_match('/^(?:cột|cot|column|col|field)\d+$/iu', $compact)) {
			return true;
		}
		$folded = self::foldHeaderForMatch($header);
		return (bool)preg_match('/^(?:column|col|field|cot)\s*\d+$/i', $folded);
	}

	public static function headersLookLikePlaceholders(array $headers) {
		if (empty($headers)) {
			return false;
		}
		$placeholder = 0;
		foreach ($headers as $header) {
			if (self::isPlaceholderHeaderName($header)) {
				$placeholder++;
			}
		}
		return $placeholder >= max(3, (int)floor(php7_count($headers) * 0.75));
	}

	public static function countKnownHeaderCells(array $cells) {
		$known = array_merge(array_keys(self::getHeaderFieldMap()), self::getCustomerCodeHeaderKeys());
		$matched = 0;
		foreach ($cells as $cell) {
			$normalized = self::normalizeHeader(Import_Utils_Helper::normalizeCsvCell((string)$cell));
			if ($normalized !== '' && in_array($normalized, $known, true)) {
				$matched++;
			}
		}
		return $matched;
	}

	public static function rowLooksLikeHeaderLabels(array $cells) {
		return self::countKnownHeaderCells($cells) >= 2;
	}

	/**
	 * BA / CRM export: Customer Code first, then Organization Name, … Company Code, Billing Address.
	 */
	/** Deleted customer slots — never auto-assign these numbers. */
	public static function getReservedSkipCustomerCodes() {
		return array(12, 37, 39, 48);
	}

	public static function bumpCustomerCodeNumber($num) {
		$num = max(1, (int) $num);
		while (in_array($num, self::getReservedSkipCustomerCodes(), true)) {
			$num++;
		}
		return $num;
	}

	public static function extractNumericCustomerCode($raw) {
		$raw = self::normalizeExcelCellNumber(trim((string) $raw));
		if ($raw === '') {
			return null;
		}
		if (preg_match('/^KH(\d+)/i', $raw, $matches)) {
			return (int) $matches[1];
		}
		if (ctype_digit($raw)) {
			return (int) $raw;
		}
		return null;
	}

	public static function resolveCustomerCodeColumnIndex(array $fieldMapping) {
		if (isset($fieldMapping[self::META_CUSTOMER_CODE])) {
			return (int) $fieldMapping[self::META_CUSTOMER_CODE];
		}
		if (isset($fieldMapping['account_no'])) {
			return (int) $fieldMapping['account_no'];
		}
		return 0;
	}

	public static function getMinimalTwoColumnMapping() {
		return array(
			self::META_CUSTOMER_CODE => 0,
			'account_no' => 0,
			'accountname' => 1,
		);
	}

	public static function finalizeFieldMapping(Vtiger_Request $request, array $fieldMapping) {
		$ccIndex = self::resolveCustomerCodeColumnIndex($fieldMapping);
		$fieldMapping[self::META_CUSTOMER_CODE] = $ccIndex;
		$fieldMapping['account_no'] = $ccIndex;
		$request->set('accounts_customer_code_col', $ccIndex);
		return $fieldMapping;
	}

	public static function injectCustomerCodeFromRawCsvRow(array $mappedData, array $rawCsvRow, $columnIndex) {
		$columnIndex = (int) $columnIndex;
		$cell = isset($rawCsvRow[$columnIndex]) ? Import_Utils_Helper::normalizeCsvCell($rawCsvRow[$columnIndex]) : '';
		$cell = self::normalizeExcelCellNumber(trim((string) $cell));
		if ($cell !== '' && self::looksLikeCustomerCodeValue($cell)) {
			$mappedData[self::META_CUSTOMER_CODE] = $cell;
			$mappedData['account_no'] = $cell;
		}
		return $mappedData;
	}

	public static function isImportMetaField($fieldName) {
		return $fieldName === self::META_CUSTOMER_CODE;
	}

	public static function getCustomerCodeHeaderKeys() {
		return array(
			'customer code',
			'mã khách hàng',
			'ma khach hang',
			'khách hàng',
			'khach hang',
			'ma kh',
			'mã kh',
			'code',
			'no',
			'no.',
		);
	}

	public static function looksLikeCustomerCodeValue($value) {
		$value = self::normalizeExcelCellNumber(trim((string) $value));
		if ($value === '') {
			return false;
		}
		if (preg_match('/^KH\d+/i', $value)) {
			return true;
		}
		if (!ctype_digit($value)) {
			return false;
		}
		$num = (int) $value;
		return $num > 0 && $num < 100000;
	}

	public static function extractRawCustomerCodeFromRow(array $row) {
		if (!empty($row[self::META_CUSTOMER_CODE])) {
			return self::normalizeExcelCellNumber($row[self::META_CUSTOMER_CODE]);
		}
		if (!empty($row['account_no'])) {
			return self::normalizeExcelCellNumber($row['account_no']);
		}
		return '';
	}

	public static function stashCustomerCodeForStagingRow($stagingRowId, array $row) {
		$raw = self::extractRawCustomerCodeFromRow($row);
		if ($raw === '') {
			return;
		}
		self::$customerCodeByStagingId[(int) $stagingRowId] = $raw;
	}

	public static function resetImportMetaState() {
		self::$customerCodeByStagingId = array();
		self::$customerCodeByRecordId = array();
	}

	public static function persistAccountNoToRecord($recordId, $stagingRowId = null, array $row = null, array $fieldData = array()) {
		global $adb;
		$recordId = (int) $recordId;
		if ($recordId <= 0) {
			return '';
		}

		$raw = '';
		if ($stagingRowId !== null && isset(self::$customerCodeByStagingId[(int) $stagingRowId])) {
			$raw = self::$customerCodeByStagingId[(int) $stagingRowId];
		} elseif (is_array($row)) {
			$raw = self::extractRawCustomerCodeFromRow($row);
		}
		if ($raw === '' && !empty($fieldData)) {
			if (!empty($fieldData[self::META_CUSTOMER_CODE])) {
				$raw = self::normalizeExcelCellNumber($fieldData[self::META_CUSTOMER_CODE]);
			} elseif (!empty($fieldData['account_no']) && !preg_match('/^KH/i', trim((string) $fieldData['account_no']))) {
				$raw = self::normalizeExcelCellNumber($fieldData['account_no']);
			}
		}

		$numeric = self::extractNumericCustomerCode($raw);
		if ($numeric === null) {
			return '';
		}
		$accountNo = self::formatCustomerAccountNo((string) $numeric);
		if ($accountNo === '') {
			return '';
		}

		$adb->pquery(
			'UPDATE vtiger_account SET account_no = ? WHERE accountid = ?',
			array($accountNo, $recordId)
		);
		self::$customerCodeByRecordId[$recordId] = $accountNo;
		return $accountNo;
	}

	public static function ensureCustomerCodeColumnMapping(array $headerIndex, array $fieldMapping, array $firstRow) {
		if (isset($fieldMapping[self::META_CUSTOMER_CODE]) || isset($fieldMapping['account_no'])) {
			return $fieldMapping;
		}

		foreach (self::getCustomerCodeHeaderKeys() as $headerKey) {
			if (isset($headerIndex[$headerKey])) {
				$index = $headerIndex[$headerKey];
				$fieldMapping[self::META_CUSTOMER_CODE] = $index;
				$fieldMapping['account_no'] = $index;
				return $fieldMapping;
			}
		}

		foreach ($headerIndex as $headerKey => $index) {
			if (preg_match('/customer|khach|khách|client|mã|ma\\b|code|kh\\b/i', $headerKey)) {
				$fieldMapping[self::META_CUSTOMER_CODE] = $index;
				$fieldMapping['account_no'] = $index;
				return $fieldMapping;
			}
		}

		$firstValue = '';
		foreach ($firstRow as $value) {
			$firstValue = trim((string) $value);
			break;
		}
		if (self::looksLikeCustomerCodeValue($firstValue)) {
			$fieldMapping[self::META_CUSTOMER_CODE] = 0;
			$fieldMapping['account_no'] = 0;
		}
		return $fieldMapping;
	}

	public static function importHasCustomerCodesInStaging($user) {
		$adb = PearDatabase::getInstance();
		$tableName = Import_Utils_Helper::getDbTableName($user);
		if (!Vtiger_Utils::CheckTable($tableName)) {
			return !empty(self::$customerCodeByStagingId);
		}
		if (!empty(self::$customerCodeByStagingId)) {
			return true;
		}
		if (self::stagingTableHasImportMetaColumns($tableName)) {
			$result = $adb->pquery(
				'SELECT 1 FROM ' . $tableName . ' WHERE TRIM(' . self::META_CUSTOMER_CODE . ") != '' LIMIT 1",
				array()
			);
			if ($result && $adb->num_rows($result) > 0) {
				return true;
			}
		}
		$result = $adb->pquery(
			"SELECT account_no FROM " . $tableName . " WHERE TRIM(account_no) != '' LIMIT 5",
			array()
		);
		if ($result) {
			$rows = $adb->num_rows($result);
			for ($i = 0; $i < $rows; $i++) {
				$cell = $adb->query_result($result, $i, 'account_no');
				if (self::looksLikeCustomerCodeValue($cell)) {
					return true;
				}
			}
		}
		return false;
	}

	public static function getBaExportPositionalOrder() {
		return array(
			self::META_CUSTOMER_CODE,
			'accountname',
			'website',
			'tickersymbol',
			'phone',
			'fax',
			null,
			'industry',
			'employees',
			'annualrevenue',
			'email2',
			'ownership',
			'rating',
			'accounttype',
			'siccode',
			'emailoptout',
			null,
			null,
			null,
			null,
			null,
			null,
			'cf_855',
			'description',
			'bill_street',
			'ship_street',
			'bill_pobox',
			'ship_pobox',
			'bill_city',
			'ship_city',
			'bill_state',
			'ship_state',
			'bill_code',
			'ship_code',
			'bill_country',
			'ship_country',
		);
	}

	/**
	 * English vtiger Accounts export column order (Organization Name, Organization Number, …).
	 */
	public static function getEnglishExportPositionalOrder() {
		return array(
			'accountname',
			'account_no',
			'website',
			'phone',
			'otherphone',
			'fax',
			'tickersymbol',
			'email1',
			'email2',
			'ownership',
			'industry',
			'rating',
			'accounttype',
			'siccode',
			'emailoptout',
			'annualrevenue',
			'employees',
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			'cf_855',
			null,
			'bill_street',
			'bill_pobox',
			'bill_city',
			'bill_state',
			'bill_code',
			'bill_country',
			'ship_street',
			'ship_pobox',
			'ship_city',
			'ship_state',
			'ship_code',
			'ship_country',
			'description',
		);
	}

	public static function buildPositionalFieldMapping($columnCount) {
		if ($columnCount >= 2 && $columnCount < 20) {
			return self::getMinimalTwoColumnMapping();
		}
		$mapping = array();
		$order = ($columnCount >= 20) ? self::getBaExportPositionalOrder() : self::getEnglishExportPositionalOrder();
		foreach ($order as $index => $fieldName) {
			if ($index >= $columnCount || $fieldName === null) {
				continue;
			}
			if (in_array($fieldName, self::$skipFieldNames, true)) {
				continue;
			}
			if ($fieldName === self::META_CUSTOMER_CODE) {
				$mapping[self::META_CUSTOMER_CODE] = $index;
				$mapping['account_no'] = $index;
				continue;
			}
			$mapping[$fieldName] = $index;
		}
		return $mapping;
	}

	public static function normalizeExcelCellNumber($value) {
		$value = trim((string) $value);
		if ($value === '') {
			return '';
		}
		if (preg_match('/^\d+\.0+$/', $value)) {
			return preg_replace('/\.0+$/', '', $value);
		}
		return $value;
	}

	/**
	 * Customer Code 13 → số hiệu tổ chức KH00013 (khớp import Opp).
	 */
	public static function formatCustomerAccountNo($customerCode) {
		$raw = trim((string) $customerCode);
		if ($raw === '') {
			return '';
		}
		if (preg_match('/^KH\d+/i', $raw)) {
			return strtoupper($raw);
		}
		$digits = preg_replace('/\D/', '', self::normalizeExcelCellNumber($raw));
		if ($digits === '') {
			return '';
		}
		return 'KH' . str_pad($digits, 5, '0', STR_PAD_LEFT);
	}

	public static function resolveAccountNoFromImportData(array $fieldData) {
		$raw = '';
		if (!empty($fieldData[self::META_CUSTOMER_CODE])) {
			$raw = $fieldData[self::META_CUSTOMER_CODE];
		} elseif (!empty($fieldData['account_no'])) {
			$raw = $fieldData['account_no'];
		}
		return self::formatCustomerAccountNo($raw);
	}

	public static function normalizeImportRow(array &$fieldData) {
		$accountNo = self::resolveAccountNoFromImportData($fieldData);
		if ($accountNo !== '') {
			$fieldData['account_no'] = $accountNo;
		}
		unset($fieldData[self::META_CUSTOMER_CODE]);
	}

	public static function resolveCustomerCodeMapping(array $headerIndex, array $fieldMapping) {
		foreach (self::getCustomerCodeHeaderKeys() as $headerKey) {
			if (!isset($headerIndex[$headerKey])) {
				continue;
			}
			$index = $headerIndex[$headerKey];
			$fieldMapping[self::META_CUSTOMER_CODE] = $index;
			$fieldMapping['account_no'] = $index;
			return $fieldMapping;
		}
		return $fieldMapping;
	}

	public static function resolveAccountNoForStagingRow(array $row, &$runningNum) {
		$raw = self::extractRawCustomerCodeFromRow($row);
		$numeric = self::extractNumericCustomerCode($raw);
		if ($numeric !== null) {
			$runningNum = $numeric;
			return self::formatCustomerAccountNo((string) $numeric);
		}
		if ($runningNum !== null) {
			$runningNum = self::bumpCustomerCodeNumber($runningNum + 1);
		} else {
			$runningNum = self::bumpCustomerCodeNumber(1);
		}
		return self::formatCustomerAccountNo((string) $runningNum);
	}

	public static function stagingTableHasImportMetaColumns($tableName) {
		$adb = PearDatabase::getInstance();
		$result = $adb->pquery('SHOW COLUMNS FROM ' . $tableName . ' LIKE ?', array(self::META_CUSTOMER_CODE));
		return ($result && $adb->num_rows($result) > 0);
	}

	/**
	 * After bulk create CRM may auto-fill account_no — restore codes from staging Customer Code.
	 */
	public static function applyAccountNumbersAfterImport($user) {
		$adb = PearDatabase::getInstance();
		$tableName = Import_Utils_Helper::getDbTableName($user);
		if (!Vtiger_Utils::CheckTable($tableName)) {
			return array('updated' => 0, 'skipped' => 0);
		}

		$hasMeta = self::stagingTableHasImportMetaColumns($tableName);
		$select = $hasMeta
			? 'recordid, account_no, ' . self::META_CUSTOMER_CODE
			: 'recordid, account_no';
		$result = $adb->pquery(
			'SELECT ' . $select . ' FROM ' . $tableName . '
			 WHERE status = ? AND recordid IS NOT NULL AND recordid != ?
			 ORDER BY id ASC',
			array(Import_Data_Action::$IMPORT_RECORD_CREATED, '')
		);

		$updated = 0;
		$skipped = 0;
		if (!$result) {
			return array('updated' => 0, 'skipped' => 0);
		}

		$rows = $adb->num_rows($result);
		$runningNum = null;
		for ($i = 0; $i < $rows; $i++) {
			$recordId = (int) $adb->query_result($result, $i, 'recordid');
			if ($recordId <= 0) {
				continue;
			}
			$row = array();
			if ($hasMeta) {
				$row[self::META_CUSTOMER_CODE] = $adb->query_result($result, $i, self::META_CUSTOMER_CODE);
			}
			$row['account_no'] = $adb->query_result($result, $i, 'account_no');
			$accountNo = self::resolveAccountNoForStagingRow($row, $runningNum);
			if ($accountNo === '') {
				$skipped++;
				continue;
			}
			$adb->pquery(
				'UPDATE vtiger_account SET account_no = ? WHERE accountid = ?',
				array($accountNo, $recordId)
			);
			self::$customerCodeByRecordId[$recordId] = $accountNo;
			$updated++;
		}

		return array('updated' => $updated, 'skipped' => $skipped);
	}

	public static function normalizeImportFile(Vtiger_Request $request, $user) {
		$filePath = Import_Utils_Helper::getImportFilePath($user);
		if (!$filePath || !is_readable($filePath)) {
			return false;
		}
		$delimiter = $request->get('delimiter') ? $request->get('delimiter') : ',';
		$handle = fopen($filePath, 'r');
		if (!$handle) {
			return false;
		}
		$rows = array();
		while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
			$rows[] = $data;
		}
		fclose($handle);
		if (php7_count($rows) < 2) {
			return false;
		}

		$row0 = $rows[0];
		$row1 = $rows[1];
		if (!self::headersLookLikePlaceholders($row0) || !self::rowLooksLikeHeaderLabels($row1)) {
			return false;
		}

		$newRows = array($row1);
		for ($i = 2, $n = php7_count($rows); $i < $n; $i++) {
			if (self::rowLooksLikeHeaderLabels($rows[$i])) {
				continue;
			}
			$newRows[] = $rows[$i];
		}

		$tmpPath = $filePath . '.mknorm.csv';
		$out = fopen($tmpPath, 'w');
		if (!$out) {
			return false;
		}
		fwrite($out, "\xEF\xBB\xBF");
		foreach ($newRows as $row) {
			fputcsv($out, $row, $delimiter);
		}
		fclose($out);
		if (!@rename($tmpPath, $filePath)) {
			@unlink($tmpPath);
			return false;
		}
		return true;
	}

	public static function tryRebuildHeaderIndexFromRawRow(Vtiger_Request $request, $user, $rowOffset = 0) {
		$filePath = Import_Utils_Helper::getImportFilePath($user);
		if (!$filePath || !is_readable($filePath)) {
			return null;
		}
		$delimiter = $request->get('delimiter') ? $request->get('delimiter') : ',';
		$handle = fopen($filePath, 'r');
		if (!$handle) {
			return null;
		}
		$line = null;
		for ($i = 0; $i <= $rowOffset; $i++) {
			$line = fgetcsv($handle, 0, $delimiter);
			if ($line === false) {
				break;
			}
		}
		fclose($handle);
		if (!is_array($line) || empty($line)) {
			return null;
		}
		$headerIndex = array();
		$known = array_merge(array_keys(self::getHeaderFieldMap()), self::getCustomerCodeHeaderKeys());
		$matched = 0;
		foreach ($line as $index => $cell) {
			$cell = self::normalizeHeader(Import_Utils_Helper::normalizeCsvCell((string)$cell));
			if ($cell === '') {
				continue;
			}
			$headerIndex[$cell] = $index;
			if (in_array($cell, $known, true)) {
				$matched++;
			}
		}
		return ($matched >= 2) ? $headerIndex : null;
	}

	public static function getHeaderFieldMap() {
		return array(
			'organization name' => 'accountname',
			'account name' => 'accountname',
			'tên' => 'accountname',
			'tên ngắn gọn thường gọi' => 'accountname',
			'company code' => 'cf_855',
			'mã công ty' => 'cf_855',
			'mã company' => 'cf_855',
			'website' => 'website',
			'trang web' => 'website',
			'primary phone' => 'phone',
			'phone' => 'phone',
			'số điện thoại liên hệ' => 'phone',
			'secondary phone' => 'otherphone',
			'other phone' => 'otherphone',
			'fax' => 'fax',
			'ticker symbol' => 'tickersymbol',
			'primary email' => 'email1',
			'email' => 'email1',
			'email liên lạc' => 'email1',
			'secondary email' => 'email2',
			'ownership' => 'ownership',
			'industry' => 'industry',
			'ngành nghề kinh doanh' => 'industry',
			'ngành' => 'industry',
			'rating' => 'rating',
			'type' => 'accounttype',
			'account type' => 'accounttype',
			'sic code' => 'siccode',
			'mã số thuế' => 'siccode',
			'email opt out' => 'emailoptout',
			'annual revenue' => 'annualrevenue',
			'description' => 'description',
			'mô tả' => 'description',
			'ghi chú' => 'description',
			'employees' => 'employees',
			'billing address' => 'bill_street',
			'địa chỉ trụ sở chính' => 'bill_street',
			'địa chỉ' => 'bill_street',
			'shipping address' => 'ship_street',
			'billing po box' => 'bill_pobox',
			'shipping po box' => 'ship_pobox',
			'billing city' => 'bill_city',
			'shipping city' => 'ship_city',
			'billing state' => 'bill_state',
			'shipping state' => 'ship_state',
			'billing postal code' => 'bill_code',
			'shipping postal code' => 'ship_code',
			'billing country' => 'bill_country',
			'shipping country' => 'ship_country',
		);
	}

	public static function filterFieldMapping($fieldMapping, $moduleName = 'Accounts') {
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		if (!$moduleModel) {
			return $fieldMapping;
		}
		$moduleFields = $moduleModel->getFields();
		$additionalFields = $moduleModel->getAdditionalImportFields();
		$allowed = array_merge($moduleFields, $additionalFields);

		$filtered = array();
		foreach ($fieldMapping as $fieldName => $index) {
			if (self::isImportMetaField($fieldName)) {
				$filtered[$fieldName] = $index;
				continue;
			}
			if ($moduleName === 'Accounts' && $fieldName === 'account_no') {
				$filtered[$fieldName] = $index;
				continue;
			}
			if (in_array($fieldName, self::$skipFieldNames, true)) {
				continue;
			}
			if (!isset($allowed[$fieldName])) {
				continue;
			}
			$filtered[$fieldName] = $index;
		}
		return $filtered;
	}

	public static function buildFieldMapping(Vtiger_Request $request, $user) {
		$fileReader = Import_Utils_Helper::getFileReader($request, $user);
		if ($fileReader == null) {
			throw new Exception(vtranslate('LBL_INVALID_FILE', 'Import'));
		}

		$hasHeader = $fileReader->hasHeader();
		$firstRow = $fileReader->getFirstRowData($hasHeader);
		if (!is_array($firstRow) || empty($firstRow)) {
			throw new Exception(vtranslate('LBL_NO_ROWS_FOUND', 'Import'));
		}

		$headers = array_keys($firstRow);
		if (php7_count($headers) <= 3) {
			$fieldMapping = self::getMinimalTwoColumnMapping();
			$fieldMapping = self::filterFieldMapping($fieldMapping, $request->getModule());
			if (!isset($fieldMapping['accountname'])) {
				$fieldMapping['accountname'] = 1;
			}
			return self::finalizeFieldMapping($request, $fieldMapping);
		}

		$headerIndex = array();
		foreach ($headers as $index => $header) {
			$headerIndex[self::normalizeHeader($header)] = $index;
		}

		$usePositional = self::headersLookLikePlaceholders($headers);
		if ($usePositional) {
			$rebuilt = self::tryRebuildHeaderIndexFromRawRow($request, $user, 1);
			if (is_array($rebuilt) && !empty($rebuilt)) {
				$headerIndex = $rebuilt;
				$usePositional = false;
			}
		}
		if (!$usePositional) {
			$rebuilt = self::tryRebuildHeaderIndexFromRawRow($request, $user, 0);
			if (is_array($rebuilt) && !empty($rebuilt)) {
				$headerIndex = $rebuilt;
			}
		}

		if ($usePositional) {
			$fieldMapping = self::buildPositionalFieldMapping(php7_count($headers));
			$fieldMapping = self::ensureCustomerCodeColumnMapping($headerIndex, $fieldMapping, $firstRow);
			$fieldMapping = self::filterFieldMapping($fieldMapping, $request->getModule());
		} else {
			$headerMap = self::getHeaderFieldMap();
			$fieldMapping = array();
			foreach ($headerMap as $headerKey => $fieldName) {
				if (!isset($headerIndex[$headerKey])) {
					continue;
				}
				$fieldMapping[$fieldName] = $headerIndex[$headerKey];
			}
			$fieldMapping = self::resolveCustomerCodeMapping($headerIndex, $fieldMapping);
			$fieldMapping = self::ensureCustomerCodeColumnMapping($headerIndex, $fieldMapping, $firstRow);
			$fieldMapping = self::filterFieldMapping($fieldMapping, $request->getModule());
		}

		if (!isset($fieldMapping['accountname'])) {
			$found = array_keys($headerIndex);
			$foundText = $found ? implode(' | ', array_slice($found, 0, 12)) : '(trống — kiểm tra dấu phẩy hoặc chấm phẩy)';
			throw new Exception(
				'Không map được cột Organization Name / Tên. Header đọc được: [' . $foundText . ']. '
				. 'Lưu file CSV UTF-8 với dòng header đúng (hoặc xuất lại từ Numbers: dòng 2 phải là Organization Name, Organization Number, …).'
			);
		}
		return self::finalizeFieldMapping($request, $fieldMapping);
	}

	public static function getFailedRowSamples($user, $limit = 5) {
		$adb = PearDatabase::getInstance();
		$tableName = Import_Utils_Helper::getDbTableName($user);
		if (!Vtiger_Utils::CheckTable($tableName)) {
			return array();
		}
		$samples = array();
		$result = $adb->pquery(
			'SELECT accountname FROM ' . $tableName . ' WHERE status = ? LIMIT ' . (int)$limit,
			array(Import_Data_Action::$IMPORT_RECORD_FAILED)
		);
		if ($result) {
			$rows = $adb->num_rows($result);
			for ($i = 0; $i < $rows; $i++) {
				$name = trim((string)$adb->query_result($result, $i, 'accountname'));
				if ($name !== '') {
					$samples[] = $name;
				}
			}
		}
		return $samples;
	}

	public static function buildResultMessage($importStatusCount, $failedSamples = array()) {
		$imported = (int)$importStatusCount['IMPORTED'];
		$failed = (int)$importStatusCount['FAILED'];
		$skipped = (int)$importStatusCount['SKIPPED'];
		$total = (int)$importStatusCount['TOTAL'];

		if ($imported <= 0) {
			$message = 'Import thất bại: 0/' . $total . ' bản ghi được tạo.';
			if ($failed > 0) {
				$message .= ' (' . $failed . ' dòng lỗi';
				if ($skipped > 0) {
					$message .= ', ' . $skipped . ' bỏ qua';
				}
				$message .= ')';
			} elseif ($total <= 0) {
				$message = 'File không có dòng dữ liệu (chỉ header hoặc file rỗng).';
			}
			if (!empty($failedSamples)) {
				$message .= '. Ví dụ: ' . implode(', ', array_slice($failedSamples, 0, 3));
			}
			return $message;
		}

		$message = sprintf('Import Tổ chức hoàn tất: %d/%d bản ghi thành công', $imported, $total);
		if ($failed > 0) {
			$message .= sprintf(' (%d lỗi)', $failed);
		}
		return $message;
	}
}
