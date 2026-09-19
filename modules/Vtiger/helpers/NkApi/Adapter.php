<?php
/*+***********************************************************************************
 * Base adapter for Settings → Tích hợp hệ thống.
 * Senior implements a concrete adapter; do not change the Settings UI.
 *************************************************************************************/

abstract class NkApi_Adapter
{

	abstract public function code();

	abstract public function label();

	abstract public function description();

	/**
	 * False until a senior implements the real API client.
	 */
	public function isImplemented()
	{
		return false;
	}

	/**
	 * Extra form fields beyond enabled / base_url / credentials.
	 * @return array
	 */
	public function extraFields()
	{
		return array();
	}

	/**
	 * Sanitized config for the admin UI (never include secrets).
	 * @return array
	 */
	public function getConfigForAdmin()
	{
		$row = NkApiConnection::getRow($this->code());
		$status = isset($row['status']) ? (string) $row['status'] : 'not_configured';
		if (empty($row['enabled'])) {
			$status = $status === 'ok' ? 'disabled' : $status;
		}
		if (!$this->isImplemented() && ($status === 'not_configured' || $status === '')) {
			$status = $this->hasCredentials($row) || !empty($row['base_url']) ? 'coming_soon' : 'not_configured';
		}
		return array(
			'code' => $this->code(),
			'label' => $this->label(),
			'description' => $this->description(),
			'icon' => $this->icon(),
			'implemented' => $this->isImplemented(),
			'enabled' => !empty($row['enabled']),
			'base_url' => isset($row['base_url']) ? (string) $row['base_url'] : '',
			'username' => $this->publicUsername($row),
			'credentials_configured' => $this->hasCredentials($row),
			'status' => $status,
			'status_label' => NkApiConnection::statusLabel($status),
			'last_sync' => isset($row['last_sync']) ? (string) $row['last_sync'] : '',
			'last_error' => isset($row['last_error']) ? (string) $row['last_error'] : '',
			'extra_fields' => $this->extraFields(),
			'extra' => isset($row['extra']) && is_array($row['extra']) ? $row['extra'] : array(),
			'hint' => $this->hint(),
		);
	}

	public function hint()
	{
		return '';
	}

	public function icon()
	{
		return $this->code();
	}

	protected function statusSummary($status)
	{
		$map = array(
			'ok' => 'Kết nối OK',
			'idle' => 'Đã lưu, chưa test',
			'coming_soon' => 'Chờ implement API',
			'not_configured' => 'Chưa đủ thông tin',
			'disabled' => 'Đang tắt',
			'error' => 'Có lỗi',
		);
		return isset($map[$status]) ? $map[$status] : $status;
	}

	/**
	 * @param array $payload
	 * @param int $userId
	 * @return array sanitized config
	 */
	public function save(array $payload, $userId = 0)
	{
		$row = NkApiConnection::getRow($this->code());
		$creds = isset($row['credentials']) && is_array($row['credentials']) ? $row['credentials'] : array();

		if (array_key_exists('api_key', $payload) && trim((string) $payload['api_key']) !== '') {
			$creds['api_key'] = trim((string) $payload['api_key']);
		}
		if (array_key_exists('password', $payload) && (string) $payload['password'] !== '') {
			$creds['password'] = (string) $payload['password'];
		}
		if (array_key_exists('username', $payload)) {
			$creds['username'] = trim((string) $payload['username']);
		}

		$enabled = !empty($payload['enabled']);
		$baseUrl = isset($payload['base_url']) ? trim((string) $payload['base_url']) : (isset($row['base_url']) ? $row['base_url'] : '');

		$status = $row['status'];
		if ($this->isImplemented()) {
			if ($status === '' || $status === null) {
				$status = $this->hasCredentials(array('credentials' => $creds)) || $baseUrl !== '' ? 'idle' : 'not_configured';
			}
		} else {
			$status = ($this->hasCredentials(array('credentials' => $creds)) || $baseUrl !== '') ? 'coming_soon' : 'not_configured';
		}

		NkApiConnection::saveRow($this->code(), array(
			'enabled' => $enabled ? 1 : 0,
			'base_url' => $baseUrl,
			'credentials' => $creds,
			'status' => $status,
		), $userId);

		$statusType = 'success';
		if ($status === 'not_configured' || $status === 'coming_soon') {
			$statusType = 'warning';
		}

		$this->logActivity(
			'config',
			$statusType,
			$this->label() . ' — cập nhật cấu hình',
			$enabled
				? 'Đã bật kết nối • ' . $this->statusSummary($status)
				: 'Đã tắt kết nối',
			array('user_id' => (int) $userId)
		);

		return $this->getConfigForAdmin();
	}

