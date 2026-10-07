<?php
/*+***********************************************************************************
 * MISA AMIS Kế toán — ACT Open API.
 * Chốt A: đơn CRM → Đơn đặt hàng (sa_order) trên AMIS; không qua bước sinh CT trước.
 * Trạng thái về CRM qua callback, và khi mở menu Hóa đơn.
 *
 * App ID, mã kết nối, mã công ty lưu trong Cài đặt → Tích hợp (nk_api_connection)
 * hoặc config.misa.local.php. Không ghi mã kết nối vào source.
 *************************************************************************************/

require_once 'modules/Vtiger/helpers/NkApi/Adapter.php';

class NkApi_Misa_Adapter extends NkApi_Adapter {

	const DEFAULT_BASE = 'https://actapp.misa.vn';
	const DEFAULT_APP_ID = '869676a3-f361-40d5-b510-5ceb4f730d5a';
	const DEFAULT_ORG = 'km4kb96v';

	public function code() {
		return 'misa';
	}

	public function label() {
		return 'MISA';
	}

	public function description() {
		return 'Đẩy đơn hàng CRM sang AMIS thành Đơn đặt hàng. Kế toán lập chứng từ bán hàng trên MISA khi cần; CRM nhận trạng thái qua callback.';
	}

	public function isImplemented() {
		return true;
	}

	public function hint() {
		return 'Host https://actapp.misa.vn. Username = App ID, API key = mã kết nối. Mã công ty: km4kb96v. Chuyển qua MISA → tab Đơn đặt hàng. Callback: modules/Invoice/callbacks/MisaAct.php — khai trong MISA → Thiết lập → Kết nối ứng dụng.';
	}

	public function save(array $payload, $userId = 0) {
		$row = NkApiConnection::getRow($this->code());
		$creds = isset($row['credentials']) && is_array($row['credentials']) ? $row['credentials'] : array();
		$extra = isset($row['extra']) && is_array($row['extra']) ? $row['extra'] : array();

		if (array_key_exists('username', $payload) && trim((string) $payload['username']) !== '') {
			$creds['username'] = trim((string) $payload['username']);
			$creds['app_id'] = $creds['username'];
		}
		if (array_key_exists('api_key', $payload) && trim((string) $payload['api_key']) !== '') {
			$creds['api_key'] = trim((string) $payload['api_key']);
		}
		if (array_key_exists('password', $payload) && (string) $payload['password'] !== '') {
			$creds['password'] = (string) $payload['password'];
		}
		if (empty($extra['org_company_code'])) {
			$extra['org_company_code'] = self::DEFAULT_ORG;
		}

		$enabled = array_key_exists('enabled', $payload) ? (!empty($payload['enabled']) ? 1 : 0) : (!empty($row['enabled']) ? 1 : 0);
		$baseUrl = array_key_exists('base_url', $payload) ? trim((string) $payload['base_url']) : (isset($row['base_url']) ? (string) $row['base_url'] : '');
		if ($baseUrl === '') {
			$baseUrl = self::DEFAULT_BASE;
		}

		$status = isset($row['status']) ? (string) $row['status'] : 'not_configured';
		if ($status === '' || $status === 'not_configured' || $status === 'coming_soon') {
			$status = !empty($creds['api_key']) ? 'idle' : 'not_configured';
		}

		NkApiConnection::saveRow($this->code(), array(
			'enabled' => $enabled,
			'base_url' => $baseUrl,
			'credentials' => $creds,
			'extra' => $extra,
			'status' => $status,
		), $userId);

		$this->logActivity(
			'config',
			$status === 'not_configured' ? 'warning' : 'success',
			'MISA — cập nhật cấu hình',
			$enabled ? 'Đã bật kết nối' : 'Đã tắt kết nối',
			array('user_id' => (int) $userId)
		);
		return $this->getConfigForAdmin();
	}

	protected function doTest() {
		$this->prepareConnection();
		$token = $this->accessToken(true);
		$info = $this->post('/apir/sync/actopen/get_company_info', array(
			'app_id' => $this->appId(),
			'branch_id' => null,
		), $token);
		$name = '';
		if (!empty($info['Data'])) {
			$data = $this->decodeData($info['Data']);
			if (is_array($data) && !empty($data['company_name'])) {
				$name = (string) $data['company_name'];
			}
		}
		NkApiConnection::saveRow($this->code(), array(
			'status' => 'ok',
			'last_sync' => date('Y-m-d H:i:s'),
			'last_error' => '',
		), 0);
		$message = $name !== '' ? ('Đã kết nối MISA: ' . $name) : 'Đã kết nối MISA.';
		return array(
			'success' => true,
			'status' => 'ok',
			'message' => $message,
		);
	}

