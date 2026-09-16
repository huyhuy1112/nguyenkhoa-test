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
			// Tổng bài là metadata riêng từng khóa, không dùng chung cho các course_id khác.
			array(
				'id' => '28108',
				'label' => 'KHÓA HỌC PHA CHẾ TỔNG HỢP CƠ BẢN',
				'total_lessons' => 32,
			),
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
	 * @param int $version 1|2 — mặc định thử 2 rồi fallback 1 nếu chưa parse được %
	 */
	public function getProcessLearnStudent($email, $courseId, $version = 2) {
		$email = trim((string) $email);
		$courseId = trim((string) $courseId);
		if ($email === '' || $courseId === '') {
			throw new Exception('getProcessLearnStudent cần email và course_id.');
		}
		$prefer = (int) $version > 0 ? (int) $version : 2;
		$order = ($prefer === 1) ? array(1, 2) : array(2, 1);
		$lastRes = null;
		$lastPct = null;
		$usedVersion = $prefer;
		foreach ($order as $ver) {
			$res = $this->apiGet('getProcessLearnStudent', array(
				'email' => $email,
				'course_id' => $courseId,
				'version' => (string) $ver,
			));
			$lastRes = $res;
			$status = isset($res['status']) ? strtoupper((string) $res['status']) : '';
			if ($status !== 'OK') {
				throw new Exception(self::formatApiError($res, 'Edubit progress'));
			}
			$totalHint = $this->courseTotalLessonsHint($courseId);
			$pct = self::extractProgressPercent(isset($res['data']) ? $res['data'] : null, $totalHint);
			if ($pct === null) {
				$pct = self::extractProgressPercent($res, $totalHint);
			}
			$lastPct = $pct;
			$usedVersion = $ver;
			if ($pct !== null) {
				break;
			}
		}
		return array(
			'success' => true,
			'status' => isset($lastRes['status']) ? strtoupper((string) $lastRes['status']) : 'OK',
			'message' => self::formatApiMessage(isset($lastRes['message']) ? $lastRes['message'] : ''),
			'progress_pct' => $lastPct,
			'version_used' => $usedVersion,
			'data' => isset($lastRes['data']) ? $lastRes['data'] : null,
			'raw' => $lastRes,
		);
	}

	/**
	 * total_lessons gợi ý từ catalog cấu hình (để tính % khi API chỉ trả bài đã xong).
	 * @param string $courseId
	 * @return int|null
	 */
	protected function courseTotalLessonsHint($courseId) {
		$courseId = trim((string) $courseId);
		if ($courseId === '') {
			return null;
		}
		// Cấu hình admin được ưu tiên; catalog mặc định là fallback để cấu hình cũ
		// (đã lưu trước khi có total_lessons) vẫn tính được đúng theo course_id.
		foreach (array($this->listCoursesForUi(), self::suggestedCourses()) as $courses) {
			foreach ($courses as $c) {
				if (!is_array($c) || !isset($c['id']) || (string) $c['id'] !== $courseId) {
					continue;
				}
				foreach (array('total_lessons', 'lesson_total', 'total') as $k) {
					if (isset($c[$k]) && is_numeric($c[$k]) && (int) $c[$k] > 0) {
						return (int) $c[$k];
					}
				}
			}
		}
		return null;
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
			$row = array('id' => $id, 'label' => $label !== '' ? $label : $id);
			foreach (array('total_lessons', 'lesson_total', 'total') as $tk) {
				if (isset($item[$tk]) && is_numeric($item[$tk]) && (int) $item[$tk] > 0) {
					$row['total_lessons'] = (int) $item[$tk];
					break;
				}
			}
			$list[] = $row;
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
	 * Docs: v1 = bài cuối; v2 = danh sách bài đã hoàn thành (thường không có flag completed).
	 *
	 * @param mixed $data
	 * @param int|null $totalLessonsHint tổng bài khóa (khi API chỉ trả bài đã xong)
	 * @return int|null 0–100
	 */
	public static function extractProgressPercent($data, $totalLessonsHint = null) {
		$hint = ($totalLessonsHint !== null && (int) $totalLessonsHint > 0) ? (int) $totalLessonsHint : null;
		if (is_string($data)) {
			$trim = trim($data);
			if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
				$decoded = json_decode($trim, true);
				if (is_array($decoded)) {
					$data = $decoded;
				}
			}
		}
		$direct = self::coercePercentValue($data);
		if ($direct !== null) {
			return $direct;
		}
		if (!is_array($data)) {
			return null;
		}

		// Ưu tiên field % tường minh (kể cả nested nông).
		$pctKeys = array(
			'progress_percent', 'progressPercent', 'complete_percent', 'completePercent',
			'percent', 'percentage', 'progress', 'process', 'completion', 'tiendo', 'tien_do',
			'phan_tram', 'phantram', 'process_percent', 'processPercent', 'learning_progress',
			'percent_complete', 'pct', 'ratio',
		);
		foreach ($pctKeys as $k) {
			if (!array_key_exists($k, $data)) {
				continue;
			}
			$v = self::coercePercentValue($data[$k]);
			if ($v !== null) {
				return $v;
			}
			// "12/40" hoặc "12 / 40 bài"
			$ratio = self::parseDoneTotalRatio($data[$k]);
			if ($ratio !== null) {
				return $ratio;
			}
		}

		// Cặp completed/total
		$doneKeys = array(
			'completed_lessons', 'complete_lessons', 'completedLessons', 'lesson_completed',
			'count_completed', 'so_bai_hoan_thanh', 'completed', 'finish_lesson', 'finish_lessons',
			'complete_lesson', 'learned_lessons',
		);
		$totalKeys = array(
			'total_lessons', 'totalLessons', 'lesson_total', 'count_lesson', 'count_lessons',
			'total_lesson', 'so_bai', 'tong_bai', 'total', 'lessons_total',
		);
		$done = null;
		$total = null;
		foreach ($doneKeys as $k) {
			if (isset($data[$k]) && is_numeric($data[$k])) {
				$done = (int) $data[$k];
				break;
			}
		}
		foreach ($totalKeys as $k) {
			if (isset($data[$k]) && is_numeric($data[$k]) && (int) $data[$k] > 0) {
				$total = (int) $data[$k];
				break;
			}
		}
		if ($done !== null && $total !== null && $total > 0) {
			return max(0, min(100, (int) round(($done / $total) * 100)));
		}
		if ($done !== null && $hint !== null) {
			return max(0, min(100, (int) round(($done / $hint) * 100)));
		}

		// List bài: key lessons / list / items / data; hoặc chính data là list (v2).
		$lists = array();
		foreach (array('lessons', 'lesson', 'list', 'items', 'processes', 'process_list', 'process', 'data', 'result') as $lk) {
			if (isset($data[$lk]) && is_array($data[$lk]) && self::isListArray($data[$lk])) {
				$lists[] = $data[$lk];
			}
		}
		if (self::isListArray($data)) {
			$lists[] = $data;
		}
		foreach ($lists as $list) {
			$fromList = self::percentFromLessonList($list, $hint, $total);
			if ($fromList !== null) {
				return $fromList;
			}
		}

		// Deep scan 1 tầng object con (tránh đệ quy vô hạn).
		foreach ($data as $child) {
			if (!is_array($child) || self::isListArray($child)) {
				continue;
			}
			$nested = self::extractProgressPercent($child, $hint);
			if ($nested !== null) {
				return $nested;
			}
		}
		return null;
	}

	/**
	 * @param mixed $value
	 * @return int|null
	 */
	protected static function coercePercentValue($value) {
		if ($value === null || $value === '' || is_bool($value) || is_array($value)) {
			return null;
		}
		if (is_numeric($value)) {
			$v = (float) $value;
			// 0–1 thường là tỉ lệ; >1 và ≤100 là %
			if ($v >= 0 && $v <= 1) {
				$v = $v * 100;
			}
			if ($v < 0 || $v > 100) {
				return null;
			}
			return (int) round($v);
		}
		if (!is_string($value)) {
			return null;
		}
		$s = trim($value);
		if ($s === '') {
			return null;
		}
		if (preg_match('/^\s*(\d+(?:[.,]\d+)?)\s*%\s*$/', $s, $m)) {
			return max(0, min(100, (int) round((float) str_replace(',', '.', $m[1]))));
		}
		if (preg_match('/^\s*(\d+(?:[.,]\d+)?)\s*$/', $s, $m)) {
			return self::coercePercentValue((float) str_replace(',', '.', $m[1]));
		}
		return null;
	}

	/**
	 * @param mixed $value
	 * @return int|null
	 */
	protected static function parseDoneTotalRatio($value) {
		if (!is_string($value) && !is_numeric($value)) {
			return null;
		}
		$s = trim((string) $value);
		if (!preg_match('/(\d+)\s*\/\s*(\d+)/', $s, $m)) {
			return null;
		}
		$done = (int) $m[1];
		$total = (int) $m[2];
		if ($total <= 0) {
			return null;
		}
		return max(0, min(100, (int) round(($done / $total) * 100)));
	}

	/**
	 * @param array $arr
	 * @return bool
	 */
	protected static function isListArray(array $arr) {
		if ($arr === array()) {
			return true;
		}
		if (function_exists('array_is_list')) {
			return array_is_list($arr);
		}
		$i = 0;
		foreach ($arr as $k => $_) {
			if ($k !== $i) {
				return false;
			}
			$i++;
		}
		return true;
	}

	/**
	 * @param array $list
	 * @param int|null $hint
	 * @param int|null $totalFromParent
	 * @return int|null
	 */
	protected static function percentFromLessonList(array $list, $hint = null, $totalFromParent = null) {
		if (empty($list)) {
			return 0;
		}
		$hasCompletionFlag = false;
		$done = 0;
		$pctSum = 0;
		$pctCount = 0;
		$maxOrder = 0;
		$orderTotal = null;
		foreach ($list as $lesson) {
			if (!is_array($lesson)) {
				// list id thuần → coi như bài đã hoàn thành (đúng docs v2)
				$done++;
				continue;
			}
			$lp = null;
			foreach (array('progress_percent', 'progressPercent', 'percent', 'percentage', 'progress', 'process') as $k) {
				if (array_key_exists($k, $lesson)) {
					$lp = self::coercePercentValue($lesson[$k]);
					if ($lp !== null) {
						break;
					}
				}
			}
			if ($lp !== null) {
				$pctSum += $lp;
				$pctCount++;
			}
			foreach (array('total_lessons', 'totalLessons', 'lesson_total', 'total', 'tong_bai') as $tk) {
				if (isset($lesson[$tk]) && is_numeric($lesson[$tk]) && (int) $lesson[$tk] > 0) {
					$orderTotal = (int) $lesson[$tk];
					break;
				}
			}
			foreach (array('order', 'stt', 'index', 'position', 'sort', 'lesson_index', 'number', 'no') as $ok) {
				if (isset($lesson[$ok]) && is_numeric($lesson[$ok])) {
					$maxOrder = max($maxOrder, (int) $lesson[$ok]);
				}
			}
			$completed = null;
			if (array_key_exists('completed', $lesson) || array_key_exists('is_completed', $lesson)
				|| array_key_exists('isCompleted', $lesson) || array_key_exists('finish', $lesson)
				|| array_key_exists('status', $lesson) || array_key_exists('state', $lesson)) {
				$hasCompletionFlag = true;
				$st = isset($lesson['status']) ? strtolower(trim((string) $lesson['status'])) : '';
				$state = isset($lesson['state']) ? strtolower(trim((string) $lesson['state'])) : '';
				$completed = !empty($lesson['completed']) || !empty($lesson['is_completed']) || !empty($lesson['isCompleted'])
					|| !empty($lesson['finish'])
					|| in_array($st, array('completed', 'complete', 'done', 'finished', '1', 'true'), true)
					|| in_array($state, array('completed', 'complete', 'done', 'finished'), true)
					|| ($lp !== null && $lp >= 100);
			}
			if ($completed === true) {
				$done++;
			} elseif ($completed === null) {
				// Không có flag: theo docs v2, phần tử trong list = đã hoàn thành
				$done++;
			}
		}

		$total = $totalFromParent !== null && $totalFromParent > 0 ? (int) $totalFromParent : null;
		if ($total === null && $orderTotal !== null && $orderTotal > 0) {
			$total = $orderTotal;
		}
		if ($total === null && $hint !== null && $hint > 0) {
			$total = (int) $hint;
		}

		if ($hasCompletionFlag) {
			// List đủ bài (có/không hoàn thành)
			$n = count($list);
			if ($n > 0) {
				return max(0, min(100, (int) round(($done / $n) * 100)));
			}
		}

		// List chỉ bài đã xong — cần tổng khóa
		if ($total !== null && $total > 0) {
			$numer = $maxOrder > 0 ? $maxOrder : $done;
			return max(0, min(100, (int) round(($numer / $total) * 100)));
		}

		// Có % từng bài → trung bình
		if ($pctCount > 0) {
			return max(0, min(100, (int) round($pctSum / $pctCount)));
		}

		// Không đủ dữ liệu để suy ra % (tránh trả 0 giả)
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
