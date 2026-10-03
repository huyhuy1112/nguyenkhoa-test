<?php
/*+***********************************************************************************
 * Google Sheet → Accounts (Tuibao / khách nhượng quyền).
 * Called from Leads_SheetImportService when source.target_module = accounts.
 *************************************************************************************/

require_once 'modules/Accounts/helpers/FranchiseContractService.php';

class Accounts_SheetIngestService_Helper {

	const TABLE_IMPORT = 'bace_account_sheet_import';

	public static function installSchema(PearDatabase $adb = null) {
		if (!$adb) {
			$adb = PearDatabase::getInstance();
		}
		$adb->pquery(
			'CREATE TABLE IF NOT EXISTS ' . self::TABLE_IMPORT . ' (
				id INT(11) NOT NULL AUTO_INCREMENT,
				sheet_row_key VARCHAR(191) NOT NULL,
				accountid INT(19) NOT NULL,
				source_id INT(11) DEFAULT NULL,
				raw_json MEDIUMTEXT,
				imported_at DATETIME DEFAULT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY uniq_sheet_row (sheet_row_key),
				KEY idx_account (accountid),
				KEY idx_source (source_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8',
			array()
		);
		Accounts_FranchiseContractService_Helper::ensureFranchiseFields();
	}

	public static function defaultColumnMap() {
		return array(
			'accountname' => 'accountname',
			'phone' => 'phone',
			'email' => 'email',
			'tb_party_b_name' => '',
			'tb_party_b_phone' => '',
			'tb_party_b_email' => '',
			'tb_party_b_cccd' => '',
			'tb_store_address' => '',
			'tb_contract_no' => '',
			'bill_street' => '',
			'account_no' => '',
		);
	}

	public static function fieldHeaderAliases() {
		return array(
			'accountname' => array(
				'accountname', 'account name', 'organization name', 'ten cong ty', 'ten khach hang',
				'ten', 'company', 'cong ty', 'ho ten', 'name',
			),
			'phone' => array(
				'phone', 'sdt', 'so dien thoai', 'dien thoai', 'mobile', 'tel', 'primary phone',
			),
			'email' => array('email', 'e mail', 'mail'),
			'tb_party_b_name' => array(
				'tb_party_b_name', 'ho ten ben b', 'ben b', 'nguoi dai dien', 'dai dien',
			),
			'tb_party_b_phone' => array(
				'tb_party_b_phone', 'sdt ben b', 'dien thoai ben b', 'phone ben b',
			),
			'tb_party_b_email' => array('tb_party_b_email', 'email ben b'),
			'tb_party_b_cccd' => array('tb_party_b_cccd', 'cccd', 'cmnd', 'so cccd'),
			'tb_store_address' => array(
				'tb_store_address', 'dia chi cua hang', 'dia chi shop', 'cua hang', 'store address',
			),
			'tb_contract_no' => array('tb_contract_no', 'so hop dong', 'contract no', 'ma hop dong'),
			'bill_street' => array('bill_street', 'dia chi', 'address', 'billing address'),
			'account_no' => array('account_no', 'customer code', 'ma khach hang', 'ma kh'),
		);
	}

	/**
	 * Poll one Accounts-targeted sheet source.
	 * @param array $source
	 * @param string $serviceAccountJsonOrPath
	 * @param callable $fetchSheetValues fn($spreadsheetId,$range,$sa)
	 * @param callable $makeRowKey fn($spreadsheetId,$range,$rowNum)
	 * @param callable $updatePollMeta fn($sourceId,$error,$result)
	 * @return array
	 */
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
			if (self::isEmptyRow($assoc)) {
				continue;
			}
			try {
				$payload = self::mapRowToAccountPayload($assoc, $colMap, $rowKey, $source);
				if ($payload['accountname'] === '') {
					$errors[] = "Row {$sheetRowNum}: thiếu tên công ty / khách hàng";
					continue;
				}
				if ($payload['phone'] === '' && $payload['tb_party_b_phone'] === '') {
					$errors[] = "Row {$sheetRowNum}: thiếu số điện thoại";
					continue;
				}
				$existingId = self::findAccountIdByPhone(
					$payload['phone'] !== '' ? $payload['phone'] : $payload['tb_party_b_phone']
				);
				if ($existingId > 0) {
					// Already in CRM — mark row imported to avoid re-processing; do not duplicate Account.
					self::recordImport($rowKey, $existingId, $assoc, $sourceId);
					$skippedExisting++;
					continue;
				}
				$accountId = self::createAccount($payload);
				if ($accountId <= 0) {
					$errors[] = "Row {$sheetRowNum}: không tạo được Account";
					continue;
				}
				self::recordImport($rowKey, $accountId, $assoc, $sourceId);
				$imported++;
			} catch (Exception $ex) {
				$errors[] = "Row {$sheetRowNum}: " . $ex->getMessage();
			}
		}

