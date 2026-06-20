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
		$header = trim($header);
		if (strlen($header) >= 2 && $header[0] === '"' && substr($header, -1) === '"') {
			$header = substr($header, 1, -1);
		}
		$header = str_replace('"', '', $header);
		$header = preg_replace('/\s+/', ' ', $header);
		return mb_strtolower(trim($header), 'UTF-8');
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
		$headerMap = self::getHeaderFieldMap();
		$fieldMapping = array();

		foreach ($headers as $index => $header) {
			$normalized = self::normalizeHeader($header);
			if ($normalized === '' || !isset($headerMap[$normalized])) {
				continue;
			}
			$fieldName = $headerMap[$normalized];
			$fieldMapping[$fieldName] = $index;
		}

		return self::filterFieldMapping($fieldMapping, $request->getModule());
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
