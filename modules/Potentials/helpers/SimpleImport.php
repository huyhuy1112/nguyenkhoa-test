<?php
/*+***********************************************************************************
 * Potentials (Orders/Opportunities) one-step import — auto-map export CSV.
 *************************************************************************************/

class Potentials_SimpleImport_Helper {

	public static $skipFieldNames = array(
		'assigned_user_id',
	);

	/** Staging-row import meta (customer code + project order from BA template). */
	public static $importMetaByStagingId = array();

	/** Resolved meta keyed by created potential record id. */
	public static $importMetaByRecordId = array();

	const META_CUSTOMER_CODE = 'mk_import_customer_code';
	const META_PROJECT_ORDER = 'mk_import_project_order';

	/**
	 * Export CSV often leaves Expected Close Date empty; closingdate is mandatory in CRM.
	 */
	public static function getDefaultClosingDate() {
		return date('Y-12-31', strtotime('+1 year'));
	}

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
		static $aliases = array(
			'organisation name' => 'organization name',
			'organisation' => 'organization name',
			'org name' => 'organization name',
			'account name' => 'organization name',
			'projectname' => 'project name',
			'opportunityname' => 'opportunity name',
			'potentialname' => 'potential name',
			'contactname' => 'contact name',
			'close date' => 'expected close date',
			'customer code' => 'khách hàng',
			'mã khách hàng' => 'khách hàng',
			'ma khach hang' => 'khách hàng',
			'project order' => 'thứ tự dự án',
			'thu tu du an' => 'thứ tự dự án',
		);
		return isset($aliases[$header]) ? $aliases[$header] : $header;
	}

	public static function getHeaderFieldMap() {
		return array(
			'khách hàng' => self::META_CUSTOMER_CODE,
			'thứ tự dự án' => self::META_PROJECT_ORDER,
			'opportunity name' => 'potentialname',
			'potential name' => 'potentialname',
			'tiêu đề' => 'potentialname',
			'project name' => 'cf_857',
			'tên dự án' => 'cf_857',
			'project code' => 'cf_859',
			'mã dự án' => 'cf_859',
			'order name' => 'potentialname',
			'opportunity number' => 'potential_no',
			'opportunity no' => 'potential_no',
			'potential no' => 'potential_no',
			'mã orders' => 'potential_no',
			'organization name' => 'related_to',
			'organisation name' => 'related_to',
			'tên khách hàng' => 'related_to',
			'related to' => 'related_to',
			'contact name' => 'contact_id',
			'tên liên hệ' => 'contact_id',
			'amount' => 'amount',
			'giá trị dự kiến' => 'amount',
			'type' => 'opportunity_type',
			'loại order' => 'opportunity_type',
			'opportunity type' => 'opportunity_type',
			'model' => 'opportunity_type',
			'expected close date' => 'closingdate',
			'close date' => 'closingdate',
			'ngày dự kiến kết thúc' => 'closingdate',
			'lead source' => 'leadsource',
			'source' => 'leadsource',
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
			'source(2)' => 'order_category',
			'description' => 'description',
			'mô tả' => 'description',
			'ghi chú' => 'description',
			'weighted revenue' => 'forecast_amount',
			'forecast amount' => 'forecast_amount',
			'dự đoán giá trị' => 'forecast_amount',
		);
	}

	public static function isImportMetaField($fieldName) {
		return $fieldName === self::META_CUSTOMER_CODE || $fieldName === self::META_PROJECT_ORDER;
	}

	public static function formatCustomerAccountNo($customerCode) {
		$digits = preg_replace('/\D/', '', (string) $customerCode);
		if ($digits === '') {
			return '';
		}
		return 'KH' . str_pad($digits, 5, '0', STR_PAD_LEFT);
	}

	public static function lookupAccountIdByCustomerCode($customerCode, $orgNameHint = null) {
		global $adb;
		$accountNo = self::formatCustomerAccountNo($customerCode);
		if ($accountNo === '') {
			return 0;
		}
		$result = $adb->pquery(
			'SELECT a.accountid FROM vtiger_account a
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = a.accountid
			 WHERE ce.deleted = 0 AND a.account_no = ? LIMIT 1',
			array($accountNo)
		);
		if ($result && $adb->num_rows($result) > 0) {
			return (int) $adb->query_result($result, 0, 'accountid');
		}
		if (!empty($orgNameHint)) {
			$byName = getEntityId('Accounts', decode_html(trim((string) $orgNameHint)));
			if (!empty($byName)) {
				return (int) $byName;
			}
		}
		return 0;
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

	public static function stashImportMetaForStagingRow($stagingRowId, array $row) {
		$customerCode = isset($row[self::META_CUSTOMER_CODE]) ? self::normalizeExcelCellNumber($row[self::META_CUSTOMER_CODE]) : '';
		$projectOrder = isset($row[self::META_PROJECT_ORDER]) ? self::normalizeExcelCellNumber($row[self::META_PROJECT_ORDER]) : '';
		if ($customerCode === '' && $projectOrder === '') {
			return;
		}
		self::$importMetaByStagingId[(int) $stagingRowId] = array(
			'customer_code' => $customerCode,
			'project_order' => $projectOrder,
		);
	}

	public static function attachImportMetaToRecord($stagingRowId, $recordId) {
		$stagingRowId = (int) $stagingRowId;
		$recordId = (int) $recordId;
		if ($recordId <= 0 || !isset(self::$importMetaByStagingId[$stagingRowId])) {
			return;
		}
		self::$importMetaByRecordId[$recordId] = self::$importMetaByStagingId[$stagingRowId];
		unset(self::$importMetaByStagingId[$stagingRowId]);
	}

	public static function stripImportMetaFields(array $fieldData) {
		unset($fieldData[self::META_CUSTOMER_CODE], $fieldData[self::META_PROJECT_ORDER]);
		return $fieldData;
	}

	public static function resolveImportReferences(array &$fieldData, $stagingRowId = null) {
		$customerCode = '';
		$projectOrder = '';
		if ($stagingRowId !== null && isset(self::$importMetaByStagingId[(int) $stagingRowId])) {
			$meta = self::$importMetaByStagingId[(int) $stagingRowId];
			$customerCode = isset($meta['customer_code']) ? $meta['customer_code'] : '';
			$projectOrder = isset($meta['project_order']) ? $meta['project_order'] : '';
		}

		$orgLabel = isset($fieldData['related_to']) ? trim((string) $fieldData['related_to']) : '';
		$accountId = 0;
		if ($orgLabel !== '' && ctype_digit($orgLabel)) {
			$accountId = (int) $orgLabel;
		} elseif ($orgLabel !== '') {
			$accountId = (int) getEntityId('Accounts', decode_html($orgLabel));
		}
		if ($accountId <= 0 && $customerCode !== '') {
			$accountId = self::lookupAccountIdByCustomerCode($customerCode, $orgLabel);
		}
		if ($accountId > 0) {
			global $adb;
			$nameResult = $adb->pquery(
				'SELECT accountname FROM vtiger_account WHERE accountid = ? LIMIT 1',
				array($accountId)
			);
			if ($nameResult && $adb->num_rows($nameResult) > 0) {
				$fieldData['related_to'] = $adb->query_result($nameResult, 0, 'accountname');
			}
		}
	}

	public static function filterFieldMapping($fieldMapping, $moduleName = 'Potentials') {
		$projectNameIndex = isset($fieldMapping['cf_857']) ? $fieldMapping['cf_857'] : null;
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
			if (in_array($fieldName, self::$skipFieldNames, true)) {
				continue;
			}
			if (!isset($allowed[$fieldName])) {
				continue;
			}
			$filtered[$fieldName] = $index;
		}
		if ($projectNameIndex !== null && !isset($filtered['cf_857']) && !isset($filtered['potentialname'])) {
			$filtered['potentialname'] = $projectNameIndex;
		}
		return $filtered;
	}

	public static function assertRequiredMapping($fieldMapping, $headerIndex) {
		$missing = array();
		if (!array_key_exists('cf_857', $fieldMapping) && !array_key_exists('potentialname', $fieldMapping)) {
			$missing[] = 'Project Name / Opportunity Name / Tiêu đề';
		}
		$hasOrg = array_key_exists('related_to', $fieldMapping);
		$hasCustomerCode = array_key_exists(self::META_CUSTOMER_CODE, $fieldMapping);
		if (!$hasOrg && !$hasCustomerCode) {
			$missing[] = 'Organization Name hoặc Khách hàng (mã KH)';
		}
		if (empty($missing)) {
			return;
		}
		$found = array_keys($headerIndex);
		$foundText = $found ? implode(' | ', array_slice($found, 0, 12)) : '(trống — kiểm tra dấu phẩy hoặc chấm phẩy)';
		throw new Exception(
			'Không map được cột ' . implode(' và ', $missing) . '. '
			. 'Header đọc được: [' . $foundText . ']. '
			. 'Dòng 1 file cần đúng tên cột (Project Name, Organization Name, …) và lưu CSV UTF-8.'
		);
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

	public static function isPlaceholderHeaderName($header) {
		$header = self::normalizeHeader($header);
		if ($header === '' || ctype_digit($header)) {
			return true;
		}
		$compact = preg_replace('/\s+/', '', $header);
		if (preg_match('/^(?:cột|cot|column|col|field)\d+$/iu', $compact)) {
			return true;
		}
		$header = preg_replace('/\p{M}/u', '', $header);
		if (function_exists('iconv')) {
			$ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header);
			if (is_string($ascii) && $ascii !== '') {
				$header = strtolower($ascii);
			}
		}
		return (bool)preg_match('/^(?:column|col|field|cot)\s*\d+$/i', $header);
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

	/**
	 * BA / CRM export template (Khách hàng, Thứ tự dự án, Project Name, …).
	 */
	public static function getBaExportPositionalOrder() {
		return array(
			self::META_CUSTOMER_CODE,
			self::META_PROJECT_ORDER,
			'cf_857',
			'cf_859',
			'description',
			'potentialname',
			'potential_no',
			'related_to',
			'contact_id',
			'amount',
			'opportunity_type',
			null,
			'closingdate',
			'leadsource',
			'nextstep',
			'assigned_user_id',
			'sales_stage',
			'campaignid',
			'probability',
			null,
			null,
			null,
			null,
			'leadsource',
			'order_category',
		);
	}

	/**
	 * English CRM export (Project Name, Project Code, …, Organization Name, Contact Name).
	 */
	public static function getEnglishExportPositionalOrder() {
		return array(
			'cf_857',
			'cf_859',
			null,
			'potentialname',
			'potential_no',
			'related_to',
			'contact_id',
		);
	}

	/**
	 * SALES export / sample CSV column order (Vietnamese headers in DownloadImportSample).
	 */
	public static function getPositionalFieldOrder() {
		return array(
			'description',
			'potentialname',
			'potential_no',
			'related_to',
			'contact_id',
			'opportunity_type',
			'amount',
			'leadsource',
			'closingdate',
			'assigned_user_id',
			'nextstep',
			'campaignid',
			'sales_stage',
			'probability',
			'forecast_amount',
			'order_category',
		);
	}

	public static function buildPositionalFieldMapping($columnCount) {
		$mapping = array();
		$baOrder = self::getBaExportPositionalOrder();
		if ($columnCount >= 20) {
			foreach ($baOrder as $index => $fieldName) {
				if ($index >= $columnCount || $fieldName === null) {
					continue;
				}
				if ($fieldName === 'assigned_user_id' && in_array($fieldName, self::$skipFieldNames, true)) {
					continue;
				}
				$mapping[$fieldName] = $index;
			}
		}
		$englishOrder = self::getEnglishExportPositionalOrder();
		if (empty($mapping) && $columnCount <= 12) {
			foreach ($englishOrder as $index => $fieldName) {
				if ($index >= $columnCount || $fieldName === null) {
					continue;
				}
				if (in_array($fieldName, self::$skipFieldNames, true)) {
					continue;
				}
				$mapping[$fieldName] = $index;
			}
		}
		if (empty($mapping)) {
			$order = self::getPositionalFieldOrder();
			foreach ($order as $index => $fieldName) {
				if ($index >= $columnCount) {
					break;
				}
				if (in_array($fieldName, self::$skipFieldNames, true)) {
					continue;
				}
				$mapping[$fieldName] = $index;
			}
		}
		if (!isset($mapping['cf_857']) && isset($mapping['potentialname'])) {
			$mapping['cf_857'] = $mapping['potentialname'];
		}
		return self::sortFieldMappingForImport($mapping);
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
		return self::countKnownHeaderCells($cells) >= 3;
	}

	/**
	 * Numbers/Excel often exports row1 = Column1..N and row2 = real headers.
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

	public static function sortFieldMappingForImport(array $fieldMapping) {
		$priority = array(
			'related_to' => 10,
			'contact_id' => 20,
		);
		uksort($fieldMapping, function ($a, $b) use ($priority) {
			$pa = isset($priority[$a]) ? $priority[$a] : 50;
			$pb = isset($priority[$b]) ? $priority[$b] : 50;
			if ($pa === $pb) {
				return 0;
			}
			return ($pa < $pb) ? -1 : 1;
		});
		return $fieldMapping;
	}

	public static function tryRebuildHeaderIndexFromRawRow(Vtiger_Request $request, $user) {
		$filePath = Import_Utils_Helper::getImportFilePath($user);
		if (!$filePath || !is_readable($filePath)) {
			return null;
		}
		$delimiter = $request->get('delimiter') ? $request->get('delimiter') : ',';
		$handle = fopen($filePath, 'r');
		if (!$handle) {
			return null;
		}
		$headerCells = fgetcsv($handle, 0, $delimiter);
		fclose($handle);
		if (!is_array($headerCells) || empty($headerCells)) {
			return null;
		}
		$headerIndex = array();
		$known = array_keys(self::getHeaderFieldMap());
		$matched = 0;
		foreach ($headerCells as $index => $cell) {
			$cell = self::normalizeHeader(Import_Utils_Helper::normalizeCsvCell($cell));
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

		$hasMetaColumns = self::stagingTableHasImportMetaColumns($tableName);
		if ($hasMetaColumns) {
			$result = $adb->pquery(
				'SELECT recordid, ' . self::META_CUSTOMER_CODE . ', ' . self::META_PROJECT_ORDER . '
				 FROM ' . $tableName . '
				 WHERE status = ? AND recordid IS NOT NULL AND recordid != ?
				 ORDER BY id ASC',
				array(Import_Data_Action::$IMPORT_RECORD_CREATED, '')
			);
		} else {
			$result = $adb->pquery(
				'SELECT recordid FROM ' . $tableName . ' WHERE status = ? AND recordid IS NOT NULL AND recordid != ? ORDER BY id ASC',
				array(Import_Data_Action::$IMPORT_RECORD_CREATED, '')
			);
		}

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
			$projectOrder = '';
			if ($hasMetaColumns) {
				$projectOrder = trim((string) $adb->query_result($result, $i, self::META_PROJECT_ORDER));
			}
			if ($projectOrder === '') {
				$meta = isset(self::$importMetaByRecordId[$recordId]) ? self::$importMetaByRecordId[$recordId] : array();
				$projectOrder = isset($meta['project_order']) ? trim((string) $meta['project_order']) : '';
			}
			$options = array('force' => true);
			if ($projectOrder !== '') {
				$options['project_order'] = $projectOrder;
			}
			$code = ProjectCodeHandler::generateForPotential($recordId, $options);
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

		$usePositional = self::headersLookLikePlaceholders($headers);
		if (!$usePositional) {
			$rebuilt = self::tryRebuildHeaderIndexFromRawRow($request, $user);
			if (is_array($rebuilt) && !empty($rebuilt)) {
				$headerIndex = $rebuilt;
				$usePositional = false;
			}
		}

		if ($usePositional) {
			$fieldMapping = self::buildPositionalFieldMapping(php7_count($headers));
			$fieldMapping = self::filterFieldMapping($fieldMapping, $request->getModule());
			self::assertRequiredMapping($fieldMapping, $headerIndex);
			return $fieldMapping;
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
		$fieldMapping = self::filterFieldMapping($fieldMapping, $request->getModule());
		$fieldMapping = self::sortFieldMappingForImport($fieldMapping);
		self::assertRequiredMapping($fieldMapping, $headerIndex);
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

	public static function resetImportMetaState() {
		self::$importMetaByStagingId = array();
		self::$importMetaByRecordId = array();
	}

	public static function stagingTableHasImportMetaColumns($tableName) {
		$adb = PearDatabase::getInstance();
		$tableName = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $tableName);
		if ($tableName === '') {
			return false;
		}
		$result = $adb->pquery('SHOW COLUMNS FROM `' . $tableName . '` LIKE ?', array(self::META_PROJECT_ORDER));
		return ($result && $adb->num_rows($result) > 0);
	}
}
