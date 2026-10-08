<?php
/**
 * Google Sheet → Khách hàng nhượng quyền tiềm năng (ServiceContracts).
 */
require_once 'modules/ServiceContracts/models/ModernService.php';

class ServiceContracts_SheetIngestService_Helper {

	const TABLE_IMPORT = 'bace_sc_sheet_import';

	public static function installSchema(PearDatabase $adb = null) {
		if (!$adb) {
			$adb = PearDatabase::getInstance();
		}
		$adb->pquery(
			'CREATE TABLE IF NOT EXISTS ' . self::TABLE_IMPORT . ' (
				id INT NOT NULL AUTO_INCREMENT,
				sheet_row_key VARCHAR(191) NOT NULL,
				servicecontractsid INT NOT NULL DEFAULT 0,
				source_id INT NULL,
				raw_json MEDIUMTEXT NULL,
				imported_at DATETIME NULL,
				PRIMARY KEY (id),
				UNIQUE KEY uniq_sheet_row (sheet_row_key)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8',
			array()
		);
	}

	public static function defaultColumnMap() {
		return array(
			'full_name' => 'Họ tên',
			'phone' => 'Số điện thoại',
			'email' => 'Email',
			'business_note' => 'Ghi chú',
			'franchise_status' => 'Trạng thái',
			'data_source' => 'Nguồn',
			'referrer' => 'Người giới thiệu',
			'received_date' => 'Ngày tiếp nhận',
		);
	}

	public static function pollSource(
		array $source,
		$serviceAccountJsonOrPath,
		$fetchSheetValues,
		$makeRowKey,
		$updatePollMeta
	) {
		global $current_user;
		if (empty($current_user) || empty($current_user->id)) {
			$current_user = Users::getActiveAdminUser();
		}
		$adb = PearDatabase::getInstance();
		self::installSchema($adb);
		ServiceContracts_ModernService::installSchema($adb);

		$sourceId = (int) $source['id'];
		$spreadsheetId = trim((string) $source['spreadsheet_id']);
		$range = trim((string) $source['sheet_range']);
		if ($range === '') {
			$range = 'Sheet1';
		}
		$colMap = isset($source['column_map']) && is_array($source['column_map'])
			? $source['column_map']
			: self::defaultColumnMap();

		try {
			$rows = call_user_func($fetchSheetValues, $spreadsheetId, $range, $serviceAccountJsonOrPath);
		} catch (Exception $e) {
			$msg = $e->getMessage();
			call_user_func($updatePollMeta, $sourceId, $msg, '');
			return array('success' => false, 'error' => $msg, 'imported' => 0, 'source_id' => $sourceId);
		}

		if (!is_array($rows) || count($rows) < 2) {
			$summary = '0 rows (header only or empty)';
			call_user_func($updatePollMeta, $sourceId, '', $summary);
			return array(
				'success' => true,
				'imported' => 0,
				'skipped_existing' => 0,
				'errors' => array(),
				'summary' => $summary,
				'source_id' => $sourceId,
			);
		}

		$header = array();
		foreach ($rows[0] as $i => $h) {
			$header[$i] = trim((string) $h);
		}

		$imported = 0;
		$skippedExisting = 0;
		$errors = array();
		for ($i = 1; $i < count($rows); $i++) {
			$row = $rows[$i];
			if (!is_array($row)) {
				continue;
			}
			$sheetRowNum = $i + 1;
			$rowKey = call_user_func($makeRowKey, $spreadsheetId, $range, $sheetRowNum);
			if (self::importKeyExists($rowKey)) {
				$skippedExisting++;
				continue;
			}
			$assoc = array();
			foreach ($header as $hi => $h) {
				if ($h === '') {
					continue;
				}
				$assoc[$h] = isset($row[$hi]) ? trim((string) $row[$hi]) : '';
			}
			if (!array_filter($assoc, function ($v) { return trim((string) $v) !== ''; })) {
				continue;
			}
			try {
				$payload = self::mapRow($assoc, $colMap);
				if ($payload['full_name'] === '') {
					$errors[] = "Row {$sheetRowNum}: thiếu họ tên";
					continue;
				}
				if ($payload['phone'] === '') {
					$errors[] = "Row {$sheetRowNum}: thiếu số điện thoại";
					continue;
				}
				$dup = ServiceContracts_ModernService::checkDuplicateByPhone($payload['phone']);
				if (!empty($dup['match']['id'])) {
					self::recordImport($rowKey, (int) $dup['match']['id'], $assoc, $sourceId);
					$skippedExisting++;
					continue;
				}
				$saved = ServiceContracts_ModernService::saveFranchise($payload);
				$scId = 0;
				if (is_array($saved)) {
					$scId = (int) (isset($saved['id']) ? $saved['id'] : (isset($saved['servicecontractsid']) ? $saved['servicecontractsid'] : 0));
				}
				if ($scId <= 0) {
					$errors[] = "Row {$sheetRowNum}: không tạo được hồ sơ";
					continue;
				}
				self::recordImport($rowKey, $scId, $assoc, $sourceId);
				$imported++;
			} catch (Exception $ex) {
				$errors[] = "Row {$sheetRowNum}: " . $ex->getMessage();
			}
		}

		$summary = "nq imported={$imported}; skipped_existing={$skippedExisting}; errors=" . count($errors);
		$errText = count($errors) ? implode(' | ', array_slice($errors, 0, 5)) : '';
		call_user_func($updatePollMeta, $sourceId, $errText, $summary);
		return array(
			'success' => true,
			'imported' => $imported,
			'skipped_existing' => $skippedExisting,
			'errors' => $errors,
			'summary' => $summary,
			'source_id' => $sourceId,
			'target_module' => 'servicecontracts',
		);
	}

