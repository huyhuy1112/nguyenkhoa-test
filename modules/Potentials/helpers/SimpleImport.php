<?php
/*+***********************************************************************************
 * Potentials (Orders/Opportunities) one-step import — auto-map export CSV.
 *************************************************************************************/

class Potentials_SimpleImport_Helper {

	public static $skipFieldNames = array(
		'assigned_user_id',
	);

	/**
	 * Export CSV often leaves Expected Close Date empty; closingdate is mandatory in CRM.
	 */
	public static function getDefaultClosingDate() {
		return date('Y-12-31', strtotime('+1 year'));
	}

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
			'opportunity name' => 'potentialname',
			'potential name' => 'potentialname',
			'tiêu đề' => 'potentialname',
			'project name' => 'cf_857',
			'tên dự án' => 'cf_857',
			'opportunity number' => 'potential_no',
			'potential no' => 'potential_no',
			'mã orders' => 'potential_no',
			'organization name' => 'related_to',
			'tên khách hàng' => 'related_to',
			'related to' => 'related_to',
			'contact name' => 'contact_id',
			'tên liên hệ' => 'contact_id',
			'amount' => 'amount',
			'giá trị dự kiến' => 'amount',
			'type' => 'opportunity_type',
			'loại order' => 'opportunity_type',
			'opportunity type' => 'opportunity_type',
			'expected close date' => 'closingdate',
			'ngày dự kiến kết thúc' => 'closingdate',
			'lead source' => 'leadsource',
			'nguồn order' => 'leadsource',
			'next step' => 'nextstep',
			'bước tiếp theo' => 'nextstep',
			'sales stage' => 'sales_stage',
			'trạng thái order' => 'sales_stage',
			'campaign source' => 'campaignid',
			'nguồn chiến dịch' => 'campaignid',
			'probability' => 'probability',
			'xác suất' => 'probability',
			'order category' => 'order_category',
			'phân loại order' => 'order_category',
			'description' => 'description',
			'mô tả' => 'description',
			'ghi chú' => 'description',
			'weighted revenue' => 'forecast_amount',
			'forecast amount' => 'forecast_amount',
			'dự đoán giá trị' => 'forecast_amount',
		);
	}

	public static function filterFieldMapping($fieldMapping, $moduleName = 'Potentials') {
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

	public static function getColumnValueByHeaderKey($headerIndex, $headerKey, $headers, $firstRowData) {
		if (!isset($headerIndex[$headerKey])) {
			return '';
		}
		$idx = $headerIndex[$headerKey];
		if (isset($headers[$idx])) {
			return trim((string)($firstRowData[$headers[$idx]] ?? ''));
		}
		return '';
	}

	public static function resolvePotentialNameMapping($headerIndex, $fieldMapping, $headers, $firstRowData) {
		$titleKeys = array('opportunity name', 'potential name', 'tiêu đề');
		foreach ($titleKeys as $key) {
			if (!isset($headerIndex[$key])) {
				continue;
			}
			if (self::getColumnValueByHeaderKey($headerIndex, $key, $headers, $firstRowData) !== '') {
				$fieldMapping['potentialname'] = $headerIndex[$key];
				return $fieldMapping;
			}
		}
		// Placeholder until post-import ProjectCodeHandler builds full Opportunity Name.
		if (isset($headerIndex['project name'])) {
			$fieldMapping['potentialname'] = $headerIndex['project name'];
		}
		return $fieldMapping;
	}

	public static function resolveProjectNameMapping($headerIndex, $fieldMapping) {
		foreach (array('project name', 'tên dự án') as $key) {
			if (isset($headerIndex[$key])) {
				$fieldMapping['cf_857'] = $headerIndex[$key];
				break;
			}
		}
		return $fieldMapping;
	}

	/**
	 * Resolve Contact Name from export CSV (label / first+last / account scope).
	 */
	public static function lookupContactId($contactName, $accountId = null) {
		global $adb;
		$contactName = trim(html_entity_decode((string)$contactName, ENT_QUOTES, 'UTF-8'));
		if ($contactName === '') {
			return 0;
		}

		$entityId = getEntityId('Contacts', $contactName);
		if (!empty($entityId)) {
			return (int)$entityId;
		}

		$labelResult = $adb->pquery(
			'SELECT crmid FROM vtiger_crmentity WHERE setype = ? AND deleted = 0 AND label = ? LIMIT 1',
			array('Contacts', $contactName)
		);
		if ($labelResult && $adb->num_rows($labelResult) > 0) {
			return (int)$adb->query_result($labelResult, 0, 'crmid');
		}

		$nameParts = preg_split('/\s+/', $contactName, 2);
		if (php7_count($nameParts) === 2) {
			$firstName = trim($nameParts[0]);
			$lastName = trim($nameParts[1]);
			$sql = 'SELECT cd.contactid FROM vtiger_contactdetails cd
				INNER JOIN vtiger_crmentity ce ON ce.crmid = cd.contactid
				WHERE ce.deleted = 0 AND cd.firstname = ? AND cd.lastname = ?';
			$params = array($firstName, $lastName);
			if (!empty($accountId) && is_numeric($accountId)) {
				$sql .= ' AND cd.accountid = ?';
				$params[] = (int)$accountId;
			}
			$sql .= ' LIMIT 1';
			$nameResult = $adb->pquery($sql, $params);
			if ($nameResult && $adb->num_rows($nameResult) > 0) {
				return (int)$adb->query_result($nameResult, 0, 'contactid');
			}
		}

		$sql = 'SELECT cd.contactid FROM vtiger_contactdetails cd
			INNER JOIN vtiger_crmentity ce ON ce.crmid = cd.contactid
			WHERE ce.deleted = 0 AND (cd.lastname = ? OR cd.firstname = ?)';
		$params = array($contactName, $contactName);
		if (!empty($accountId) && is_numeric($accountId)) {
			$sql .= ' AND cd.accountid = ?';
			$params[] = (int)$accountId;
		}
		$sql .= ' LIMIT 1';
		$singleResult = $adb->pquery($sql, $params);
		if ($singleResult && $adb->num_rows($singleResult) > 0) {
			return (int)$adb->query_result($singleResult, 0, 'contactid');
		}

		return 0;
	}

	/**
	 * Import runs in bulk mode (events skipped). Re-apply Project Code naming rule after import.
	 * Format: YYMMDD-{ORG}{INDEX}-{companyCode}-{projectName}
	 */
	public static function applyProjectCodesAfterImport($user) {
		require_once 'modules/Potentials/ProjectCodeHandler.php';

		$adb = PearDatabase::getInstance();
		$tableName = Import_Utils_Helper::getDbTableName($user);
		if (!Vtiger_Utils::CheckTable($tableName)) {
			return array('updated' => 0, 'failed' => 0);
		}

		$result = $adb->pquery(
			'SELECT recordid FROM ' . $tableName . ' WHERE status = ? AND recordid IS NOT NULL AND recordid != ? ORDER BY id ASC',
			array(Import_Data_Action::$IMPORT_RECORD_CREATED, '')
		);

		$updated = 0;
		$failed = 0;
		if (!$result) {
			return array('updated' => 0, 'failed' => 0);
		}

		$rows = $adb->num_rows($result);
		for ($i = 0; $i < $rows; $i++) {
			$recordId = (int)$adb->query_result($result, $i, 'recordid');
			if ($recordId <= 0) {
				continue;
			}
			$code = ProjectCodeHandler::generateForPotential($recordId, array('force' => true));
			if ($code) {
				$updated++;
			} else {
				$failed++;
			}
		}

		return array('updated' => $updated, 'failed' => $failed);
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

		$headerMap = self::getHeaderFieldMap();
		$fieldMapping = array();
		foreach ($headerMap as $headerKey => $fieldName) {
			if ($fieldName === 'potentialname' || $fieldName === 'cf_857') {
				continue;
			}
			if (isset($headerIndex[$headerKey])) {
				$fieldMapping[$fieldName] = $headerIndex[$headerKey];
			}
		}

		$fieldMapping = self::resolveProjectNameMapping($headerIndex, $fieldMapping);
		$fieldMapping = self::resolvePotentialNameMapping($headerIndex, $fieldMapping, $headers, $firstRow);
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
			'SELECT potentialname FROM ' . $tableName . ' WHERE status = ? LIMIT ' . (int)$limit,
			array(Import_Data_Action::$IMPORT_RECORD_FAILED)
		);
		if ($result) {
			$rows = $adb->num_rows($result);
			for ($i = 0; $i < $rows; $i++) {
				$name = trim((string)$adb->query_result($result, $i, 'potentialname'));
				if ($name !== '') {
					$samples[] = $name;
				}
			}
		}
		return $samples;
	}

	public static function buildResultMessage($importStatusCount, $failedSamples = array(), $codeStats = array()) {
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

		$message = sprintf('Import Orders hoàn tất: %d/%d bản ghi thành công', $imported, $total);
		if ($failed > 0) {
			$message .= sprintf(' (%d lỗi)', $failed);
		}
		if (!empty($codeStats['failed'])) {
			$message .= sprintf('. %d Order chưa tạo được mã tên (thiếu Company Code hoặc Organization Number).', (int)$codeStats['failed']);
		}
		return $message;
	}
}
