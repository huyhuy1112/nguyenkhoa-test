<?php
/*+***********************************************************************************
 * Google Sheet → Leads. Config is shared with the Leads list button
 * (bace_lead_sheet_settings + bace_lead_sheet_sources via Leads_SheetImportService).
 *************************************************************************************/

require_once 'modules/Vtiger/helpers/NkApi/Adapter.php';
require_once 'modules/Leads/models/SheetImportService.php';

class NkApi_GoogleSheet_Adapter extends NkApi_Adapter {

	public function code() {
		return 'google_sheet';
	}

	public function label() {
		return 'Google Sheet';
	}

	public function description() {
		return 'Import Lead / Account (Tuibao) realtime từ nhiều Google Sheet. Cùng cấu hình với nút trên danh sách Lead.';
	}

	public function isImplemented() {
		return true;
	}

	public function hint() {
		return 'Quản lý & kiểm tra từng nguồn bên dưới. Share mỗi sheet với email service account (Viewer). Thêm/sửa chi tiết map cột cũng có thể làm ở Leads → Google Sheet.';
	}

	public function extraFields() {
		return array('spreadsheet_id', 'sheet_range', 'column_map', 'sources');
	}

	public function getConfigForAdmin() {
		$sheet = Leads_SheetImportService::getSettingsForAdmin();
		$sources = isset($sheet['sources']) && is_array($sheet['sources']) ? $sheet['sources'] : array();
		$hasSheet = false;
		foreach ($sources as $src) {
			if (!empty($src['spreadsheet_id'])) {
				$hasSheet = true;
				break;
			}
		}
		if (!$hasSheet && !empty($sheet['spreadsheet_id'])) {
			$hasSheet = true;
		}
		$configured = !empty($sheet['service_account_configured']) && $hasSheet;
		$status = 'not_configured';
		if (!empty($sheet['last_error'])) {
			$status = 'error';
		} elseif ($configured && !empty($sheet['enabled'])) {
			$status = 'ok';
		} elseif ($configured) {
			$status = 'disabled';
		}
		$columnMap = isset($sheet['column_map']) && is_array($sheet['column_map'])
			? $sheet['column_map']
			: array();
		$columnMapJson = json_encode($columnMap, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		if ($columnMapJson === false) {
			$columnMapJson = '{}';
		}
		$sourcesJson = json_encode($sources, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		if ($sourcesJson === false) {
			$sourcesJson = '[]';
		}
		$enabledCount = isset($sheet['enabled_sources_count']) ? (int) $sheet['enabled_sources_count'] : 0;
		$totalCount = isset($sheet['sources_count']) ? (int) $sheet['sources_count'] : count($sources);
		$sourcesUi = array();
		foreach ($sources as $src) {
			$sid = isset($src['id']) ? (int) $src['id'] : 0;
			$ssId = isset($src['spreadsheet_id']) ? (string) $src['spreadsheet_id'] : '';
			$sourcesUi[] = array(
				'id' => $sid,
				'name' => isset($src['name']) ? (string) $src['name'] : ('#' . $sid),
				'spreadsheet_id' => $ssId,
				'spreadsheet_short' => $ssId !== '' ? (strlen($ssId) > 18 ? (substr($ssId, 0, 10) . '…' . substr($ssId, -6)) : $ssId) : '—',
				'sheet_range' => isset($src['sheet_range']) ? (string) $src['sheet_range'] : 'Sheet1',
				'target_module' => isset($src['target_module']) ? (string) $src['target_module'] : 'leads',
				'target_label' => (isset($src['target_module']) && $src['target_module'] === 'accounts')
					? 'Accounts (Tuibao)'
					: 'Leads',
				'enabled' => !empty($src['enabled']),
				'last_poll_at' => isset($src['last_poll_at']) ? (string) $src['last_poll_at'] : '',
				'last_error' => isset($src['last_error']) ? (string) $src['last_error'] : '',
				'last_result' => isset($src['last_result']) ? (string) $src['last_result'] : '',
				'status' => !empty($src['last_error'])
					? 'error'
					: ((!empty($src['spreadsheet_id']) && !empty($src['enabled'])) ? 'ok' : 'idle'),
			);
		}
		return array(
			'code' => $this->code(),
			'label' => $this->label(),
			'description' => $this->description(),
			'implemented' => true,
			'enabled' => !empty($sheet['enabled']),
			'base_url' => isset($sheet['spreadsheet_id']) ? (string) $sheet['spreadsheet_id'] : '',
			'username' => isset($sheet['service_account_email']) ? (string) $sheet['service_account_email'] : '',
			'credentials_configured' => !empty($sheet['service_account_configured']),
			'status' => $status,
			'status_label' => NkApiConnection::statusLabel($status),
			'last_sync' => isset($sheet['last_poll_at']) ? (string) $sheet['last_poll_at'] : '',
			'last_error' => isset($sheet['last_error']) ? (string) $sheet['last_error'] : '',
			'last_result' => isset($sheet['last_result']) ? (string) $sheet['last_result'] : '',
			'extra_fields' => $this->extraFields(),
			'extra' => array(
				'spreadsheet_id' => isset($sheet['spreadsheet_id']) ? (string) $sheet['spreadsheet_id'] : '',
				'sheet_range' => isset($sheet['sheet_range']) ? (string) $sheet['sheet_range'] : 'Sheet1',
				'column_map' => $columnMap,
				'column_map_json' => $columnMapJson,
				'service_account_email' => isset($sheet['service_account_email']) ? (string) $sheet['service_account_email'] : '',
				'sources' => $sources,
				'sources_ui' => $sourcesUi,
				'sources_json' => $sourcesJson,
				'sources_count' => $totalCount,
				'enabled_sources_count' => $enabledCount,
				'sources_summary' => $totalCount > 0
					? ($enabledCount . '/' . $totalCount . ' nguồn đang bật')
					: 'Chưa có nguồn sheet',
			),
			'hint' => $this->hint(),
		);
	}

	public function save(array $payload, $userId = 0) {
		$sheetPayload = array();
		if (array_key_exists('enabled', $payload)) {
			$sheetPayload['enabled'] = !empty($payload['enabled']) ? 1 : 0;
		}
		if (array_key_exists('sources', $payload) && is_array($payload['sources'])) {
			$sheetPayload['sources'] = $payload['sources'];
		} elseif (array_key_exists('sources_json', $payload) && is_string($payload['sources_json'])) {
			$decoded = json_decode($payload['sources_json'], true);
			if (is_array($decoded)) {
				$sheetPayload['sources'] = $decoded;
			}
		} else {
			$url = '';
			if (array_key_exists('spreadsheet_id', $payload)) {
				$url = (string) $payload['spreadsheet_id'];
			} elseif (array_key_exists('base_url', $payload)) {
				$url = (string) $payload['base_url'];
			}
			if ($url !== '') {
				$sheetPayload['spreadsheet_id'] = $this->parseSpreadsheetId($url);
			}
			if (array_key_exists('sheet_range', $payload)) {
				$sheetPayload['sheet_range'] = $payload['sheet_range'];
			}
			if (array_key_exists('column_map', $payload)) {
				$sheetPayload['column_map'] = $payload['column_map'];
			}
			if (array_key_exists('source_name', $payload)) {
				$sheetPayload['source_name'] = $payload['source_name'];
			}
			if (array_key_exists('source_tag', $payload)) {
				$sheetPayload['source_tag'] = $payload['source_tag'];
			}
			if (array_key_exists('source_id', $payload)) {
				$sheetPayload['source_id'] = $payload['source_id'];
			}
		}
		if (array_key_exists('service_account_json', $payload) && trim((string) $payload['service_account_json']) !== '') {
			$sheetPayload['service_account_json'] = $payload['service_account_json'];
		} elseif (array_key_exists('api_key', $payload) && trim((string) $payload['api_key']) !== '') {
			$sheetPayload['service_account_json'] = $payload['api_key'];
		}
		Leads_SheetImportService::saveSettings($sheetPayload, $userId);
		Leads_SheetImportService::registerCron();

		$admin = $this->getConfigForAdmin();
		NkApiConnection::saveRow($this->code(), array(
			'enabled' => !empty($admin['enabled']) ? 1 : 0,
			'base_url' => $admin['base_url'],
			'status' => $admin['status'],
			'last_sync' => $admin['last_sync'],
			'last_error' => $admin['last_error'],
		), $userId);
		return $admin;
	}

	public function test(array $options = array()) {
		$sourceId = 0;
		if (isset($options['source_id'])) {
			$sourceId = (int) $options['source_id'];
		}
		$testAll = !empty($options['test_all']);
		if ($testAll) {
			$result = Leads_SheetImportService::testAllConnections();
		} else {
			$result = Leads_SheetImportService::testConnection($sourceId > 0 ? $sourceId : null);
		}
		$ok = !empty($result['success']);
		$msg = $ok
			? (isset($result['message']) ? (string) $result['message'] : 'Kết nối Google Sheet thành công.')
			: (isset($result['error']) ? (string) $result['error'] : (isset($result['message']) ? (string) $result['message'] : 'Không kết nối được Google Sheet.'));
		$fields = array(
			'status' => $ok ? 'ok' : 'error',
			'last_error' => $ok ? '' : $msg,
		);
		if ($ok) {
			$fields['last_sync'] = date('Y-m-d H:i:s');
		}
		NkApiConnection::saveRow($this->code(), $fields, 0);
		$out = array(
			'success' => $ok,
			'status' => $ok ? 'ok' : 'error',
			'message' => $msg,
			'imported' => isset($result['imported']) ? $result['imported'] : null,
			'source_id' => isset($result['source_id']) ? (int) $result['source_id'] : $sourceId,
			'source_name' => isset($result['source_name']) ? (string) $result['source_name'] : '',
			'error_code' => isset($result['error_code']) ? (string) $result['error_code'] : '',
			'raw_error' => isset($result['raw_error']) ? (string) $result['raw_error'] : '',
		);
		if (!empty($result['results']) && is_array($result['results'])) {
			$out['results'] = $result['results'];
			$out['passed'] = isset($result['passed']) ? (int) $result['passed'] : 0;
			$out['failed'] = isset($result['failed']) ? (int) $result['failed'] : 0;
			$out['tested'] = isset($result['tested']) ? (int) $result['tested'] : 0;
		}
		return $out;
	}

	public function isEnabled() {
		$s = Leads_SheetImportService::getSettings();
		return !empty($s['enabled']);
	}

	protected function parseSpreadsheetId($input) {
		return Leads_SheetImportService::parseSpreadsheetId($input);
	}
}
