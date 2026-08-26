<?php
/*+***********************************************************************************
 * Zalo Official Account — OAuth + OpenAPI adapter for Settings → Tích hợp hệ thống.
 *
 * Docs: https://docs.zaloplatforms.com/docs/OA/bat-dau/xac-thuc-va-uy-quyen-cho-ung-dung-new
 *************************************************************************************/

require_once 'modules/Vtiger/helpers/NkApi/Adapter.php';

class NkApi_ZaloOa_Adapter extends NkApi_Adapter {

	const TOKEN_URL = 'https://oauth.zaloapp.com/v4/oa/access_token';
	const AUTH_URL = 'https://oauth.zaloapp.com/v4/oa/permission';
	const OA_INFO_URL = 'https://openapi.zalo.me/v2.0/oa/getoa';
	const API_BASE = 'https://openapi.zalo.me/v2.0';

	public function code() {
		return 'zalo_oa';
	}

	public function label() {
		return 'Zalo OA';
	}

	public function description() {
		return 'Zalo Official Account — gửi tin / đồng bộ hội thoại qua OpenAPI.';
	}

	public function isImplemented() {
		return true;
	}

	public function hint() {
		return 'Nhập App ID, Secret Key, OA ID từ Zalo Developers. Lấy Refresh Token qua “Kết nối Zalo OA” (OAuth) hoặc dán thủ công. Access token tự gia hạn từ refresh token. Webhook script OA sẽ đẩy dữ liệu trực tiếp vào Leads.';
	}

	public function icon() {
		return 'zalo_oa';
	}

	public function extraFields() {
		return array('app_id', 'oa_id', 'secret_key', 'refresh_token', 'access_token');
	}

	public function getConfigForAdmin() {
		$row = NkApiConnection::getRow($this->code());
		$creds = isset($row['credentials']) && is_array($row['credentials']) ? $row['credentials'] : array();
		$extra = isset($row['extra']) && is_array($row['extra']) ? $row['extra'] : array();

		$appId = isset($creds['app_id']) ? (string) $creds['app_id'] : '';
		$oaId = isset($creds['oa_id']) ? (string) $creds['oa_id'] : '';
		$hasSecret = !empty($creds['secret_key']);
		$hasRefresh = !empty($creds['refresh_token']);
		$hasAccess = !empty($creds['access_token']);
		$expiresAt = isset($creds['expires_at']) ? (string) $creds['expires_at'] : '';
		$oaName = isset($extra['oa_name']) ? (string) $extra['oa_name'] : '';

		$status = isset($row['status']) ? (string) $row['status'] : 'not_configured';
		if (empty($row['enabled']) && $status === 'ok') {
			$status = 'disabled';
		}
		if ($status === '' || $status === null) {
			$status = $this->hasCredentials($row) ? 'idle' : 'not_configured';
		}

		return array(
			'code' => $this->code(),
			'label' => $this->label(),
			'description' => $this->description(),
			'icon' => $this->icon(),
			'implemented' => true,
			'enabled' => !empty($row['enabled']),
			'base_url' => self::API_BASE,
			'username' => $oaName !== '' ? $oaName : $oaId,
			'credentials_configured' => $this->hasCredentials($row),
			'status' => $status,
			'status_label' => NkApiConnection::statusLabel($status),
			'last_sync' => isset($row['last_sync']) ? (string) $row['last_sync'] : '',
			'last_error' => isset($row['last_error']) ? (string) $row['last_error'] : '',
			'extra_fields' => $this->extraFields(),
			'extra' => array(
				'app_id' => $appId,
				'oa_id' => $oaId,
				'oa_name' => $oaName,
				'secret_configured' => $hasSecret,
				'refresh_token_configured' => $hasRefresh,
				'access_token_configured' => $hasAccess,
				'expires_at' => $expiresAt,
				'callback_url' => self::callbackUrl(),
				'webhook_url' => self::webhookUrl(),
			),
			'hint' => $this->hint(),
		);
	}