	/**
	 * @param Vtiger_Record_Model $soModel
	 * @return array
	 */
	public function transfer($soModel) {
		$this->prepareConnection();
		if (!$this->isEnabled()) {
			return array('error' => 'Kết nối MISA đang tắt. Bật trong Cài đặt → Tích hợp hệ thống.');
		}
		if ($this->accessCode() === '' || $this->appId() === '') {
			return array('error' => 'Chưa có App ID hoặc mã kết nối MISA. Vào Cài đặt → Tích hợp hệ thống.');
		}
		require_once 'modules/Invoice/models/MisaSyncService.php';
		try {
			return Invoice_MisaSyncService::push($this, $soModel);
		} catch (Exception $e) {
			$this->logSync('error', 'MISA — không gửi được Đơn đặt hàng', $e->getMessage());
			return array('error' => $e->getMessage());
		}
	}

	public function prepareConnection() {
		$row = NkApiConnection::getRow($this->code());
		$creds = isset($row['credentials']) && is_array($row['credentials']) ? $row['credentials'] : array();
		$extra = isset($row['extra']) && is_array($row['extra']) ? $row['extra'] : array();
		$changed = false;

		$local = $this->readLocalSecret();
		if (!empty($local['access_code']) && (empty($creds['api_key']) || $creds['api_key'] !== $local['access_code'])) {
			$creds['api_key'] = $local['access_code'];
			unset($extra['access_token'], $extra['token_expires']);
			$changed = true;
		}
		if (empty($creds['app_id']) && empty($creds['username'])) {
			$creds['app_id'] = !empty($local['app_id']) ? $local['app_id'] : self::DEFAULT_APP_ID;
			$creds['username'] = $creds['app_id'];
			$changed = true;
		} elseif (empty($creds['app_id']) && !empty($creds['username'])) {
			$creds['app_id'] = $creds['username'];
			$changed = true;
		}
		if (empty($extra['org_company_code'])) {
			$extra['org_company_code'] = !empty($local['org_company_code']) ? $local['org_company_code'] : self::DEFAULT_ORG;
			$changed = true;
		}
		$base = isset($row['base_url']) ? trim((string) $row['base_url']) : '';
		if ($base === '') {
			$base = self::DEFAULT_BASE;
			$changed = true;
		}
		$status = isset($row['status']) ? (string) $row['status'] : '';
		$enabled = !empty($row['enabled']);
		if (!$enabled && $creds['api_key'] !== '' && in_array($status, array('', 'not_configured', 'coming_soon'), true)) {
			$enabled = true;
			$status = 'idle';
			$changed = true;
		}
		if ($changed) {
			NkApiConnection::saveRow($this->code(), array(
				'enabled' => $enabled ? 1 : 0,
				'base_url' => $base,
				'credentials' => $creds,
				'extra' => $extra,
				'status' => $status !== '' ? $status : 'idle',
			), 0);
		}
	}

	public function appId() {
		$row = NkApiConnection::getRow($this->code());
		$creds = isset($row['credentials']) && is_array($row['credentials']) ? $row['credentials'] : array();
		if (!empty($creds['app_id'])) {
			return trim((string) $creds['app_id']);
		}
		if (!empty($creds['username'])) {
			return trim((string) $creds['username']);
		}
		return self::DEFAULT_APP_ID;
	}

	public function orgCompanyCode() {
		$row = NkApiConnection::getRow($this->code());
		$extra = isset($row['extra']) && is_array($row['extra']) ? $row['extra'] : array();
		$code = isset($extra['org_company_code']) ? trim((string) $extra['org_company_code']) : '';
		return $code !== '' ? $code : self::DEFAULT_ORG;
	}

	public function accessCode() {
		$row = NkApiConnection::getRow($this->code());
		$creds = isset($row['credentials']) && is_array($row['credentials']) ? $row['credentials'] : array();
		if (!empty($creds['api_key'])) {
			return trim((string) $creds['api_key']);
		}
		$local = $this->readLocalSecret();
		return isset($local['access_code']) ? trim((string) $local['access_code']) : '';
	}

