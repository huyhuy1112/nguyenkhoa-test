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
				next_owner VARCHAR(128) NULL,
				next_due DATE NULL,
				snooze_until DATE NULL,
				opened_at DATETIME NOT NULL,
				closed_at DATETIME NULL,
				KEY idx_nl_open (code, contact_id, status)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			array()
		);
		$ownerCol = $adb->pquery("SHOW COLUMNS FROM mk_nl_alerts LIKE 'next_owner'", array());
		if (!$ownerCol || $adb->num_rows($ownerCol) < 1) {
			$adb->pquery('ALTER TABLE mk_nl_alerts ADD COLUMN next_owner VARCHAR(128) NULL AFTER next_task', array());
		}
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
		self::autoResolve($adb, $settings);
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
		$opened += self::scanTasksAndOpps($adb);
		$opened += self::scanFirstContact($adb, $settings);
		$opened += self::scanStudiedNotBought($adb, $settings);
		$opened += self::scanReorderAndDrop($adb, $settings);
		$opened += self::scanLateDelivery($adb, $settings);
		$opened += self::scanLargeCustomerRisk($adb, $settings);
		return array('opened' => $opened);
	}

	protected static function scanTasksAndOpps($adb) {
		$opened = 0;
		try {
			$tasks = $adb->pquery(
				"SELECT rel.contactid, act.subject, act.date_start
				 FROM vtiger_activity act
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = act.activityid AND ce.deleted = 0
				 INNER JOIN vtiger_cntactivityrel rel ON rel.activityid = act.activityid
				 WHERE rel.contactid > 0 AND act.date_start < CURDATE()
				   AND (act.eventstatus IS NULL OR act.eventstatus NOT IN ('Held','Cancelled'))
				   AND (act.status IS NULL OR act.status NOT IN ('Completed','Cancelled'))
				 LIMIT 60",
				array()
			);
			if ($tasks) {
				while ($row = $adb->fetchByAssoc($tasks)) {
					if (self::openAlert((int) $row['contactid'], 'NL03', 'Nhiệm vụ quá hạn', $row['subject'] . ' · ' . $row['date_start'])) {
						$opened++;
					}
				}
			}
		} catch (Exception $e) {
			// bảng lịch chưa đủ
		}
		try {
			$opps = $adb->pquery(
				"SELECT p.potentialid, p.potentialname, p.contact_id
				 FROM vtiger_potential p
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = p.potentialid AND ce.deleted = 0
				 WHERE p.contact_id > 0
				   AND p.sales_stage NOT IN ('Closed Won','Closed Lost')
				   AND NOT EXISTS (
						SELECT 1 FROM vtiger_seactivityrel sr
						INNER JOIN vtiger_activity a ON a.activityid = sr.activityid
						INNER JOIN vtiger_crmentity ace ON ace.crmid = a.activityid AND ace.deleted = 0
						WHERE sr.crmid = p.potentialid
						  AND (a.eventstatus IS NULL OR a.eventstatus NOT IN ('Held','Cancelled'))
						  AND (a.status IS NULL OR a.status NOT IN ('Completed','Cancelled'))
				   )
				 LIMIT 40",
				array()
			);
			if ($opps) {
				while ($row = $adb->fetchByAssoc($opps)) {
					if (self::openAlert((int) $row['contact_id'], 'NL04', 'Cơ hội đang mở, chưa có bước tiếp', $row['potentialname'])) {
						$opened++;
					}
				}
			}
		} catch (Exception $e) {
			// cơ hội chưa đủ bảng
		}
		return $opened;
	}

	protected static function scanFirstContact($adb, array $settings) {
		$hours = self::numSetting($settings, 'sla_first_contact_hours');
		if ($hours === null) {
			return 0;
		}
		$opened = 0;
		$res = $adb->pquery(
			"SELECT cd.contactid, ce.createdtime
			 FROM vtiger_contactdetails cd
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = cd.contactid AND ce.deleted = 0 AND ce.setype = 'Contacts'
			 WHERE ce.createdtime < DATE_SUB(NOW(), INTERVAL " . (int) $hours . " HOUR)
			   AND NOT EXISTS (
					SELECT 1 FROM vtiger_cntactivityrel rel
					INNER JOIN vtiger_activity act ON act.activityid = rel.activityid
					INNER JOIN vtiger_crmentity ace ON ace.crmid = act.activityid AND ace.deleted = 0
					WHERE rel.contactid = cd.contactid AND act.activitytype = 'Call'
			   )
			 ORDER BY ce.createdtime DESC
			 LIMIT 40",
			array()
		);
		if ($res) {
			while ($row = $adb->fetchByAssoc($res)) {
				if (self::openAlert((int) $row['contactid'], 'NL01', 'Khách mới chưa được liên hệ', 'Tạo hồ sơ ' . $row['createdtime'])) {
					$opened++;
				}
			}
		}
		return $opened;
	}

	protected static function scanStudiedNotBought($adb, array $settings) {
		$days = self::numSetting($settings, 'follow_after_class_days');
		if ($days === null) {
			return 0;
		}
		$opened = 0;
		$res = $adb->pquery(
			"SELECT fo.object_id AS contactid, ce.createdtime
			 FROM vtiger_freetagged_objects fo
			 INNER JOIN vtiger_freetags t ON t.id = fo.tag_id
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = fo.object_id AND ce.deleted = 0 AND ce.setype = 'Contacts'
			 WHERE LOWER(t.tag) IN ('da_tham_gia','mien_phi_offline','mien_phi_online','offline_da_tham_gia','gd14_da_tham_gia')
			   AND ce.createdtime < DATE_SUB(NOW(), INTERVAL " . (int) $days . " DAY)
			 LIMIT 40",
			array()
		);
		if ($res) {
			while ($row = $adb->fetchByAssoc($res)) {
				$metrics = self::contactMetrics((int) $row['contactid']);
				if ((int) $metrics['valid_orders'] > 0) {
					continue;
				}
				if (self::openAlert((int) $row['contactid'], 'NL02', 'Đã học nhưng chưa mua nguyên liệu', 'Chưa có đơn giao xong')) {
					$opened++;
				}
			}
		}
		return $opened;
	}

	protected static function scanReorderAndDrop($adb, array $settings) {
		$lead = self::numSetting($settings, 'reorder_lead_days');
		$grace = self::numSetting($settings, 'reorder_grace_days');
		$drop = self::numSetting($settings, 'drop_ratio');
		if ($lead === null && $grace === null && $drop === null) {
			return 0;
		}
		$opened = 0;
		$res = $adb->pquery(
			"SELECT so.contactid, COUNT(*) AS n
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE so.contactid > 0 AND so.sostatus = 'Delivered'
			 GROUP BY so.contactid
			 HAVING n >= 2
			 LIMIT 40",
			array()
		);
		if (!$res) {
			return 0;
		}
		while ($row = $adb->fetchByAssoc($res)) {
			$contactId = (int) $row['contactid'];
			$orders = self::ordersForContact($contactId);
			$valid = array();
			foreach ($orders as $order) {
				if (self::isDelivered(strtolower($order['status']))) {
					$valid[] = $order;
				}
			}
			if (count($valid) >= 4) {
				$older = array_slice($valid, 1);
				$olderGap = self::medianGap($older);
				$returnGap = (int) floor(($valid[0]['ts'] - $valid[1]['ts']) / 86400);
				if ($olderGap !== null && $returnGap > ($olderGap * 2)) {
					self::logOnce('Contacts', $contactId, 'KD02', 'Mua trở lại', 'Khoảng cách trước đơn mới ' . $returnGap . ' ngày, kỳ trước đó ' . $olderGap . ' ngày');
				}
			}
			if (count($valid) === 2) {
				self::logOnce('Contacts', $contactId, 'KD01', 'Mua lần hai', 'Đã có đơn giao xong thứ hai');
			}
			$gap = self::medianGap($valid);
			if ($gap !== null && !empty($valid)) {
				$age = (int) floor((time() - $valid[0]['ts']) / 86400);
				if ($grace !== null && $age > $gap + $grace) {
					if (self::openAlert($contactId, 'NL09', 'Quá kỳ mua lại', 'Cách lần mua trước ' . $age . ' ngày, kỳ điển hình ' . $gap . ' ngày')) {
						$opened++;
					}
				} elseif ($lead !== null && $age >= max(0, $gap - $lead)) {
					if (self::openAlert($contactId, 'NL08', 'Sắp đến kỳ mua lại', 'Cách lần mua trước ' . $age . ' ngày, kỳ điển hình ' . $gap . ' ngày')) {
						$opened++;
					}
				}
			}
			if ($drop !== null && count($valid) >= 4) {
				$now = time();
				$recent = self::sumSince($valid, $now - 90 * 86400);
				$prior = 0;
				foreach ($valid as $order) {
					if ($order['ts'] < $now - 90 * 86400 && $order['ts'] >= $now - 180 * 86400) {
						$prior += $order['total'];
					}
				}
				if ($prior > 0 && ($prior - $recent) / $prior >= $drop) {
					if (self::openAlert($contactId, 'NL10', 'Doanh thu mua giảm bất thường', '90 ngày này ' . self::money($recent) . ', 90 ngày trước ' . self::money($prior))) {
						$opened++;
					}
				}
				$opened += self::scanSkuDrop($adb, $contactId, $drop);
			}
		}
		return $opened;
	}

	protected static function scanSkuDrop($adb, $contactId, $drop) {
		$res = $adb->pquery(
			"SELECT ipr.productid, ipr.quantity, ce.createdtime
			 FROM vtiger_inventoryproductrel ipr
			 INNER JOIN vtiger_salesorder so ON so.salesorderid = ipr.id
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE so.contactid = ? AND so.sostatus = 'Delivered'",
			array($contactId)
		);
		if (!$res) {
			return 0;
		}
		$now = time();
		$recent = array();
		$prior = array();
		while ($row = $adb->fetchByAssoc($res)) {
			$ts = strtotime((string) $row['createdtime']);
			$pid = (int) $row['productid'];
			$qty = (float) $row['quantity'];
			if ($ts >= $now - 90 * 86400) {
				$recent[$pid] = (isset($recent[$pid]) ? $recent[$pid] : 0) + $qty;
			} elseif ($ts >= $now - 180 * 86400) {
				$prior[$pid] = (isset($prior[$pid]) ? $prior[$pid] : 0) + $qty;
			}
		}
		foreach ($prior as $pid => $oldQty) {
			if ($oldQty <= 0) {
				continue;
			}
			$newQty = isset($recent[$pid]) ? $recent[$pid] : 0;
			if (($oldQty - $newQty) / $oldQty >= $drop) {
				if (self::openAlert($contactId, 'NL11', 'Một mặt hàng mua ít đi', 'Sản phẩm #' . $pid)) {
					return 1;
				}
			}
		}
		return 0;
	}

	protected static function logOnce($module, $recordId, $step, $result, $note) {
		require_once 'modules/Vtiger/models/CareActivityService.php';
		Vtiger_CareActivityService::install();
		$adb = PearDatabase::getInstance();
		$exists = $adb->pquery(
			'SELECT id FROM mk_care_activity WHERE module = ? AND record_id = ? AND step_code = ? LIMIT 1',
			array($module, (int) $recordId, $step)
		);
		if ($exists && $adb->num_rows($exists) > 0) {
			return;
		}
		Vtiger_CareActivityService::log($module, $recordId, $step, $result, $note);
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
		$owner = trim((string) (isset($payload['next_owner']) ? $payload['next_owner'] : ''));
		$due = trim((string) (isset($payload['next_due']) ? $payload['next_due'] : ''));
		$snooze = trim((string) (isset($payload['snooze_until']) ? $payload['snooze_until'] : ''));
		$row = $adb->pquery('SELECT code, contact_id, title FROM mk_nl_alerts WHERE id = ?', array($id));
		$code = ($row && $adb->num_rows($row) > 0) ? (string) $adb->query_result($row, 0, 'code') : '';
		if ($next === 'done' && ($note === '' || $evidence === '')) {
			throw new Exception('Hoàn thành cần kết quả và bằng chứng (đơn, cuộc gọi, chứng từ).');
		}
		if ($next === 'done' && self::needsFollowUp($code) && ($task === '' || $owner === '' || $due === '')) {
			throw new Exception('Khách còn cần theo dõi. Hoàn thành phải có việc tiếp theo, người phụ trách và hạn.');
		}
		if ($next === 'snoozed' && ($note === '' || $snooze === '')) {
			throw new Exception('Tạm hoãn cần lý do và ngày kiểm tra lại.');
		}
		if ($next === 'na' && $note === '') {
			throw new Exception('Không áp dụng cần lý do.');
		}
		$closed = in_array($next, array('done', 'na'), true) ? date('Y-m-d H:i:s') : null;
		$adb->pquery(
			'UPDATE mk_nl_alerts
			 SET status = ?, result_note = ?, evidence_ref = ?, next_task = ?, next_owner = ?, next_due = ?, snooze_until = ?, closed_at = ?
			 WHERE id = ?',
			array($next, $note, $evidence, $task, $owner, $due !== '' ? $due : null, $snooze !== '' ? $snooze : null, $closed, $id)
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

	protected static function needsFollowUp($code) {
		return in_array($code, array('NL01', 'NL02', 'NL07', 'NL08', 'NL09', 'NL10', 'NL11', 'NL12', 'NL16', 'NL18'), true);
	}

	protected static function closeOpen($code, $contactId, $note) {
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			"UPDATE mk_nl_alerts
			 SET status = 'done', result_note = ?, evidence_ref = 'CRM', closed_at = ?
			 WHERE code = ? AND contact_id = ? AND status NOT IN ('done','na')",
			array($note, date('Y-m-d H:i:s'), $code, (int) $contactId)
		);
	}

	protected static function hasTable($table) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery('SHOW TABLES LIKE ?', array($table));
		return $res && $adb->num_rows($res) > 0;
	}

	protected static function hasColumn($table, $column) {
		if (!self::hasTable($table)) {
			return false;
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery('SHOW COLUMNS FROM ' . $table . ' LIKE ?', array($column));
		return $res && $adb->num_rows($res) > 0;
	}

	/**
	 * Tắt việc khi CRM đã chứng minh vấn đề hết: báo giá xong, đơn đã thu/hủy, hoặc có đơn giao mới.
	 */
	protected static function autoResolve($adb, array $settings) {
		$remind = self::numSetting($settings, 'quote_remind_days');
		$open = $adb->pquery(
			"SELECT id, code, contact_id, opened_at FROM mk_nl_alerts WHERE status NOT IN ('done','na') AND code IN ('NL05','NL06','NL08','NL09','NL16')",
			array()
		);
		if (!$open) {
			return;
		}
		while ($row = $adb->fetchByAssoc($open)) {
			$contactId = (int) $row['contact_id'];
			$code = (string) $row['code'];
			if ($code === 'NL05' && !self::quoteStillDue($adb, $contactId, $remind)) {
				self::closeOpen($code, $contactId, 'Báo giá đã được chấp nhận, từ chối hoặc không còn quá hạn.');
			} elseif ($code === 'NL06' && !self::paymentStillOverdue($adb, $contactId)) {
				self::closeOpen($code, $contactId, 'Đơn đã thu tiền, đã giao hoặc đã hủy.');
			} elseif (($code === 'NL08' || $code === 'NL09') && self::deliveredAfter($adb, $contactId, (string) $row['opened_at'])) {
				self::closeOpen($code, $contactId, 'Đã có đơn giao xong sau khi mở việc.');
			} elseif ($code === 'NL16' && !self::deliveryStillLate($adb, $contactId)) {
				self::closeOpen($code, $contactId, 'Đơn giao trễ đã hoàn tất đúng việc hoặc không còn quá hạn.');
			}
		}
	}

	protected static function quoteStillDue($adb, $contactId, $remind) {
		$res = $adb->pquery(
			"SELECT validtill FROM vtiger_quotes q
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = q.quoteid AND ce.deleted = 0
			 WHERE q.contactid = ? AND q.quotestage NOT IN ('Accepted','Rejected')
			   AND q.validtill IS NOT NULL AND q.validtill <> ''",
			array($contactId)
		);
		if (!$res) {
			return false;
		}
		while ($row = $adb->fetchByAssoc($res)) {
			$till = strtotime((string) $row['validtill']);
			if (!$till) {
				continue;
			}
			$days = (int) floor(($till - time()) / 86400);
			if ($days < 0 || ($remind !== null && $days <= $remind)) {
				return true;
			}
		}
		return false;
	}

	protected static function paymentStillOverdue($adb, $contactId) {
		$res = $adb->pquery(
			"SELECT salesorderid FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE so.contactid = ? AND so.duedate IS NOT NULL AND so.duedate <> '' AND so.duedate < CURDATE()
			   AND so.sostatus NOT IN ('Delivered','Cancelled','Cancel')
			 LIMIT 1",
			array($contactId)
		);
		return $res && $adb->num_rows($res) > 0;
	}

	protected static function deliveredAfter($adb, $contactId, $openedAt) {
		$opened = strtotime($openedAt);
		if (!$opened) {
			return false;
		}
		$res = $adb->pquery(
			"SELECT ce.createdtime FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE so.contactid = ? AND so.sostatus = 'Delivered'
			 ORDER BY ce.createdtime DESC LIMIT 1",
			array($contactId)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return false;
		}
		$ts = strtotime((string) $adb->query_result($res, 0, 'createdtime'));
		return $ts && $ts > $opened;
	}

	protected static function lateDeliverySqlReady($adb) {
		return self::hasTable('vtiger_goodsissue')
			&& self::hasColumn('vtiger_goodsissue', 'salesorder_id')
			&& self::hasColumn('vtiger_goodsissue', 'status')
			&& self::hasColumn('vtiger_goodsissue', 'issued_date')
			&& self::hasColumn('vtiger_goodsissue', 'deleted')
			&& self::hasColumn('vtiger_salesorder', 'duedate');
	}

	protected static function deliveryStillLate($adb, $contactId) {
		if (!self::lateDeliverySqlReady($adb)) {
			return false;
		}
		$res = $adb->pquery(
			"SELECT so.salesorderid
			 FROM vtiger_goodsissue gi
			 INNER JOIN vtiger_salesorder so ON so.salesorderid = gi.salesorder_id
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE gi.deleted = 0 AND so.contactid = ?
			   AND so.duedate IS NOT NULL AND so.duedate <> '' AND so.duedate <> '0000-00-00' AND so.duedate < CURDATE()
			   AND NOT (gi.status = 'completed' AND gi.issued_date IS NOT NULL AND gi.issued_date <= so.duedate)
			 LIMIT 1",
			array($contactId)
		);
		return $res && $adb->num_rows($res) > 0;
	}

	protected static function scanLateDelivery($adb, array $settings) {
		if (!self::lateDeliverySqlReady($adb)) {
			return 0;
		}
		$opened = 0;
		$res = $adb->pquery(
			"SELECT so.contactid, so.salesorder_no, so.duedate
			 FROM vtiger_goodsissue gi
			 INNER JOIN vtiger_salesorder so ON so.salesorderid = gi.salesorder_id
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE gi.deleted = 0 AND so.contactid > 0
			   AND so.duedate IS NOT NULL AND so.duedate <> '' AND so.duedate <> '0000-00-00' AND so.duedate < CURDATE()
			   AND NOT (gi.status = 'completed' AND gi.issued_date IS NOT NULL AND gi.issued_date <= so.duedate)
			 ORDER BY so.duedate ASC
			 LIMIT 80",
			array()
		);
		$seen = array();
		if ($res) {
			while ($row = $adb->fetchByAssoc($res)) {
				$contactId = (int) $row['contactid'];
				if (isset($seen[$contactId])) {
					continue;
				}
				$seen[$contactId] = true;
				if (self::openAlert($contactId, 'NL16', 'Đơn ' . $row['salesorder_no'] . ' giao trễ', 'Hạn giao ' . $row['duedate'])) {
					$opened++;
				}
			}
		}
		$repeat = self::numSetting($settings, 'repeat_incident_count');
		if ($repeat === null || $repeat < 1) {
			return $opened;
		}
		$counts = $adb->pquery(
			"SELECT so.contactid, COUNT(DISTINCT so.salesorderid) AS n
			 FROM vtiger_goodsissue gi
			 INNER JOIN vtiger_salesorder so ON so.salesorderid = gi.salesorder_id
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE gi.deleted = 0 AND so.contactid > 0
			   AND so.duedate IS NOT NULL AND so.duedate <> '' AND so.duedate <> '0000-00-00'
			   AND (
					(gi.status = 'completed' AND gi.issued_date IS NOT NULL AND gi.issued_date > so.duedate)
					OR (so.duedate < CURDATE() AND NOT (gi.status = 'completed' AND gi.issued_date IS NOT NULL AND gi.issued_date <= so.duedate))
			   )
			 GROUP BY so.contactid
			 HAVING n >= ?
			 LIMIT 40",
			array((int) $repeat)
		);
		if ($counts) {
			while ($row = $adb->fetchByAssoc($counts)) {
				if (self::openAlert((int) $row['contactid'], 'NL18', 'Sự cố giao lặp lại', (int) $row['n'] . ' đơn giao trễ')) {
					$opened++;
				}
			}
		}
		return $opened;
	}

	protected static function scanLargeCustomerRisk($adb, array $settings) {
		$silver = self::numSetting($settings, 'tier_silver');
		$gold = self::numSetting($settings, 'tier_gold');
		if ($silver === null && $gold === null) {
			return 0;
		}
		$res = $adb->pquery(
			"SELECT DISTINCT contact_id FROM mk_nl_alerts
			 WHERE code IN ('NL09','NL10','NL11') AND status NOT IN ('done','na') AND contact_id > 0
			 LIMIT 40",
			array()
		);
		if (!$res) {
			return 0;
		}
		$opened = 0;
		while ($row = $adb->fetchByAssoc($res)) {
			$contactId = (int) $row['contact_id'];
			$orders = self::ordersForContact($contactId);
			$valid = array();
			foreach ($orders as $order) {
				if (self::isDelivered(strtolower($order['status']))) {
					$valid[] = $order;
				}
			}
			$period = self::sumSince($valid, strtotime('-90 days'));
			$tier = self::tierFromValue($period, $silver, $gold);
			if ($tier !== 'Bạc' && $tier !== 'Vàng') {
				continue;
			}
			self::logOnce('Contacts', $contactId, 'KD03', 'Khách lớn mất giá trị', 'Hạng ' . $tier . ' đang có việc quá kỳ, giảm mua hoặc mất mặt hàng');
			if (self::openAlert($contactId, 'NL12', 'Khách lớn đang rủi ro', 'Hạng ' . $tier . ' · dùng chung hồ sơ với việc mua lại hoặc giảm mua')) {
				$opened++;
			}
		}
		return $opened;
	}

	protected static function tierFromValue($periodValue, $silver, $gold) {
		if ($gold === null && $silver === null) {
			return '';
		}
		if ($gold !== null && $periodValue >= $gold) {
			return 'Vàng';
		}
		if ($silver !== null && $periodValue >= $silver) {
			return 'Bạc';
		}
		return '';
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