	/**
	 * @return array {success, status, message}
	 */
	public function test()
	{
		$start = microtime(true);

		$row = NkApiConnection::getRow($this->code());
		$configured = $this->hasCredentials($row) || !empty($row['base_url']);

		if (!$configured) {
			NkApiConnection::saveRow($this->code(), array(
				'status' => 'not_configured',
				'last_error' => 'Chưa cấu hình URL / thông tin đăng nhập.',
			), 0);

			$this->logActivity(
				'test',
				'warning',
				$this->label() . ' — kiểm tra kết nối',
				'Chưa cấu hình URL hoặc thông tin đăng nhập',
				array('error_message' => 'Chưa cấu hình')
			);

			return array(
				'success' => false,
				'status' => 'not_configured',
				'message' => 'Chưa cấu hình. Nhập URL và thông tin đăng nhập, rồi Lưu.',
			);
		}

		if (!$this->isImplemented()) {
			NkApiConnection::saveRow($this->code(), array(
				'status' => 'coming_soon',
				'last_error' => '',
			), 0);

			$this->logActivity(
				'test',
				'warning',
				$this->label() . ' — chưa implement',
				'Adapter ' . get_class($this) . ' chưa có API client',
				array('error_message' => 'not_implemented')
			);

			return array(
				'success' => false,
				'status' => 'coming_soon',
				'message' => 'Adapter chưa được implement. Cấu hình đã lưu — senior cắm API vào class ' . get_class($this) . ' mà không sửa UI.',
			);
		}

		try {
			$result = $this->doTest();
		} catch (Exception $e) {
			$result = array(
				'success' => false,
				'status' => 'error',
				'message' => $e->getMessage(),
			);
		}

		$durationMs = (int) ((microtime(true) - $start) * 1000);

		$this->logActivity(
			'test',
			!empty($result['success']) ? 'success' : 'error',
			$this->label() . ' — ' . (!empty($result['success']) ? 'kết nối thành công' : 'kết nối thất bại'),
			isset($result['message']) ? (string) $result['message'] : '',
			array(
				'duration_ms' => $durationMs,
				'error_message' => empty($result['success']) ? (isset($result['message']) ? $result['message'] : '') : '',
			)
		);

		return $result;
	}

	/**
	 * Adapter con override hàm này để test API thật.
	 * KHÔNG override test() — log đã được base class lo.
	 *
	 * @return array {success, status, message}
	 */
	protected function doTest()
	{
		return array(
			'success' => false,
			'status' => 'error',
			'message' => 'Chưa có bước test cho adapter này.',
		);
	}

	/**
	 * Helper: ghi log hoạt động cho adapter con dùng khi sync.
	 *
	 * @param string $status  success | warning | error
	 * @param string $title
	 * @param string $detail
	 * @param array  $extra   records_count, duration_ms, error_message, user_id
	 */
	protected function logSync($status, $title, $detail = '', array $extra = array())
	{
		$this->logActivity('sync', $status, $title, $detail, $extra);
	}

	/**
	 * Wrapper an toàn — không throw nếu log lỗi.
	 *
	 * @param string $eventType
	 * @param string $status
	 * @param string $title
	 * @param string $detail
	 * @param array  $extra
	 */
	protected function logActivity($eventType, $status, $title, $detail = '', array $extra = array())
	{
		try {
			NkApiConnection::logActivity(
				$this->code(),
				$eventType,
				$status,
				mb_substr((string) $title, 0, 255),
				$detail,
				$extra
			);
		} catch (Exception $e) {
			// Không để lỗi log làm hỏng luồng chính
		}
	}

	protected function hasCredentials($row)
	{
		$creds = isset($row['credentials']) && is_array($row['credentials']) ? $row['credentials'] : array();
		foreach (array('api_key', 'password', 'token', 'secret') as $k) {
			if (!empty($creds[$k])) {
				return true;
			}
		}
		return false;
	}

	protected function publicUsername($row)
	{
		$creds = isset($row['credentials']) && is_array($row['credentials']) ? $row['credentials'] : array();
		return isset($creds['username']) ? (string) $creds['username'] : '';
	}

	public function isEnabled()
	{
		$row = NkApiConnection::getRow($this->code());
		return !empty($row['enabled']);
	}
}