	public function accessToken($force = false) {
		$row = NkApiConnection::getRow($this->code());
		$extra = isset($row['extra']) && is_array($row['extra']) ? $row['extra'] : array();
		$expires = isset($extra['token_expires']) ? strtotime((string) $extra['token_expires']) : 0;
		if (!$force && !empty($extra['access_token']) && $expires > time() + 120) {
			return (string) $extra['access_token'];
		}
		$body = $this->post('/api/oauth/actopen/connect', array(
			'app_id' => $this->appId(),
			'access_code' => $this->accessCode(),
			'org_company_code' => $this->orgCompanyCode(),
		), '');
		if (empty($body['Success'])) {
			$msg = isset($body['ErrorMessage']) ? (string) $body['ErrorMessage'] : 'MISA từ chối kết nối.';
			throw new Exception($msg);
		}
		$data = $this->decodeData(isset($body['Data']) ? $body['Data'] : '');
		$token = is_array($data) && !empty($data['access_token']) ? (string) $data['access_token'] : '';
		if ($token === '') {
			throw new Exception('MISA không trả access token.');
		}
		$extra['access_token'] = $token;
		$extra['token_expires'] = is_array($data) && !empty($data['expired_time'])
			? (string) $data['expired_time']
			: date('Y-m-d H:i:s', time() + 11 * 3600);
		NkApiConnection::saveRow($this->code(), array('extra' => $extra, 'status' => 'ok', 'last_error' => ''), 0);
		return $token;
	}

	public function saveVoucher(array $voucher, array $dictionary) {
		$payload = array(
			'app_id' => $this->appId(),
			'org_company_code' => $this->orgCompanyCode(),
			'voucher' => array($voucher),
			'dictionary' => $dictionary,
		);
		try {
			$body = $this->post('/apir/sync/actopen/save', $payload, $this->accessToken(false));
		} catch (Exception $e) {
			if (stripos($e->getMessage(), 'token') === false && stripos($e->getMessage(), 'Expired') === false) {
				throw $e;
			}
			$body = $this->post('/apir/sync/actopen/save', $payload, $this->accessToken(true));
		}
		if (is_array($body) && isset($body['ErrorCode']) && (string) $body['ErrorCode'] === 'ExpiredToken') {
			$body = $this->post('/apir/sync/actopen/save', $payload, $this->accessToken(true));
		}
		if (empty($body['Success'])) {
			$msg = isset($body['ErrorMessage']) && $body['ErrorMessage'] !== ''
				? (string) $body['ErrorMessage']
				: 'MISA không nhận Đơn đặt hàng.';
			$code = isset($body['ErrorCode']) ? (string) $body['ErrorCode'] : '';
			throw new Exception($code !== '' ? ($msg . ' (' . $code . ')') : $msg);
		}
		NkApiConnection::saveRow($this->code(), array(
			'status' => 'ok',
			'last_sync' => date('Y-m-d H:i:s'),
			'last_error' => '',
		), 0);
		return $body;
	}

	/**
	 * Kết quả callback mà MISA không gọi tới được CRM.
	 * @return array
	 */
	public function fetchCallbackResults($fromDate, $toDate) {
		$body = $this->post('/apir/sync/actopen/get_call_back_detail_error', array(
			'app_id' => $this->appId(),
			'from_date' => $fromDate,
			'to_date' => $toDate,
			'skip' => '0',
			'take' => '100',
		), $this->accessToken(false));
		if (empty($body['Success'])) {
			return array();
		}
		$data = $this->decodeData(isset($body['Data']) ? $body['Data'] : array());
		return is_array($data) ? $data : array();
	}

	public function post($path, array $payload, $token) {
		$base = NkApiConnection::getRow($this->code());
		$root = isset($base['base_url']) ? rtrim((string) $base['base_url'], '/') : '';
		if ($root === '') {
			$root = self::DEFAULT_BASE;
		}
		$url = $root . $path;
		$json = json_encode($payload, JSON_UNESCAPED_UNICODE);
		$headers = array('Content-Type: application/json');
		if ($token !== '') {
			$headers[] = 'X-MISA-AccessToken: ' . $token;
		}
		if (!function_exists('curl_init')) {
			throw new Exception('Máy chủ thiếu PHP curl, không gọi được MISA.');
		}
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		$raw = curl_exec($ch);
		$err = curl_error($ch);
		$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($raw === false) {
			throw new Exception('Không gọi được MISA: ' . $err);
		}
		$decoded = json_decode($raw, true);
		if (!is_array($decoded)) {
			throw new Exception('MISA trả về không phải JSON (HTTP ' . $code . ').');
		}
		return $decoded;
	}

	public function decodeData($data) {
		if (is_array($data)) {
			return $data;
		}
		$text = trim((string) $data);
		if ($text === '') {
			return array();
		}
		$decoded = json_decode($text, true);
		if (is_string($decoded)) {
			$again = json_decode($decoded, true);
			if (is_array($again)) {
				return $again;
			}
		}
		return is_array($decoded) ? $decoded : array();
	}

	protected function readLocalSecret() {
		$path = dirname(__FILE__) . '/../../../../config.misa.local.php';
		if (!is_file($path)) {
			return array();
		}
		$loaded = include $path;
		return is_array($loaded) ? $loaded : array();
	}
}
