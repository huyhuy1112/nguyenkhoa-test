<?php
/*+***********************************************************************************
 * Edubit LMS — GD 1.2 Online (tạo user / kích hoạt khóa / tiến độ).
 * Token lưu trong nk_api_connection — không hardcode, không mặc định course_id.
 *************************************************************************************/

require_once 'modules/Vtiger/helpers/NkApi/Adapter.php';

class NkApi_Edubit_Adapter extends NkApi_Adapter {

	const DEFAULT_BASE = 'https://nguyenkhoa.edu.vn';

	public function code() {
		return 'edubit';
	}

	public function label() {
		return 'Edubit';
	}

	public function description() {
		return 'Edubit LMS — cấp tài khoản học viên, kích hoạt khóa và đồng bộ tiến độ (GD 1.2).';
	}

	public function isImplemented() {
		return true;
	}

	public function hint() {
		return 'Base URL: thường https://nguyenkhoa.edu.vn hoặc https://edubit.vn. Token lấy từ Website → Cài đặt → API KEY. Danh sách khóa chỉ để chọn tay — không có course_id mặc định.';
	}

	public function icon() {
		return 'edubit';
	}

	public function extraFields() {
		return array('courses_json');
	}

	/** Catalog gợi ý (admin có thể sửa) — không auto-select. */
	public static function suggestedCourses() {
		return array(
			array('id' => '29403', 'label' => 'KHÓA HỌC KHAI TRƯƠNG QUÁN BÀI BẢN'),
			array('id' => '29218', 'label' => 'KHÓA HỌC PHA CHẾ TỔNG HỢP'),
			array('id' => '28108', 'label' => 'KHÓA HỌC PHA CHẾ TỔNG HỢP CƠ BẢN'),
			array('id' => '27312', 'label' => 'KHÓA HỌC PHA CHẾ KINH DOANH (Miễn Phí)'),
		);
	}