	public function save(array $payload, $userId = 0) {
		$row = NkApiConnection::getRow($this->code());
		$creds = isset($row['credentials']) && is_array($row['credentials']) ? $row['credentials'] : array();
		$extra = isset($row['extra']) && is_array($row['extra']) ? $row['extra'] : array();

		if (array_key_exists('app_id', $payload)) {
			$creds['app_id'] = trim((string) $payload['app_id']);
		}
		if (array_key_exists('oa_id', $payload)) {
			$creds['oa_id'] = trim((string) $payload['oa_id']);
		}
		if (array_key_exists('secret_key', $payload) && trim((string) $payload['secret_key']) !== '') {
			$creds['secret_key'] = trim((string) $payload['secret_key']);
		}
		if (array_key_exists('refresh_token', $payload) && trim((string) $payload['refresh_token']) !== '') {
			$creds['refresh_token'] = trim((string) $payload['refresh_token']);
		}
		if (array_key_exists('access_token', $payload) && trim((string) $payload['access_token']) !== '') {
			$creds['access_token'] = trim((string) $payload['access_token']);
		}

		$enabled = !empty($payload['enabled']);
		$configured = $this->hasCredentials(array('credentials' => $creds));
		$status = $configured ? (isset($row['status']) && $row['status'] === 'ok' ? 'ok' : 'idle') : 'not_configured';

		NkApiConnection::saveRow($this->code(), array(
			'enabled' => $enabled ? 1 : 0,
			'base_url' => self::API_BASE,
			'credentials' => $creds,
			'extra' => $extra,
			'status' => $status,
			'last_error' => $configured ? (isset($row['last_error']) ? $row['last_error'] : '') : 'Chưa đủ App ID / Secret / Refresh Token.',
		), $userId);

		return $this->getConfigForAdmin();
	}