		$summary = "accounts imported={$imported}; skipped_existing={$skippedExisting}; errors=" . count($errors);
		$errText = count($errors) ? implode(' | ', array_slice($errors, 0, 5)) : '';
		call_user_func($updatePollMeta, $sourceId, $errText, $summary);

		return array(
			'success' => true,
			'imported' => $imported,
			'skipped_existing' => $skippedExisting,
			'errors' => $errors,
			'summary' => $summary,
			'source_id' => $sourceId,
			'target_module' => 'accounts',
		);
	}

	protected static function isEmptyRow(array $assoc) {
		foreach ($assoc as $v) {
			if (trim((string) $v) !== '') {
				return false;
			}
		}
		return true;
	}

	protected static function importKeyExists($rowKey) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT id FROM ' . self::TABLE_IMPORT . ' WHERE sheet_row_key = ? LIMIT 1',
			array($rowKey)
		);
		return ($res && $adb->num_rows($res) > 0);
	}

	protected static function recordImport($rowKey, $accountId, array $assoc, $sourceId = 0) {
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'INSERT INTO ' . self::TABLE_IMPORT . ' (sheet_row_key, accountid, source_id, raw_json, imported_at) VALUES (?,?,?,?,?)',
			array(
				$rowKey,
				(int) $accountId,
				$sourceId > 0 ? (int) $sourceId : null,
				json_encode($assoc, JSON_UNESCAPED_UNICODE),
				date('Y-m-d H:i:s'),
			)
		);
	}

	public static function normalizePhone($raw) {
		$phone = preg_replace('/\D+/', '', (string) $raw);
		if (strlen($phone) === 9 && preg_match('/^[3-9]/', $phone)) {
			$phone = '0' . $phone;
		}
		if (strlen($phone) > 11) {
			$phone = substr($phone, -10);
		}
		return $phone;
	}

	public static function findAccountIdByPhone($phone) {
		$phone = self::normalizePhone($phone);
		if ($phone === '') {
			return 0;
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT a.accountid
			 FROM vtiger_account a
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = a.accountid AND ce.deleted = 0
			 LEFT JOIN vtiger_accountscf cf ON cf.accountid = a.accountid
			 WHERE REPLACE(REPLACE(REPLACE(IFNULL(a.phone,\'\'), \' \', \'\'), \'-\', \'\'), \'.\', \'\') = ?
			    OR REPLACE(REPLACE(REPLACE(IFNULL(cf.tb_party_b_phone,\'\'), \' \', \'\'), \'-\', \'\'), \'.\', \'\') = ?
			 LIMIT 1',
			array($phone, $phone)
		);
		if ($res && $adb->num_rows($res) > 0) {
			return (int) $adb->query_result($res, 0, 'accountid');
		}
		return 0;
	}

	protected static function fold($s) {
		require_once 'modules/Leads/models/SheetImportService.php';
		return Leads_SheetImportService::fold($s);
	}

	protected static function getMappedCell(array $assoc, array $colMap, $field) {
		$header = isset($colMap[$field]) ? $colMap[$field] : '';
		if (!is_array($header) && $header !== '' && $header !== null) {
			if (isset($assoc[$header])) {
				return trim((string) $assoc[$header]);
			}
			$want = self::fold($header);
			foreach ($assoc as $k => $v) {
				if (self::fold($k) === $want) {
					return trim((string) $v);
				}
			}
		}
		$aliases = self::fieldHeaderAliases();
		$wantList = isset($aliases[$field]) ? $aliases[$field] : array($field);
		foreach ($assoc as $k => $v) {
			$fk = self::fold($k);
			foreach ($wantList as $alias) {
				if ($fk === self::fold($alias)) {
					return trim((string) $v);
				}
			}
		}
		return '';
	}

	/**
	 * @param array $assoc
	 * @param array $colMap
	 * @param string $rowKey
	 * @param array|null $source
	 * @return array
	 */
	public static function mapRowToAccountPayload(array $assoc, array $colMap, $rowKey, $source = null) {
		$accountName = self::getMappedCell($assoc, $colMap, 'accountname');
		$partyName = self::getMappedCell($assoc, $colMap, 'tb_party_b_name');
		if ($accountName === '' && $partyName !== '') {
			$accountName = $partyName;
		}
		$phone = self::normalizePhone(self::getMappedCell($assoc, $colMap, 'phone'));
		$partyPhone = self::normalizePhone(self::getMappedCell($assoc, $colMap, 'tb_party_b_phone'));
		if ($phone === '' && $partyPhone !== '') {
			$phone = $partyPhone;
		}
		if ($partyPhone === '' && $phone !== '') {
			$partyPhone = $phone;
		}
		$email = self::getMappedCell($assoc, $colMap, 'email');
		$partyEmail = self::getMappedCell($assoc, $colMap, 'tb_party_b_email');
		if ($partyEmail === '' && $email !== '') {
			$partyEmail = $email;
		}
		$storeAddr = self::getMappedCell($assoc, $colMap, 'tb_store_address');
		$billStreet = self::getMappedCell($assoc, $colMap, 'bill_street');
		if ($storeAddr === '' && $billStreet !== '') {
			$storeAddr = $billStreet;
		}
		if ($billStreet === '' && $storeAddr !== '') {
			$billStreet = $storeAddr;
		}

		$sourceName = '';
		$sourceId = 0;
		if (is_array($source)) {
			$sourceId = isset($source['id']) ? (int) $source['id'] : 0;
			$sourceName = isset($source['name']) ? trim((string) $source['name']) : '';
		}

		return array(
			'accountname' => $accountName,
			'phone' => $phone,
			'email1' => $email,
			'tb_party_b_name' => $partyName !== '' ? $partyName : $accountName,
			'tb_party_b_phone' => $partyPhone,
			'tb_party_b_email' => $partyEmail,
			'tb_party_b_cccd' => self::getMappedCell($assoc, $colMap, 'tb_party_b_cccd'),
			'tb_store_address' => $storeAddr,
			'tb_contract_no' => self::getMappedCell($assoc, $colMap, 'tb_contract_no'),
			'bill_street' => $billStreet,
			'account_no' => self::getMappedCell($assoc, $colMap, 'account_no'),
			'sheet_row_key' => $rowKey,
			'sheet_source_id' => $sourceId,
			'sheet_source_name' => $sourceName,
		);
	}

	/**
	 * @param array $payload
	 * @return int accountid
	 */
	public static function createAccount(array $payload) {
		global $current_user;
		if (empty($current_user) || empty($current_user->id)) {
			$current_user = Users::getActiveAdminUser();
		}
		Accounts_FranchiseContractService_Helper::ensureFranchiseFields();

		$record = Vtiger_Record_Model::getCleanInstance('Accounts');
		$record->set('mode', '');
		$record->set('accountname', $payload['accountname']);
		if (!empty($payload['phone'])) {
			$record->set('phone', $payload['phone']);
		}
		if (!empty($payload['email1'])) {
			$record->set('email1', $payload['email1']);
		}
		if (!empty($payload['bill_street'])) {
			$record->set('bill_street', $payload['bill_street']);
		}
		if (!empty($payload['account_no'])) {
			$record->set('account_no', $payload['account_no']);
		}
		$franchiseFields = array(
			'tb_party_b_name', 'tb_party_b_phone', 'tb_party_b_email', 'tb_party_b_cccd',
			'tb_store_address', 'tb_contract_no',
		);
		foreach ($franchiseFields as $fname) {
			if (!empty($payload[$fname])) {
				$record->set($fname, $payload[$fname]);
			}
		}
		// Description note for sales
		$noteParts = array('Nguồn: Google Sheet Tuibao / nhượng quyền');
		if (!empty($payload['sheet_source_name'])) {
			$noteParts[] = 'Sheet: ' . $payload['sheet_source_name'];
		}
		if (!empty($payload['sheet_row_key'])) {
			$noteParts[] = 'row_key=' . $payload['sheet_row_key'];
		}
		$record->set('description', implode(' | ', $noteParts));
		$record->set('assigned_user_id', (int) $current_user->id);
		$record->save();
		return (int) $record->getId();
	}
}