	public function getConfigForAdmin() {
		$row = NkApiConnection::getRow($this->code());
		$creds = isset($row['credentials']) && is_array($row['credentials']) ? $row['credentials'] : array();
		$extra = isset($row['extra']) && is_array($row['extra']) ? $row['extra'] : array();
		$courses = $this->normalizeCourses(isset($extra['courses']) ? $extra['courses'] : null);
		$status = isset($row['status']) ? (string) $row['status'] : 'not_configured';
		if (empty($row['enabled']) && $status === 'ok') {
			$status = 'disabled';
		}
		$tokenOk = !empty($creds['api_key']) || !empty($creds['token']);
		if ($status === '' || $status === null) {
			$status = $tokenOk ? 'idle' : 'not_configured';
		}
		$base = isset($row['base_url']) ? trim((string) $row['base_url']) : '';
		if ($base === '') {
			$base = self::DEFAULT_BASE;
		}
		return array(
			'code' => $this->code(),
			'label' => $this->label(),
			'description' => $this->description(),
			'icon' => $this->icon(),
			'implemented' => true,
			'enabled' => !empty($row['enabled']),
			'base_url' => $base,
			'username' => '',
			'credentials_configured' => $tokenOk,
			'status' => $status,
			'status_label' => NkApiConnection::statusLabel($status),
			'last_sync' => isset($row['last_sync']) ? (string) $row['last_sync'] : '',
			'last_error' => isset($row['last_error']) ? (string) $row['last_error'] : '',
			'extra_fields' => $this->extraFields(),
			'extra' => array(
				'courses' => $courses,
				'courses_json' => json_encode($courses, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
			),
			'courses' => $courses,
			'hint' => $this->hint(),
		);
	}

	public function save(array $payload, $userId = 0) {
		$row = NkApiConnection::getRow($this->code());
		$creds = isset($row['credentials']) && is_array($row['credentials']) ? $row['credentials'] : array();
		$extra = isset($row['extra']) && is_array($row['extra']) ? $row['extra'] : array();

		if (array_key_exists('api_key', $payload) && trim((string) $payload['api_key']) !== '') {
			$creds['api_key'] = trim((string) $payload['api_key']);
			$creds['token'] = $creds['api_key'];
		} elseif (array_key_exists('token', $payload) && trim((string) $payload['token']) !== '') {
			$creds['api_key'] = trim((string) $payload['token']);
			$creds['token'] = $creds['api_key'];
		}

		$enabled = array_key_exists('enabled', $payload)
			? (!empty($payload['enabled']) ? 1 : 0)
			: (!empty($row['enabled']) ? 1 : 0);
		$baseUrl = array_key_exists('base_url', $payload)
			? rtrim(trim((string) $payload['base_url']), '/')
			: (isset($row['base_url']) ? (string) $row['base_url'] : '');
		if ($baseUrl === '') {
			$baseUrl = self::DEFAULT_BASE;
		}

		if (array_key_exists('courses_json', $payload)) {
			$decoded = json_decode((string) $payload['courses_json'], true);
			$extra['courses'] = $this->normalizeCourses(is_array($decoded) ? $decoded : null);
		} elseif (array_key_exists('courses', $payload)) {
			$extra['courses'] = $this->normalizeCourses($payload['courses']);
		} elseif (empty($extra['courses'])) {
			$extra['courses'] = self::suggestedCourses();
		}

		$status = $row['status'];
		$tokenOk = !empty($creds['api_key']) || !empty($creds['token']);
		if ($status === '' || $status === null || $status === 'not_configured') {
			$status = $tokenOk ? 'idle' : 'not_configured';
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
			'success',
			'Edubit — cập nhật cấu hình',
			$enabled ? 'Đã bật · ' . count($extra['courses']) . ' khóa trong catalog (không mặc định)' : 'Đã tắt',
			array('user_id' => (int) $userId)
		);

		return $this->getConfigForAdmin();
	}

	public function test() {
		try {
			$res = $this->apiGet('getListRegStudent', array('type' => '0'));
			$ok = isset($res['status']) && strtoupper((string) $res['status']) === 'OK';
			$msg = $ok
				? self::formatApiMessage(isset($res['message']) ? $res['message'] : 'Kết nối Edubit OK.')
				: self::formatApiError($res, 'Edubit test');
			NkApiConnection::saveRow($this->code(), array(
				'status' => $ok ? 'ok' : 'error',
				'last_sync' => $ok ? date('Y-m-d H:i:s') : null,
				'last_error' => $ok ? '' : $msg,
			), 0);
			$this->logActivity('test', $ok ? 'success' : 'error', 'Edubit — kiểm tra kết nối', $msg);
			return array(
				'success' => $ok,
				'status' => $ok ? 'ok' : 'error',
				'message' => $msg,
				'raw' => $res,
			);
		} catch (Exception $e) {
			NkApiConnection::saveRow($this->code(), array(
				'status' => 'error',
				'last_error' => $e->getMessage(),
			), 0);
			$this->logActivity('test', 'error', 'Edubit — kiểm tra kết nối', $e->getMessage());
			return array(
				'success' => false,
				'status' => 'error',
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * @param array $params name, phone, email, password?
	 * @return array
	 */
	public function createUser(array $params) {
		$query = array(
			'name' => isset($params['name']) ? trim((string) $params['name']) : '',
			'phone' => self::normalizePhone(isset($params['phone']) ? $params['phone'] : ''),
			'email' => isset($params['email']) ? trim((string) $params['email']) : '',
		);
		if (!empty($params['password'])) {
			$query['password'] = (string) $params['password'];
		}
		if ($query['name'] === '' || $query['phone'] === '' || $query['email'] === '') {
			throw new Exception('Edubit create user cần name, phone, email.');
		}
		$res = $this->apiGet('user/create', $query);
		$status = isset($res['status']) ? strtoupper((string) $res['status']) : '';
		$ok = ($status === 'OK' || $status === 'USER_EXISTED');
		if (!$ok) {
			throw new Exception(self::formatApiError($res, 'Edubit create'));
		}
		return array(
			'success' => true,
			'status' => $status,
			'user_id' => isset($res['user_id']) ? self::formatApiMessage($res['user_id']) : '',
			'password' => isset($res['password']) ? self::formatApiMessage($res['password']) : '',
			'message' => self::formatApiMessage(isset($res['message']) ? $res['message'] : ''),
			'raw' => $res,
		);
	}

	/**
	 * @param array $params email (bắt buộc), course_id (bắt buộc), name?, phone?, password?
	 */
	public function activateCourse(array $params) {
		$courseId = isset($params['course_id']) ? trim((string) $params['course_id']) : '';
		$email = isset($params['email']) ? trim((string) $params['email']) : '';
		if ($courseId === '') {
			throw new Exception('Phải chọn course_id — không có khóa mặc định.');
		}
		if ($email === '') {
			throw new Exception('Edubit activate cần email học viên.');
		}
		$query = array(
			'email' => $email,
			'course_id' => $courseId,
		);
		if (!empty($params['name'])) {
			$query['name'] = trim((string) $params['name']);
		}
		if (!empty($params['phone'])) {
			$query['phone'] = self::normalizePhone($params['phone']);
		}
		if (!empty($params['password'])) {
			$query['password'] = (string) $params['password'];
		}
		$res = $this->apiGet('course/activate', $query);
		$status = isset($res['status']) ? strtoupper((string) $res['status']) : '';
		$ok = ($status === 'OK' || $status === 'COURSE_ACTIVATED');
		if (!$ok) {
			throw new Exception(self::formatApiError($res, 'Edubit activate'));
		}
		return array(
			'success' => true,
			'status' => $status,
			'message' => self::formatApiMessage(isset($res['message']) ? $res['message'] : ''),
			'data' => isset($res['data']) ? $res['data'] : null,
			'raw' => $res,
		);
	}

	/**
	 * @param string $email
	 * @param string $courseId
	 * @param int $version 1|2
	 */
	public function getProcessLearnStudent($email, $courseId, $version = 2) {
		$email = trim((string) $email);
		$courseId = trim((string) $courseId);
		if ($email === '' || $courseId === '') {
			throw new Exception('getProcessLearnStudent cần email và course_id.');
		}
		$res = $this->apiGet('getProcessLearnStudent', array(
			'email' => $email,
			'course_id' => $courseId,
			'version' => (string) ((int) $version > 0 ? (int) $version : 2),
		));
		$status = isset($res['status']) ? strtoupper((string) $res['status']) : '';
		if ($status !== 'OK') {
			throw new Exception(self::formatApiError($res, 'Edubit progress'));
		}
		$pct = self::extractProgressPercent(isset($res['data']) ? $res['data'] : null);
		return array(
			'success' => true,
			'status' => $status,
			'message' => self::formatApiMessage(isset($res['message']) ? $res['message'] : ''),
			'progress_pct' => $pct,
			'data' => isset($res['data']) ? $res['data'] : null,
			'raw' => $res,
		);
	}

	public function listCoursesForUi() {
		$cfg = $this->getConfigForAdmin();
		return isset($cfg['courses']) && is_array($cfg['courses']) ? $cfg['courses'] : self::suggestedCourses();
	}

	protected function normalizeCourses($raw) {
		$list = array();
		if (!is_array($raw)) {
			return self::suggestedCourses();
		}
		foreach ($raw as $item) {
			if (!is_array($item)) {
				continue;
			}
			$id = isset($item['id']) ? trim((string) $item['id']) : (isset($item['course_id']) ? trim((string) $item['course_id']) : '');
			if ($id === '') {
				continue;
			}
			$label = isset($item['label']) ? trim((string) $item['label']) : (isset($item['name']) ? trim((string) $item['name']) : $id);
			$list[] = array('id' => $id, 'label' => $label !== '' ? $label : $id);
		}
		return !empty($list) ? $list : self::suggestedCourses();
	}

	protected function token() {
		$row = NkApiConnection::getRow($this->code());
		$creds = isset($row['credentials']) && is_array($row['credentials']) ? $row['credentials'] : array();
		if (!empty($creds['api_key'])) {
			return trim((string) $creds['api_key']);
		}
		if (!empty($creds['token'])) {
			return trim((string) $creds['token']);
		}
		return '';
	}

	protected function baseUrl() {
		$row = NkApiConnection::getRow($this->code());
		$base = isset($row['base_url']) ? rtrim(trim((string) $row['base_url']), '/') : '';
		return $base !== '' ? $base : self::DEFAULT_BASE;
	}

	/**
	 * GET {base}/api/{path}?…&token=
	 * @param string $path e.g. user/create | course/activate | getProcessLearnStudent
	 */
	protected function apiGet($path, array $query) {
		if (!$this->isEnabled()) {
			throw new Exception('Kết nối Edubit đang tắt. Bật trong Cài đặt → Tích hợp hệ thống / Integration Hub.');
		}
		$token = $this->token();
		if ($token === '') {
			throw new Exception('Chưa cấu hình API token Edubit.');
		}
		$query['token'] = $token;
		$path = ltrim((string) $path, '/');
		$url = $this->baseUrl() . '/api/' . $path . '?' . http_build_query($query);
		$raw = $this->httpGet($url);
		$decoded = json_decode($raw, true);
		if (!is_array($decoded)) {
			throw new Exception('Edubit trả về không phải JSON: ' . mb_substr($raw, 0, 180));
		}
		return $decoded;
	}

	protected function httpGet($url) {
		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HTTPGET, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 45);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
			$raw = curl_exec($ch);
			$err = curl_error($ch);
			$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if ($raw === false) {
				throw new Exception('Không gọi được Edubit API: ' . $err);
			}
			if ($code >= 400 && $code !== 200) {
				// Một số lỗi Edubit vẫn HTTP 200 + status JSON; giữ body nếu có.
			}
			return (string) $raw;
		}
		$ctx = stream_context_create(array(
			'http' => array(
				'method' => 'GET',
				'timeout' => 45,
				'header' => "Accept: application/json\r\n",
			),
		));
		$raw = @file_get_contents($url, false, $ctx);
		if ($raw === false) {
			throw new Exception('Không gọi được Edubit API (file_get_contents).');
		}
		return (string) $raw;
	}

	/**
	 * Best-effort % từ payload Edubit (cấu trúc data thay đổi theo version).
	 * @param mixed $data
	 * @return int|null 0–100
	 */
	public static function extractProgressPercent($data) {
		if ($data === null) {
			return null;
		}
		if (is_numeric($data)) {
			return max(0, min(100, (int) round((float) $data)));
		}
		if (!is_array($data)) {
			return null;
		}
		foreach (array('progress', 'percent', 'percentage', 'process', 'completion', 'complete_percent', 'progress_percent') as $k) {
			if (isset($data[$k]) && is_numeric($data[$k])) {
				$v = (float) $data[$k];
				if ($v <= 1 && $v >= 0) {
					$v = $v * 100;
				}
				return max(0, min(100, (int) round($v)));
			}
		}
		// version 2: list bài đã hoàn thành / tổng
		if (isset($data['lessons']) && is_array($data['lessons'])) {
			$total = count($data['lessons']);
			$done = 0;
			foreach ($data['lessons'] as $lesson) {
				if (!is_array($lesson)) {
					continue;
				}
				if (!empty($lesson['completed']) || !empty($lesson['is_completed']) || (isset($lesson['status']) && $lesson['status'] === 'completed')) {
					$done++;
				}
			}
			if ($total > 0) {
				return (int) round(($done / $total) * 100);
			}
		}
		if (isset($data['completed_lessons']) && isset($data['total_lessons']) && (int) $data['total_lessons'] > 0) {
			return (int) round(((int) $data['completed_lessons'] / (int) $data['total_lessons']) * 100);
		}
		return null;
	}

	/**
	 * Edubit đôi khi trả message là array → (string) thành "Array".
	 * @param mixed $message
	 * @return string
	 */
	public static function formatApiMessage($message) {
		if ($message === null) {
			return '';
		}
		$raw = '';
		if (is_string($message) || is_numeric($message) || is_bool($message)) {
			$raw = trim((string) $message);
		} elseif (is_array($message)) {
			$flat = array();
			array_walk_recursive($message, function ($v) use (&$flat) {
				if (is_scalar($v) || $v === null) {
					$s = trim((string) $v);
					if ($s !== '') {
						$flat[] = $s;
					}
				}
			});
			if (!empty($flat)) {
				$raw = implode('; ', $flat);
			} else {
				$json = json_encode($message, JSON_UNESCAPED_UNICODE);
				$raw = $json !== false ? $json : 'Array';
			}
		} elseif (is_object($message)) {
			if (method_exists($message, '__toString')) {
				$raw = trim((string) $message);
			} else {
				$json = json_encode($message, JSON_UNESCAPED_UNICODE);
				$raw = $json !== false ? $json : 'Object';
			}
		}
		return self::decodeHtmlEntities($raw);
	}

	/**
	 * Edubit hay trả &mdash; / &ecirc;… — decode để UI không hiện “lỗi font”.
	 * @param string $text
	 * @return string
	 */
	protected static function decodeHtmlEntities($text) {
		$text = trim((string) $text);
		if ($text === '' || strpos($text, '&') === false) {
			return $text;
		}
		if (function_exists('decode_html')) {
			$decoded = decode_html($text);
			if ($decoded !== $text && strpos($decoded, '&') !== false) {
				$decoded = decode_html($decoded);
			}
			return trim((string) $decoded);
		}
		$decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		if ($decoded !== $text && strpos($decoded, '&') !== false) {
			$decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}
		return trim((string) $decoded);
	}

	/**
	 * @param array $res
	 * @param string $fallbackPrefix
	 * @return string
	 */
	public static function formatApiError(array $res, $fallbackPrefix = 'Edubit') {
		$status = isset($res['status']) ? strtoupper(trim((string) $res['status'])) : '';
		$msg = self::formatApiMessage(isset($res['message']) ? $res['message'] : '');
		if ($msg === '' && isset($res['error'])) {
			$msg = self::formatApiMessage($res['error']);
		}
		if ($msg === '' && isset($res['errors'])) {
			$msg = self::formatApiMessage($res['errors']);
		}
		$bits = array();
		if ($status !== '') {
			$bits[] = $status;
		}
		if ($msg !== '' && strcasecmp($msg, 'Array') !== 0) {
			$bits[] = $msg;
		}
		if (empty($bits)) {
			$bits[] = $fallbackPrefix . ' thất bại (không có mô tả).';
		}
		return implode(' — ', $bits);
	}

	public static function normalizePhone($phone) {
		$digits = preg_replace('/\D+/', '', (string) $phone);
		return $digits !== null ? $digits : '';
	}
}