	protected static function mapRow(array $assoc, array $colMap) {
		$phone = preg_replace('/\D+/', '', self::cell($assoc, $colMap, 'phone'));
		if (strlen($phone) === 9 && preg_match('/^[3-9]/', $phone)) {
			$phone = '0' . $phone;
		}
		return array(
			'full_name' => self::cell($assoc, $colMap, 'full_name'),
			'phone' => $phone,
			'email' => self::cell($assoc, $colMap, 'email'),
			'business_note' => self::cell($assoc, $colMap, 'business_note'),
			'franchise_status' => self::cell($assoc, $colMap, 'franchise_status'),
			'data_source' => self::cell($assoc, $colMap, 'data_source'),
			'referrer' => self::cell($assoc, $colMap, 'referrer'),
			'received_date' => self::cell($assoc, $colMap, 'received_date'),
		);
	}

	protected static function cell(array $assoc, array $colMap, $field) {
		require_once 'modules/Leads/models/SheetImportService.php';
		$header = isset($colMap[$field]) ? $colMap[$field] : '';
		if (!is_array($header) && $header !== '' && $header !== null) {
			if (isset($assoc[$header])) {
				return trim((string) $assoc[$header]);
			}
			$want = Leads_SheetImportService::fold($header);
			foreach ($assoc as $k => $v) {
				if (Leads_SheetImportService::fold($k) === $want) {
					return trim((string) $v);
				}
			}
		}
		$aliases = array(
			'full_name' => array('Họ tên', 'Họ và tên', 'Tên', 'name', 'full_name'),
			'phone' => array('Số điện thoại', 'SĐT', 'SDT', 'Điện thoại', 'phone', 'mobile'),
			'email' => array('Email', 'email'),
			'business_note' => array('Ghi chú', 'Ghi chú mô hình', 'Mô hình', 'business_note'),
			'franchise_status' => array('Trạng thái', 'Trạng thái nhượng quyền', 'franchise_status'),
			'data_source' => array('Nguồn', 'Nguồn dữ liệu', 'data_source'),
			'referrer' => array('Người giới thiệu', 'Giới thiệu', 'referrer'),
			'received_date' => array('Ngày tiếp nhận', 'Ngày nhận', 'received_date'),
		);
		$list = isset($aliases[$field]) ? $aliases[$field] : array($field);
		foreach ($assoc as $k => $v) {
			$fk = Leads_SheetImportService::fold($k);
			foreach ($list as $alias) {
				if ($fk === Leads_SheetImportService::fold($alias)) {
					return trim((string) $v);
				}
			}
		}
		return '';
	}

	protected static function importKeyExists($rowKey) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT id FROM ' . self::TABLE_IMPORT . ' WHERE sheet_row_key = ? LIMIT 1',
			array($rowKey)
		);
		return $res && $adb->num_rows($res) > 0;
	}

	protected static function recordImport($rowKey, $scId, array $assoc, $sourceId) {
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'INSERT INTO ' . self::TABLE_IMPORT . ' (sheet_row_key, servicecontractsid, source_id, raw_json, imported_at) VALUES (?,?,?,?,?)',
			array($rowKey, (int) $scId, (int) $sourceId, json_encode($assoc), date('Y-m-d H:i:s'))
		);
	}
}