	public function test() {
		try {
			$token = $this->getValidAccessToken(0);
			$info = $this->fetchOaInfo($token);
			$oaName = '';
			if (is_array($info)) {
				if (!empty($info['data']['name'])) {
					$oaName = (string) $info['data']['name'];
				} elseif (!empty($info['data']['oa_name'])) {
					$oaName = (string) $info['data']['oa_name'];
				}
			}
			$row = NkApiConnection::getRow($this->code());
			$extra = isset($row['extra']) && is_array($row['extra']) ? $row['extra'] : array();
			if ($oaName !== '') {
				$extra['oa_name'] = $oaName;
			}
			$oaIdFromApi = '';
			if (!empty($info['data']['oa_id'])) {
				$oaIdFromApi = (string) $info['data']['oa_id'];
			}
			$creds = isset($row['credentials']) && is_array($row['credentials']) ? $row['credentials'] : array();
			if ($oaIdFromApi !== '' && empty($creds['oa_id'])) {
				$creds['oa_id'] = $oaIdFromApi;
			}
			$msg = $oaName !== ''
				? ('Kết nối Zalo OA thành công: ' . $oaName)
				: 'Kết nối Zalo OA thành công.';
			NkApiConnection::saveRow($this->code(), array(
				'credentials' => $creds,
				'extra' => $extra,
				'status' => 'ok',
				'last_sync' => date('Y-m-d H:i:s'),
				'last_error' => '',
			), 0);
			return array(
				'success' => true,
				'status' => 'ok',
				'message' => $msg,
				'oa' => isset($info['data']) ? $info['data'] : $info,
			);
		} catch (Exception $e) {
			NkApiConnection::saveRow($this->code(), array(
				'status' => 'error',
				'last_error' => $e->getMessage(),
			), 0);
			return array(
				'success' => false,
				'status' => 'error',
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Build OAuth authorize URL (PKCE). Persists verifier in PHP session.
	 * @param array $payload optional app_id/secret_key/oa_id to save first
	 * @param int $userId
	 * @return array {authorize_url, callback_url}
	 */
	public function beginOAuth(array $payload = array(), $userId = 0) {
		if (!empty($payload)) {
			$this->save($payload, $userId);
		}
		$row = NkApiConnection::getRow($this->code());
		$creds = isset($row['credentials']) && is_array($row['credentials']) ? $row['credentials'] : array();
		$appId = isset($creds['app_id']) ? trim((string) $creds['app_id']) : '';
		$secret = isset($creds['secret_key']) ? trim((string) $creds['secret_key']) : '';
		if ($appId === '' || $secret === '') {
			throw new Exception('Cần lưu App ID và Secret Key trước khi kết nối OAuth.');
		}

		$verifier = self::base64UrlEncode(random_bytes(32));
		$challenge = self::base64UrlEncode(hash('sha256', $verifier, true));
		$state = self::base64UrlEncode(random_bytes(16));

		if (session_status() === PHP_SESSION_NONE) {
			@session_start();
		}
		$_SESSION['nk_zalo_oa_oauth'] = array(
			'code_verifier' => $verifier,
			'state' => $state,
			'app_id' => $appId,
			'created_at' => time(),
		);

		$callback = self::callbackUrl();
		$query = http_build_query(array(
			'app_id' => $appId,
			'redirect_uri' => $callback,
			'code_challenge' => $challenge,
			'code_challenge_method' => 'S256',
			'state' => $state,
		), '', '&', PHP_QUERY_RFC3986);

		return array(
			'authorize_url' => self::AUTH_URL . '?' . $query,
			'callback_url' => $callback,
		);
	}

	/**
	 * Exchange authorization code from Zalo redirect.
	 * @param string $code
	 * @param string $state
	 * @param string $oaIdFromQuery
	 * @param int $userId
	 * @return array admin config
	 */
	public function completeOAuth($code, $state, $oaIdFromQuery = '', $userId = 0) {
		$code = trim((string) $code);
		$state = trim((string) $state);
		if ($code === '') {
			throw new Exception('Thiếu authorization code từ Zalo.');
		}
		if (session_status() === PHP_SESSION_NONE) {
			@session_start();
		}
		$sess = isset($_SESSION['nk_zalo_oa_oauth']) && is_array($_SESSION['nk_zalo_oa_oauth'])
			? $_SESSION['nk_zalo_oa_oauth']
			: array();
		if (empty($sess['code_verifier']) || empty($sess['state'])) {
			throw new Exception('Phiên OAuth hết hạn. Bấm “Kết nối Zalo OA” lại.');
		}
		if ($state !== '' && !hash_equals((string) $sess['state'], $state)) {
			throw new Exception('State OAuth không khớp. Thử kết nối lại.');
		}

		$row = NkApiConnection::getRow($this->code());
		$creds = isset($row['credentials']) && is_array($row['credentials']) ? $row['credentials'] : array();
		$appId = isset($creds['app_id']) ? trim((string) $creds['app_id']) : '';
		$secret = isset($creds['secret_key']) ? trim((string) $creds['secret_key']) : '';
		if ($appId === '' || $secret === '') {
			throw new Exception('Thiếu App ID / Secret Key đã lưu.');
		}

		$token = $this->requestToken(array(
			'code' => $code,
			'app_id' => $appId,
			'grant_type' => 'authorization_code',
			'code_verifier' => (string) $sess['code_verifier'],
		), $secret);

		$this->persistTokens($token, $oaIdFromQuery, $userId);
		unset($_SESSION['nk_zalo_oa_oauth']);

		// Best-effort OA profile fetch
		try {
			$this->test();
		} catch (Exception $e) {
			/* tokens saved; test may fail if OA not fully linked yet */
		}
		return $this->getConfigForAdmin();
	}

	/**
	 * Ensure a non-expired access_token (refresh when needed).
	 * @param int $userId
	 * @return string
	 */
	public function getValidAccessToken($userId = 0) {
		$row = NkApiConnection::getRow($this->code());
		$creds = isset($row['credentials']) && is_array($row['credentials']) ? $row['credentials'] : array();
		$appId = isset($creds['app_id']) ? trim((string) $creds['app_id']) : '';
		$secret = isset($creds['secret_key']) ? trim((string) $creds['secret_key']) : '';
		$refresh = isset($creds['refresh_token']) ? trim((string) $creds['refresh_token']) : '';
		$access = isset($creds['access_token']) ? trim((string) $creds['access_token']) : '';
		$expiresAt = isset($creds['expires_at']) ? strtotime((string) $creds['expires_at']) : 0;

		if ($appId === '' || $secret === '') {
			throw new Exception('Chưa cấu hình App ID / Secret Key.');
		}
		if ($refresh === '' && $access === '') {
			throw new Exception('Chưa có Refresh Token / Access Token. Kết nối OAuth hoặc dán Refresh Token rồi Lưu.');
		}

		$skew = 300; // refresh 5 phút trước khi hết hạn
		$stillValid = $access !== '' && $expiresAt > (time() + $skew);
		if ($stillValid) {
			return $access;
		}
		if ($refresh === '') {
			throw new Exception('Access token đã hết hạn và không có Refresh Token để gia hạn.');
		}

		$token = $this->requestToken(array(
			'refresh_token' => $refresh,
			'app_id' => $appId,
			'grant_type' => 'refresh_token',
		), $secret);
		$this->persistTokens($token, '', $userId);

		$row2 = NkApiConnection::getRow($this->code());
		$creds2 = isset($row2['credentials']) && is_array($row2['credentials']) ? $row2['credentials'] : array();
		$newAccess = isset($creds2['access_token']) ? trim((string) $creds2['access_token']) : '';
		if ($newAccess === '') {
			throw new Exception('Refresh token không trả về access_token.');
		}
		return $newAccess;
	}

	public static function callbackUrl() {
		global $site_URL;
		$base = rtrim((string) $site_URL, '/');
		return $base . '/index.php?module=Vtiger&parent=Settings&action=ZaloOaOAuth&mode=callback';
	}

	public static function webhookUrl() {
		global $site_URL;
		$base = rtrim((string) $site_URL, '/');
		return $base . '/index.php?module=Leads&action=ZaloOaWebhook';
	}

	protected function hasCredentials($row) {
		$creds = isset($row['credentials']) && is_array($row['credentials']) ? $row['credentials'] : array();
		return !empty($creds['app_id'])
			&& !empty($creds['secret_key'])
			&& (!empty($creds['refresh_token']) || !empty($creds['access_token']));
	}

	protected function persistTokens(array $token, $oaIdFromQuery = '', $userId = 0) {
		$row = NkApiConnection::getRow($this->code());
		$creds = isset($row['credentials']) && is_array($row['credentials']) ? $row['credentials'] : array();
		$extra = isset($row['extra']) && is_array($row['extra']) ? $row['extra'] : array();

		if (!empty($token['access_token'])) {
			$creds['access_token'] = (string) $token['access_token'];
		}
		// Zalo rotates refresh_token on every refresh — always overwrite when present.
		if (!empty($token['refresh_token'])) {
			$creds['refresh_token'] = (string) $token['refresh_token'];
		}
		$expiresIn = isset($token['expires_in']) ? (int) $token['expires_in'] : 90000;
		if ($expiresIn < 60) {
			$expiresIn = 90000;
		}
		$creds['expires_at'] = date('Y-m-d H:i:s', time() + $expiresIn);
		$oaIdFromQuery = trim((string) $oaIdFromQuery);
		if ($oaIdFromQuery !== '') {
			$creds['oa_id'] = $oaIdFromQuery;
		}

		NkApiConnection::saveRow($this->code(), array(
			'credentials' => $creds,
			'extra' => $extra,
			'base_url' => self::API_BASE,
			'status' => 'idle',
			'last_error' => '',
		), $userId);
	}

	protected function requestToken(array $body, $secretKey) {
		$resp = $this->httpPostForm(self::TOKEN_URL, $body, array(
			'secret_key: ' . $secretKey,
			'Content-Type: application/x-www-form-urlencoded',
		));
		if (!is_array($resp)) {
			throw new Exception('Phản hồi token Zalo không hợp lệ.');
		}
		if (!empty($resp['error']) || !empty($resp['error_name']) || empty($resp['access_token'])) {
			$msg = '';
			if (!empty($resp['error_description'])) {
				$msg = (string) $resp['error_description'];
			} elseif (!empty($resp['error_name'])) {
				$msg = (string) $resp['error_name'];
			} elseif (!empty($resp['message'])) {
				$msg = (string) $resp['message'];
			} else {
				$msg = 'Không lấy được access_token từ Zalo.';
			}
			throw new Exception($msg);
		}
		return $resp;
	}

	protected function fetchOaInfo($accessToken) {
		$resp = $this->httpGet(self::OA_INFO_URL, array(
			'access_token: ' . $accessToken,
		));
		if (!is_array($resp)) {
			throw new Exception('Phản hồi getoa không hợp lệ.');
		}
		$error = 0;
		if (isset($resp['error'])) {
			$error = (int) $resp['error'];
		}
		if ($error !== 0) {
			$msg = !empty($resp['message']) ? (string) $resp['message'] : ('Zalo API error ' . $error);
			throw new Exception($msg);
		}
		return $resp;
	}

	protected function httpPostForm($url, array $fields, array $headers) {
		$body = http_build_query($fields, '', '&');
		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			$raw = curl_exec($ch);
			$err = curl_error($ch);
			$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if ($raw === false) {
				throw new Exception('Không gọi được Zalo OAuth: ' . $err);
			}
			$decoded = json_decode($raw, true);
			if (!is_array($decoded)) {
				throw new Exception('Zalo OAuth HTTP ' . $code . ': phản hồi không phải JSON.');
			}
			return $decoded;
		}
		$ctx = stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'header' => implode("\r\n", $headers),
				'content' => $body,
				'timeout' => 30,
				'ignore_errors' => true,
			),
		));
		$raw = @file_get_contents($url, false, $ctx);
		if ($raw === false) {
			throw new Exception('Không gọi được Zalo OAuth (file_get_contents).');
		}
		$decoded = json_decode($raw, true);
		if (!is_array($decoded)) {
			throw new Exception('Zalo OAuth: phản hồi không phải JSON.');
		}
		return $decoded;
	}

	protected function httpGet($url, array $headers) {
		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HTTPGET, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			$raw = curl_exec($ch);
			$err = curl_error($ch);
			$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if ($raw === false) {
				throw new Exception('Không gọi được Zalo OpenAPI: ' . $err);
			}
			$decoded = json_decode($raw, true);
			if (!is_array($decoded)) {
				throw new Exception('Zalo OpenAPI HTTP ' . $code . ': phản hồi không phải JSON.');
			}
			return $decoded;
		}
		$ctx = stream_context_create(array(
			'http' => array(
				'method' => 'GET',
				'header' => implode("\r\n", $headers),
				'timeout' => 30,
				'ignore_errors' => true,
			),
		));
		$raw = @file_get_contents($url, false, $ctx);
		if ($raw === false) {
			throw new Exception('Không gọi được Zalo OpenAPI (file_get_contents).');
		}
		$decoded = json_decode($raw, true);
		if (!is_array($decoded)) {
			throw new Exception('Zalo OpenAPI: phản hồi không phải JSON.');
		}
		return $decoded;
	}

	protected static function base64UrlEncode($bin) {
		return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
	}
}
