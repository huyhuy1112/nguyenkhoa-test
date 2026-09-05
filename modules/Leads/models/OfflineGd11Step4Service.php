<?php
/*+***********************************************************************************
 * Giai đoạn 1.1 — Bước 4: CSKH sau lớp / no-show.
 * OA (và Calendar fallback) theo mốc cấu hình được.
 *************************************************************************************/

require_once 'modules/Leads/models/OfflineGd11Service.php';

class Leads_OfflineGd11Step4Service {

	const TABLE_SETTINGS = 'bace_gd11_step4_settings';
	const PATH_ATTENDED = 'attended';
	const PATH_NOSHOW = 'noshow';

	public static function installSchema(PearDatabase $adb = null) {
		static $done = false;
		if ($done) {
			return;
		}
		if (!$adb) {
			$adb = PearDatabase::getInstance();
		}
		Leads_OfflineGd11Service::installSchema($adb);
		$cols = array(
			'offline_step4_entered_at' => 'DATETIME DEFAULT NULL',
			'offline_step4_path' => "VARCHAR(16) DEFAULT NULL",
			'offline_step4_sent' => "VARCHAR(64) DEFAULT ''",
		);
		foreach ($cols as $name => $def) {
			$res = $adb->pquery("SHOW COLUMNS FROM bace_lead_profile LIKE ?", array($name));
			if (!$res || $adb->num_rows($res) < 1) {
				$adb->pquery("ALTER TABLE bace_lead_profile ADD COLUMN {$name} {$def}", array());
			}
		}
		$tbl = $adb->pquery("SHOW TABLES LIKE '" . self::TABLE_SETTINGS . "'", array());
		if (!$tbl || $adb->num_rows($tbl) < 1) {
			$adb->pquery(
				'CREATE TABLE ' . self::TABLE_SETTINGS . ' (
					id INT NOT NULL PRIMARY KEY DEFAULT 1,
					config_json MEDIUMTEXT,
					modified_at DATETIME DEFAULT NULL
				) ENGINE=InnoDB DEFAULT CHARSET=utf8',
				array()
			);
		}
		$done = true;
	}

	public static function defaultConfig() {
		return array(
			'oa_enabled' => true,
			'milestones' => array(
				'a1' => array(
					'path' => self::PATH_ATTENDED,
					'label' => 'Cảm ơn sau lớp',
					'kb' => 'kb_a1',
					'channel' => 'oa',
					'when' => 'immediate',
				),
				'a2' => array(
					'path' => self::PATH_ATTENDED,
					'label' => 'Mời PCTH / học tiếp',
					'kb' => 'kb_a2',
					'channel' => 'oa',
					'when' => 'days_after',
					'days_after' => 1,
					'time' => '10:00',
				),
				'a3' => array(
					'path' => self::PATH_ATTENDED,
					'label' => 'Gợi ý nhượng quyền / upsell',
					'kb' => 'kb_a3',
					'channel' => 'oa',
					'when' => 'days_after',
					'days_after' => 3,
					'time' => '10:00',
				),
				'n1' => array(
					'path' => self::PATH_NOSHOW,
					'label' => 'Nhắn no-show / hẹn lại',
					'kb' => 'kb_n1',
					'channel' => 'oa',
					'when' => 'immediate',
				),
				'n2' => array(
					'path' => self::PATH_NOSHOW,
					'label' => 'Gọi follow no-show',
					'kb' => 'kb_n2',
					'channel' => 'call',
					'when' => 'days_after',
					'days_after' => 1,
					'time' => '14:00',
				),
				'n3' => array(
					'path' => self::PATH_NOSHOW,
					'label' => 'OA follow lần cuối',
					'kb' => 'kb_n3',
					'channel' => 'oa',
					'when' => 'days_after',
					'days_after' => 2,
					'time' => '10:00',
				),
			),
			'kb' => array(
				'kb_a1' => "Anh/chị ơi, cảm ơn anh/chị đã đến lớp Offline miễn phí. Em hy vọng buổi học hữu ích. Nếu cần tài liệu / hỗ trợ thêm, nhắn em nhé!",
				'kb_a2' => "Anh/chị muốn đi sâu hơn không ạ? Em có lộ trình PCTH phù hợp sau lớp free — em gửi khung giờ / học phí tham khảo giúp anh/chị nhé.",
				'kb_a3' => "Nếu anh/chị đang cân nhắc mở quán / nhượng quyền, team Nguyên Khoa sẵn sàng tư vấn ngắn. Anh/chị tiện giờ nào em sắp xếp ạ?",
				'kb_n1' => "Anh/chị ơi, em thấy anh/chị chưa kịp đến lớp Offline ngày {class_date}. Em hỗ trợ xếp lại lịch gần nhất được không ạ?",
				'kb_n2' => "Gọi follow no-show Offline — hỏi lý do, đề xuất hẹn lịch lại.",
				'kb_n3' => "Em gửi lại 1 khung giờ Offline gần nhất cho anh/chị. Nếu chưa tiện, cứ nhắn em để giữ chỗ lần sau nhé.",
			),
		);
	}

	/** @var array|null */
	protected static $configCache = null;

	public static function getConfig() {
		if (self::$configCache !== null) {
			return self::$configCache;
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery('SELECT config_json FROM ' . self::TABLE_SETTINGS . ' WHERE id = 1', array());
		$base = self::defaultConfig();
		if ($res && $adb->num_rows($res) > 0) {
			$raw = (string) $adb->query_result($res, 0, 'config_json');
			$decoded = json_decode($raw, true);
			if (is_array($decoded)) {
				$base = self::mergeConfig($base, $decoded);
			}
		}
		self::$configCache = $base;
		return $base;
	}

	protected static function mergeConfig(array $base, array $over) {
		foreach ($over as $k => $v) {
			if ($k === 'milestones' && is_array($v)) {
				if (!isset($base['milestones']) || !is_array($base['milestones'])) {
					$base['milestones'] = array();
				}
				foreach ($v as $mk => $mv) {
					if (is_array($mv)) {
						$base['milestones'][$mk] = isset($base['milestones'][$mk]) && is_array($base['milestones'][$mk])
							? array_merge($base['milestones'][$mk], $mv)
							: $mv;
					} else {
						$base['milestones'][$mk] = $mv;
					}
				}
			} elseif ($k === 'kb' && is_array($v)) {
				$base['kb'] = isset($base['kb']) && is_array($base['kb'])
					? array_merge($base['kb'], $v) : $v;
			} else {
				$base[$k] = $v;
			}
		}
		return $base;
	}

	/**
	 * Gọi sau check-in Opp (đã / không tham gia).
	 */
	public static function onAttendanceRecorded($leadId, $statusTag, $userId = null) {
		$leadId = (int) $leadId;
		$statusTag = strtolower(trim((string) $statusTag));
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'missing_lead');
		}
		$path = '';
		if ($statusTag === Leads_OfflineGd11Service::STATUS_DA_THAM_GIA) {
			$path = self::PATH_ATTENDED;
		} elseif ($statusTag === Leads_OfflineGd11Service::STATUS_KHONG_THAM_GIA) {
			$path = self::PATH_NOSHOW;
		} else {
			return array('success' => false, 'error' => 'status_not_for_step4');
		}

		self::installSchema();
		$adb = PearDatabase::getInstance();
		$now = date('Y-m-d H:i:s');
		$adb->pquery(
			'UPDATE bace_lead_profile
			 SET offline_step4_entered_at = ?, offline_step4_path = ?, offline_step4_sent = ?, modified_at = ?
			 WHERE leadid = ?',
			array($now, $path, '', $now, $leadId)
		);
		self::$configCache = null;

		$firstKey = ($path === self::PATH_ATTENDED) ? 'a1' : 'n1';
		$first = self::dispatchMilestone($leadId, $firstKey, $userId, true);
		return array(
			'success' => true,
			'path' => $path,
			'first' => $first,
		);
	}

	public static function processReminders($limit = 100) {
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$limit = max(1, min(300, (int) $limit));
		$res = $adb->pquery(
			'SELECT p.leadid FROM bace_lead_profile p
			 WHERE p.offline_step4_entered_at IS NOT NULL
			   AND p.offline_step4_path IN (?, ?)
			 ORDER BY p.offline_step4_entered_at ASC
			 LIMIT ' . $limit,
			array(
				self::PATH_ATTENDED,
				self::PATH_NOSHOW,
			)
		);
		$sent = 0;
		$skipped = 0;
		$errors = 0;
		if (!$res) {
			return array('sent' => 0, 'skipped' => 0, 'errors' => 0);
		}
		$now = time();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$leadId = (int) $adb->query_result($res, $i, 'leadid');
			$row = self::loadRow($leadId);
			if (!$row) {
				continue;
			}
			$path = isset($row['offline_step4_path']) ? $row['offline_step4_path'] : '';
			$cfg = self::getConfig();
			$milestones = isset($cfg['milestones']) ? $cfg['milestones'] : array();
			foreach ($milestones as $key => $meta) {
				if (!is_array($meta) || (isset($meta['path']) && $meta['path'] !== $path)) {
					continue;
				}
				if (isset($meta['when']) && $meta['when'] === 'immediate') {
					continue; // already on enter
				}
				$already = self::parseSent(isset($row['offline_step4_sent']) ? $row['offline_step4_sent'] : '');
				if (in_array($key, $already, true)) {
					$skipped++;
					continue;
				}
				$due = self::dueTsForMilestone($row, $key, $meta);
				if (!$due || $due > $now) {
					continue;
				}
				$r = self::dispatchMilestone($leadId, $key, null, false);
				if (!empty($r['success']) && empty($r['skipped'])) {
					$sent++;
					$row = self::loadRow($leadId) ?: $row;
				} elseif (!empty($r['skipped'])) {
					$skipped++;
				} else {
					$errors++;
				}
			}
		}
		return array('sent' => $sent, 'skipped' => $skipped, 'errors' => $errors);
	}

	public static function dispatchMilestone($leadId, $key, $userId = null, $force = false) {
		$leadId = (int) $leadId;
		$key = strtolower(trim((string) $key));
		$cfg = self::getConfig();
		$milestones = isset($cfg['milestones']) ? $cfg['milestones'] : array();
		if (!isset($milestones[$key]) || !is_array($milestones[$key])) {
			return array('success' => false, 'error' => 'unknown_milestone');
		}
		$meta = $milestones[$key];
		$row = self::loadRow($leadId);
		if (!$row) {
			return array('success' => false, 'error' => 'lead_not_found');
		}
		$path = isset($row['offline_step4_path']) ? $row['offline_step4_path'] : '';
		if (!empty($meta['path']) && $meta['path'] !== $path) {
			return array('success' => false, 'error' => 'path_mismatch');
		}
		$sent = self::parseSent(isset($row['offline_step4_sent']) ? $row['offline_step4_sent'] : '');
		if (!$force && in_array($key, $sent, true)) {
			return array('success' => true, 'skipped' => true, 'reason' => 'already_sent');
		}

		$channel = isset($meta['channel']) ? $meta['channel'] : 'oa';
		$kb = isset($meta['kb']) ? $meta['kb'] : '';
		$vars = self::templateVars($row);
		$text = self::renderKb($kb, $vars);
		$result = array('success' => false, 'milestone' => $key, 'channel' => $channel);

		if ($channel === 'oa') {
			if (empty($cfg['oa_enabled'])) {
				$result = array('success' => true, 'skipped' => true, 'reason' => 'oa_disabled', 'milestone' => $key);
			} else {
				$oa = self::sendTextToLead($leadId, $text, $userId);
				$result = array_merge(array('milestone' => $key, 'channel' => 'oa', 'kb' => $kb), $oa);
				if (empty($oa['success']) && !empty($oa['error']) && $oa['error'] === 'missing_zalo_user_id') {
					$cal = Leads_OfflineGd11Service::createFollowUpTask(
						$leadId,
						$path === self::PATH_NOSHOW
							? Leads_OfflineGd11Service::STATUS_KHONG_THAM_GIA
							: Leads_OfflineGd11Service::STATUS_DA_THAM_GIA,
						array('due_at' => date('Y-m-d H:i:s', time() + 3600)),
						$userId
					);
					$result['calendar_fallback'] = $cal;
					$result['success'] = true;
					$result['note'] = 'Thiếu zalo_user_id — đã tạo Calendar thay OA';
				}
			}
		} elseif ($channel === 'call' || $channel === 'calendar') {
			$due = self::dueTsForMilestone($row, $key, $meta);
			$when = $due ? date('Y-m-d H:i:s', $due) : date('Y-m-d H:i:s', time() + 3600);
			$cal = Leads_OfflineGd11Service::createFollowUpTask(
				$leadId,
				$path === self::PATH_NOSHOW
					? Leads_OfflineGd11Service::STATUS_KHONG_THAM_GIA
					: Leads_OfflineGd11Service::STATUS_DA_THAM_GIA,
				array('due_at' => $when),
				$userId
			);
			$result = array(
				'success' => !empty($cal['success']),
				'milestone' => $key,
				'channel' => $channel,
				'calendar' => $cal,
				'text' => $text,
			);
		}

		if (!empty($result['success']) || !empty($result['skipped'])) {
			self::markSent($leadId, $key);
		}
		return $result;
	}

	protected static function sendTextToLead($leadId, $text, $userId = null) {
		$row = self::loadRow($leadId);
		if (!$row) {
			return array('success' => false, 'error' => 'lead_not_found');
		}
		$oaUserId = isset($row['zalo_user_id']) ? trim((string) $row['zalo_user_id']) : '';
		if ($oaUserId === '') {
			return array('success' => false, 'error' => 'missing_zalo_user_id', 'text' => $text);
		}
		try {
			require_once 'modules/Vtiger/helpers/NkApiConnection.php';
			require_once 'modules/Vtiger/helpers/NkApi/ZaloOaAdapter.php';
			$adapter = new NkApi_ZaloOa_Adapter();
			$r = $adapter->sendTextMessage($oaUserId, $text, $userId ? (int) $userId : 0);
			$r['text'] = $text;
			return $r;
		} catch (Exception $e) {
			return array('success' => false, 'error' => $e->getMessage(), 'text' => $text);
		} catch (Throwable $e) {
			return array('success' => false, 'error' => $e->getMessage(), 'text' => $text);
		}
	}

	protected static function dueTsForMilestone(array $row, $key, array $meta) {
		$when = isset($meta['when']) ? $meta['when'] : '';
		$entered = !empty($row['offline_step4_entered_at'])
			? strtotime($row['offline_step4_entered_at']) : time();
		if ($when === 'immediate') {
			return $entered;
		}
		if ($when === 'days_after') {
			$days = isset($meta['days_after']) ? (int) $meta['days_after'] : 1;
			$time = isset($meta['time']) ? $meta['time'] : '10:00';
			$base = strtotime(date('Y-m-d', $entered) . ' +' . max(0, $days) . ' day');
			return strtotime(date('Y-m-d', $base) . ' ' . $time . ':00');
		}
		return 0;
	}

	protected static function templateVars(array $row) {
		return array(
			'class_date' => (!empty($row['offline_class_date']) && $row['offline_class_date'] !== '0000-00-00')
				? (string) $row['offline_class_date'] : '',
			'class_time' => !empty($row['offline_class_time']) ? (string) $row['offline_class_time'] : '09:00',
			'class_place' => !empty($row['offline_class_place']) ? (string) $row['offline_class_place'] : '',
		);
	}

	protected static function renderKb($kbKey, array $vars) {
		$cfg = self::getConfig();
		$map = isset($cfg['kb']) && is_array($cfg['kb']) ? $cfg['kb'] : array();
		$text = isset($map[$kbKey]) ? (string) $map[$kbKey] : $kbKey;
		foreach ($vars as $k => $v) {
			$text = str_replace('{' . $k . '}', (string) $v, $text);
		}
		return $text;
	}

	protected static function parseSent($raw) {
		$raw = trim((string) $raw);
		if ($raw === '') {
			return array();
		}
		$parts = preg_split('/\s*,\s*/', $raw);
		$out = array();
		foreach ($parts as $p) {
			$p = strtolower(trim($p));
			if ($p !== '' && !in_array($p, $out, true)) {
				$out[] = $p;
			}
		}
		return $out;
	}

	protected static function markSent($leadId, $key) {
		$leadId = (int) $leadId;
		$key = strtolower(trim((string) $key));
		if ($leadId <= 0 || $key === '') {
			return;
		}
		$row = self::loadRow($leadId);
		$sent = self::parseSent($row ? $row['offline_step4_sent'] : '');
		if (!in_array($key, $sent, true)) {
			$sent[] = $key;
		}
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'UPDATE bace_lead_profile SET offline_step4_sent = ?, modified_at = ? WHERE leadid = ?',
			array(implode(',', $sent), date('Y-m-d H:i:s'), $leadId)
		);
	}

	protected static function loadRow($leadId) {
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return null;
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT leadid, offline_status, offline_class_date, offline_class_time, offline_class_place,
				zalo_user_id, offline_step4_entered_at, offline_step4_path, offline_step4_sent
			 FROM bace_lead_profile WHERE leadid = ?',
			array($leadId)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return null;
		}
		return array(
			'leadid' => (int) $adb->query_result($res, 0, 'leadid'),
			'offline_status' => (string) $adb->query_result($res, 0, 'offline_status'),
			'offline_class_date' => (string) $adb->query_result($res, 0, 'offline_class_date'),
			'offline_class_time' => (string) $adb->query_result($res, 0, 'offline_class_time'),
			'offline_class_place' => (string) $adb->query_result($res, 0, 'offline_class_place'),
			'zalo_user_id' => (string) $adb->query_result($res, 0, 'zalo_user_id'),
			'offline_step4_entered_at' => (string) $adb->query_result($res, 0, 'offline_step4_entered_at'),
			'offline_step4_path' => (string) $adb->query_result($res, 0, 'offline_step4_path'),
			'offline_step4_sent' => (string) $adb->query_result($res, 0, 'offline_step4_sent'),
		);
	}
}
