<?php
/*+***********************************************************************************
 * Accounts one-step import — auto-map vtiger export CSV (EN/VN headers).
 *************************************************************************************/

class Accounts_SimpleImport_Helper {

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
			'tên khách hàng' => 'organization name',
			'ten khach hang' => 'organization name',
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
		$known = array_keys(self::getHeaderFieldMap());
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
		$mapping = array();
		$order = self::getEnglishExportPositionalOrder();
		foreach ($order as $index => $fieldName) {
			if ($index >= $columnCount || $fieldName === null) {
				continue;
			}
			if (in_array($fieldName, self::$skipFieldNames, true)) {
				continue;
			}
			$mapping[$fieldName] = $index;
		}
		return $mapping;
	}

	/**
	 * Numbers/Excel: row1 = Cột1..N / Column1..N, row2 = Organization Name, …
	 */
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
		$known = array_keys(self::getHeaderFieldMap());
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
			'organization number' => 'account_no',
			'account no' => 'account_no',
			'số hiệu tổ chức' => 'account_no',
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

		if ($usePositional) {
			$fieldMapping = self::buildPositionalFieldMapping(php7_count($headers));
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
		return $fieldMapping;
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
