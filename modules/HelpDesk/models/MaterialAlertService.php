<?php
/**
 * Cảnh báo nguyên liệu — lớp 1.
 * Ngưỡng để trống thì không bắn cảnh báo định lượng.
 */
class HelpDesk_MaterialAlertService {

	public static function install() {
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'CREATE TABLE IF NOT EXISTS mk_nl_settings (
				setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
				setting_value VARCHAR(64) NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			array()
		);
		$adb->pquery(
			'CREATE TABLE IF NOT EXISTS mk_nl_alerts (
				id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				code VARCHAR(8) NOT NULL,
				contact_id INT UNSIGNED NOT NULL DEFAULT 0,
				title VARCHAR(255) NOT NULL,
				detail TEXT NULL,
				status VARCHAR(16) NOT NULL DEFAULT \'new\',
				result_note TEXT NULL,
				evidence_ref VARCHAR(255) NULL,
				next_task VARCHAR(255) NULL,
				next_due DATE NULL,
				snooze_until DATE NULL,
				opened_at DATETIME NOT NULL,
				closed_at DATETIME NULL,
				KEY idx_nl_open (code, contact_id, status)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			array()
		);
	}

	public static function settingFields() {
		return array(
			array('key' => 'sla_first_contact_hours', 'label' => 'Hạn liên hệ khách mới (giờ)', 'hint' => 'NL01'),
			array('key' => 'follow_after_class_days', 'label' => 'Hạn theo dõi sau học, chưa mua (ngày)', 'hint' => 'NL02'),
			array('key' => 'quote_remind_days', 'label' => 'Nhắc trước khi báo giá hết hạn (ngày)', 'hint' => 'NL05'),
			array('key' => 'second_purchase_days', 'label' => 'Kỳ dự kiến mua lần hai (ngày)', 'hint' => 'NL07'),
			array('key' => 'reorder_lead_days', 'label' => 'Nhắc trước kỳ mua lại (ngày)', 'hint' => 'NL08'),
			array('key' => 'reorder_grace_days', 'label' => 'Dung sai quá kỳ mua lại (ngày)', 'hint' => 'NL09'),
			array('key' => 'drop_ratio', 'label' => 'Ngưỡng giảm sản lượng/doanh thu (0–1)', 'hint' => 'NL10'),
			array('key' => 'repeat_incident_count', 'label' => 'Số sự cố giao lặp để báo quản lý', 'hint' => 'NL18'),
			array('key' => 'tier_silver', 'label' => 'Ngưỡng hạng Bạc (giá trị mua trong kỳ)', 'hint' => 'CT09'),
			array('key' => 'tier_gold', 'label' => 'Ngưỡng hạng Vàng (giá trị mua trong kỳ)', 'hint' => 'CT09'),
		);
	}

	public static function getSettings() {
		self::install();
		$adb = PearDatabase::getInstance();
		$out = array();
		foreach (self::settingFields() as $field) {
			$out[$field['key']] = '';
		}
		$res = $adb->pquery('SELECT setting_key, setting_value FROM mk_nl_settings', array());
		if ($res) {
			while ($row = $adb->fetchByAssoc($res)) {
				$out[$row['setting_key']] = (string) $row['setting_value'];
			}
		}
		return $out;
	}

	public static function saveSettings(array $payload) {
		self::install();
		$adb = PearDatabase::getInstance();
		$allowed = array();
		foreach (self::settingFields() as $field) {
			$allowed[$field['key']] = true;
		}
		foreach ($payload as $key => $value) {
			if (!isset($allowed[$key])) {
				continue;
			}
			$value = trim((string) $value);
			if ($value !== '' && !is_numeric(str_replace(',', '.', $value))) {
				throw new Exception('Ngưỡng phải là số hoặc để trống.');
			}
			$adb->pquery(
				'INSERT INTO mk_nl_settings (setting_key, setting_value) VALUES (?,?)
				 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
				array($key, $value === '' ? null : $value)
			);
		}
		return self::getSettings();
	}

	protected static function numSetting(array $settings, $key) {
		if (!isset($settings[$key]) || $settings[$key] === '') {
			return null;
		}
		return (float) str_replace(',', '.', $settings[$key]);
	}

	public static function contactMetrics($contactId) {
		$contactId = (int) $contactId;
		$orders = self::ordersForContact($contactId);
		$valid = array();
		$waitingPay = 0;
		foreach ($orders as $order) {
			$status = strtolower($order['status']);
			if (strpos($status, 'cancel') !== false || strpos($status, 'hủy') !== false || strpos($status, 'huy') !== false) {
				continue;
			}
			if (self::isDelivered($status)) {
				$valid[] = $order;
			} else {
				$waitingPay += (float) $order['total'];
			}
		}
		$gap = self::medianGap($valid);
		$periodValue = self::sumSince($valid, strtotime('-90 days'));
		$tier = self::tierLabel($periodValue);
		return array(
			'contact_id' => $contactId,
			'valid_orders' => count($valid),
			'ct01' => self::money(self::sumAll($valid)),
			'ct02' => count($valid) ? self::money($periodValue) : 'chưa đủ dữ liệu',
			'ct03' => count($valid) ? (string) count($valid) : 'chưa đủ dữ liệu',
			'ct04' => count($valid) ? self::money(self::sumAll($valid) / count($valid)) : 'chưa đủ dữ liệu',
			'ct05' => count($valid) ? $valid[0]['day'] : 'chưa đủ dữ liệu',
			'ct07' => $gap === null ? 'chưa đủ dữ liệu' : ($gap . ' ngày'),
			'ct08' => self::money($waitingPay),
			'ct09_life' => self::lifeLabel(count($valid), $gap, $valid),
			'ct09_tier' => $tier,
			'enough' => count($valid) >= 3,
		);
	}

	public static function refresh() {
		self::install();
		$adb = PearDatabase::getInstance();
		$settings = self::getSettings();
		$opened = 0;
		$quotes = $adb->pquery(
			"SELECT q.quoteid, q.contactid, q.quote_no, q.validtill, q.quotestage
			 FROM vtiger_quotes q
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = q.quoteid AND ce.deleted = 0
			 WHERE q.contactid > 0 AND q.validtill IS NOT NULL AND q.validtill <> ''
			   AND q.quotestage NOT IN ('Accepted','Rejected')
			 ORDER BY q.validtill ASC
			 LIMIT 80",
			array()
		);
		$remind = self::numSetting($settings, 'quote_remind_days');
		if ($quotes) {
			while ($row = $adb->fetchByAssoc($quotes)) {
				$till = strtotime((string) $row['validtill']);
				if (!$till) {
					continue;
				}
				$days = (int) floor(($till - time()) / 86400);
				$due = $days < 0 || ($remind !== null && $days <= $remind);
				if (!$due) {
					continue;
				}
				if (self::openAlert((int) $row['contactid'], 'NL05', 'Báo giá ' . $row['quote_no'] . ' sắp hoặc đã quá hạn', 'Hết hạn ' . $row['validtill'])) {
					$opened++;
				}
			}
		}
		$orders = $adb->pquery(
			"SELECT so.salesorderid, so.contactid, so.salesorder_no, so.sostatus, so.duedate
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE so.contactid > 0 AND so.duedate IS NOT NULL AND so.duedate <> '' AND so.duedate < CURDATE()
			   AND so.sostatus NOT IN ('Delivered','Cancelled','Cancel')
			 ORDER BY so.duedate ASC
			 LIMIT 80",
			array()
		);
		if ($orders) {
			while ($row = $adb->fetchByAssoc($orders)) {
				if (self::openAlert((int) $row['contactid'], 'NL06', 'Đơn ' . $row['salesorder_no'] . ' chờ thanh toán quá hẹn', 'Hạn ' . $row['duedate'] . ' · ' . $row['sostatus'])) {
					$opened++;
				}
			}
		}
		$secondDays = self::numSetting($settings, 'second_purchase_days');
		if ($secondDays !== null) {
			$contacts = $adb->pquery(
				"SELECT so.contactid
				 FROM vtiger_salesorder so
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
				 WHERE so.contactid > 0 AND so.sostatus IN ('Delivered')
				 GROUP BY so.contactid
				 HAVING COUNT(*) = 1
				 LIMIT 80",
				array()
			);
			if ($contacts) {
				while ($row = $adb->fetchByAssoc($contacts)) {
					$metrics = self::contactMetrics((int) $row['contactid']);
					if ($metrics['ct05'] === 'chưa đủ dữ liệu') {
						continue;
					}
					$age = (int) floor((time() - strtotime($metrics['ct05'])) / 86400);
					if ($age <= $secondDays) {
						continue;
					}
					if (self::openAlert((int) $row['contactid'], 'NL07', 'Mua lần đầu chưa mua lần hai', 'Đơn gần nhất ' . $metrics['ct05'])) {
						$opened++;
					}
				}
			}
		}
		return array('opened' => $opened);
	}

	public static function listAlerts($status = '') {
		self::install();
		self::liftSnoozes();
		$adb = PearDatabase::getInstance();
		$sql = 'SELECT a.*, cd.firstname, cd.lastname
			FROM mk_nl_alerts a
			LEFT JOIN vtiger_contactdetails cd ON cd.contactid = a.contact_id
			WHERE 1=1';
		$params = array();
		if ($status !== '' && $status !== 'all') {
			$sql .= ' AND a.status = ?';
			$params[] = $status;
		} else {
			$sql .= " AND a.status NOT IN ('done','na')";
		}
		$sql .= ' ORDER BY a.opened_at DESC LIMIT 200';
		$res = $adb->pquery($sql, $params);
		$rows = array();
		if ($res) {
			while ($row = $adb->fetchByAssoc($res)) {
				$name = trim($row['firstname'] . ' ' . $row['lastname']);
				$rows[] = array(
					'id' => (int) $row['id'],
					'code' => $row['code'],
					'contact_id' => (int) $row['contact_id'],
					'name' => $name !== '' ? $name : 'Khách #' . $row['contact_id'],
					'title' => $row['title'],
					'detail' => $row['detail'],
					'status' => $row['status'],
					'snooze_until' => $row['snooze_until'],
				);
			}
		}
		return $rows;
	}

	public static function transition($id, $next, array $payload) {
		self::install();
		$adb = PearDatabase::getInstance();
		$id = (int) $id;
		$allowed = array('accepted', 'working', 'done', 'snoozed', 'na');
		if (!in_array($next, $allowed, true)) {
			throw new Exception('Trạng thái không hợp lệ.');
		}
		$note = trim((string) (isset($payload['result_note']) ? $payload['result_note'] : ''));
		$evidence = trim((string) (isset($payload['evidence_ref']) ? $payload['evidence_ref'] : ''));
		$task = trim((string) (isset($payload['next_task']) ? $payload['next_task'] : ''));
		$due = trim((string) (isset($payload['next_due']) ? $payload['next_due'] : ''));
		$snooze = trim((string) (isset($payload['snooze_until']) ? $payload['snooze_until'] : ''));
		if ($next === 'done' && ($note === '' || $evidence === '')) {
			throw new Exception('Hoàn thành cần kết quả và bằng chứng (đơn, cuộc gọi, chứng từ).');
		}
		if ($next === 'snoozed' && ($note === '' || $snooze === '')) {
			throw new Exception('Tạm hoãn cần lý do và ngày kiểm tra lại.');
		}
		if ($next === 'na' && $note === '') {
			throw new Exception('Không áp dụng cần lý do.');
		}
		$closed = in_array($next, array('done', 'na'), true) ? date('Y-m-d H:i:s') : null;
		$row = $adb->pquery('SELECT code, contact_id, title FROM mk_nl_alerts WHERE id = ?', array($id));
		$adb->pquery(
			'UPDATE mk_nl_alerts
			 SET status = ?, result_note = ?, evidence_ref = ?, next_task = ?, next_due = ?, snooze_until = ?, closed_at = ?
			 WHERE id = ?',
			array($next, $note, $evidence, $task, $due !== '' ? $due : null, $snooze !== '' ? $snooze : null, $closed, $id)
		);
		if ($row && $adb->num_rows($row) > 0) {
			require_once 'modules/Vtiger/models/CareActivityService.php';
			$labels = array(
				'accepted' => 'Đã nhận việc',
				'working' => 'Đang xử lý',
				'done' => 'Hoàn thành',
				'snoozed' => 'Tạm hoãn',
				'na' => 'Không áp dụng',
			);
			Vtiger_CareActivityService::log(
				'Contacts',
				(int) $adb->query_result($row, 0, 'contact_id'),
				(string) $adb->query_result($row, 0, 'code'),
				isset($labels[$next]) ? $labels[$next] : $next,
				trim($note . ($evidence !== '' ? ' · ' . $evidence : ''))
			);
		}
		return true;
	}

	public static function summary() {
		self::install();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			"SELECT code, COUNT(*) AS n FROM mk_nl_alerts WHERE status NOT IN ('done','na') GROUP BY code",
			array()
		);
		$by = array();
		$total = 0;
		if ($res) {
			while ($row = $adb->fetchByAssoc($res)) {
				$by[$row['code']] = (int) $row['n'];
				$total += (int) $row['n'];
			}
		}
		return array('total' => $total, 'by_code' => $by);
	}

	protected static function openAlert($contactId, $code, $title, $detail) {
		if ($contactId <= 0) {
			return false;
		}
		$adb = PearDatabase::getInstance();
		$exists = $adb->pquery(
			"SELECT id FROM mk_nl_alerts WHERE code = ? AND contact_id = ? AND status NOT IN ('done','na') LIMIT 1",
			array($code, $contactId)
		);
		if ($exists && $adb->num_rows($exists) > 0) {
			return false;
		}
		$adb->pquery(
			'INSERT INTO mk_nl_alerts (code, contact_id, title, detail, status, opened_at) VALUES (?,?,?,?,?,?)',
			array($code, $contactId, $title, $detail, 'new', date('Y-m-d H:i:s'))
		);
		return true;
	}

	protected static function liftSnoozes() {
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			"UPDATE mk_nl_alerts SET status = 'working' WHERE status = 'snoozed' AND snooze_until IS NOT NULL AND snooze_until <= CURDATE()",
			array()
		);
	}

	protected static function ordersForContact($contactId) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT so.salesorderid, so.total, so.sostatus, ce.createdtime
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE so.contactid = ?
			 ORDER BY ce.createdtime DESC',
			array((int) $contactId)
		);
		$rows = array();
		if ($res) {
			while ($row = $adb->fetchByAssoc($res)) {
				$rows[] = array(
					'total' => (float) $row['total'],
					'status' => (string) $row['sostatus'],
					'day' => substr((string) $row['createdtime'], 0, 10),
					'ts' => strtotime((string) $row['createdtime']),
				);
			}
		}
		return $rows;
	}

	protected static function isDelivered($status) {
		return strpos($status, 'deliver') !== false || strpos($status, 'giao') !== false || $status === 'paid';
	}

	protected static function sumAll(array $orders) {
		$sum = 0;
		foreach ($orders as $order) {
			$sum += $order['total'];
		}
		return $sum;
	}

	protected static function sumSince(array $orders, $fromTs) {
		$sum = 0;
		foreach ($orders as $order) {
			if ($order['ts'] >= $fromTs) {
				$sum += $order['total'];
			}
		}
		return $sum;
	}

	protected static function medianGap(array $orders) {
		if (count($orders) < 3) {
			return null;
		}
		$times = array();
		foreach ($orders as $order) {
			$times[] = $order['ts'];
		}
		sort($times);
		$gaps = array();
		for ($i = 1; $i < count($times); $i++) {
			$gaps[] = (int) floor(($times[$i] - $times[$i - 1]) / 86400);
		}
		sort($gaps);
		$mid = (int) floor(count($gaps) / 2);
		return $gaps[$mid];
	}

	protected static function lifeLabel($count, $gap, array $valid) {
		if ($count <= 0) {
			return 'Chưa mua';
		}
		if ($count === 1) {
			return 'Mua lần đầu';
		}
		if ($gap === null || empty($valid)) {
			return 'Mua lặp lại';
		}
		$age = (int) floor((time() - $valid[0]['ts']) / 86400);
		if ($age > $gap * 2) {
			return 'Ngưng mua';
		}
		if ($age > $gap) {
			return 'Giảm mua/Quá kỳ mua';
		}
		return 'Mua lặp lại';
	}

	protected static function tierLabel($periodValue) {
		$settings = self::getSettings();
		$gold = self::numSetting($settings, 'tier_gold');
		$silver = self::numSetting($settings, 'tier_silver');
		if ($gold === null && $silver === null) {
			return 'chưa đủ dữ liệu';
		}
		if ($gold !== null && $periodValue >= $gold) {
			return 'Vàng';
		}
		if ($silver !== null && $periodValue >= $silver) {
			return 'Bạc';
		}
		if ($silver === null) {
			return 'chưa đủ dữ liệu';
		}
		return 'Đồng';
	}

	protected static function money($amount) {
		return number_format((float) $amount, 0, ',', '.') . ' đ';
	}
}
