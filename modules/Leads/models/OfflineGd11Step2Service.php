<?php
/*+***********************************************************************************
 * Giai đoạn 1.1 — Bước 2: chăm sóc trước lớp.
 * Mốc nhắc theo doc (chỉnh được qua bace_gd11_step2_settings).
 * OA gửi KB 07a/07b/08/10; T4 = Calendar gọi; T5 = Calendar nhắc.
 *************************************************************************************/

require_once 'modules/Leads/models/OfflineGd11Service.php';

class Leads_OfflineGd11Step2Service {

	const TABLE_SETTINGS = 'bace_gd11_step2_settings';

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
			'offline_class_time' => "VARCHAR(8) DEFAULT '09:00'",
			'offline_class_place' => "VARCHAR(255) DEFAULT NULL",
			'offline_step2_entered_at' => "DATETIME DEFAULT NULL",
			'offline_step2_sent' => "VARCHAR(64) DEFAULT ''",
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

	/**
	 * Config mặc định theo GD11 doc — chỉnh được qua saveConfig().
	 */
	public static function defaultConfig() {
		return array(
			'oa_enabled' => true,
			'default_class_time' => '09:00',
			'default_class_place' => 'Cơ sở Nguyên Khoa (cập nhật địa chỉ cụ thể)',
			't1_cutoff_hour' => 16.5,
			'milestones' => array(
				't1' => array(
					'label' => 'Gửi thông tin lớp học',
					'kb' => 'kb07a',
					'channel' => 'oa',
					'when' => 'immediate',
				),
				't2' => array(
					'label' => 'Gửi cảm nhận lớp học',
					'kb' => 'kb07b',
					'channel' => 'oa',
					'when' => 'dow_before',
					'dow' => 6,
					'time' => '15:00',
				),
				't3' => array(
					'label' => 'Nhắn xác nhận tham gia',
					'kb' => 'kb08',
					'channel' => 'oa',
					'when' => 'dow_before',
					'dow' => 1,
					'time' => '09:00',
				),
				't4' => array(
					'label' => 'Gọi xác nhận tham gia',
					'kb' => 'kb09',
					'channel' => 'call',
					'when' => 'dow_before',
					'dow' => 1,
					'time' => '14:00',
				),
				't5' => array(
					'label' => 'Tạo nhắc hẹn sáng lớp',
					'kb' => 'kb_t5',
					'channel' => 'calendar',
					'when' => 'dow_before',
					'dow' => 1,
					'time' => '16:00',
					'remind_class_time' => '07:00',
				),
				't6' => array(
					'label' => 'Nhắc lịch cuối (trước giờ học)',
					'kb' => 'kb10',
					'channel' => 'oa',
					'when' => 'hours_before',
					'hours_before' => 2,
					'required' => true,
				),
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
				self::$configCache = self::mergeConfig($base, $decoded);
				return self::$configCache;
			}
		}
		self::$configCache = $base;
		return self::$configCache;
	}

	public static function saveConfig(array $patch) {
		self::installSchema();
		$merged = self::mergeConfig(self::getConfig(), $patch);
		$adb = PearDatabase::getInstance();
		$json = json_encode($merged, JSON_UNESCAPED_UNICODE);
		$exists = $adb->pquery('SELECT id FROM ' . self::TABLE_SETTINGS . ' WHERE id = 1', array());
		if ($exists && $adb->num_rows($exists) > 0) {
			$adb->pquery(
				'UPDATE ' . self::TABLE_SETTINGS . ' SET config_json = ?, modified_at = ? WHERE id = 1',
				array($json, date('Y-m-d H:i:s'))
			);
		} else {
			$adb->pquery(
				'INSERT INTO ' . self::TABLE_SETTINGS . ' (id, config_json, modified_at) VALUES (1, ?, ?)',
				array($json, date('Y-m-d H:i:s'))
			);
		}
		self::$configCache = $merged;
		return $merged;
	}

	protected static function mergeConfig(array $base, array $patch) {
		foreach ($patch as $k => $v) {
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
			} else {
				$base[$k] = $v;
			}
		}
		return $base;
	}

	public static function kbTemplates() {
		return array(
			'kb07a' => "Anh/chị ơi, em gửi thông tin lớp Offline miễn phí:\n📅 Ngày: {class_date}\n⏰ Giờ: {class_time}\n📍 Địa điểm: {class_place}\nAnh/chị giữ lịch giúp em nhé. Có thắc mắc cứ nhắn em.",
			'kb07b' => "Anh/chị xem thêm cảm nhận học viên khoá trước nhé — lớp Offline rất thực tế và dễ áp dụng. Em nhắc lại lịch {class_date} {class_time} tại {class_place}.",
			'kb08' => "Em gửi thư mời giữ chỗ lớp Offline ngày {class_date} lúc {class_time}. Anh/chị phản hồi giúp em «Mình sẽ đến» để em giữ chỗ nhé.",
			'kb09' => "Gọi xác nhận tham gia lớp Offline {class_date} {class_time} — khách chưa phản hồi tin nhắn.",
			'kb10' => "Nhắc anh/chị: còn khoảng 2 tiếng nữa lớp Offline bắt đầu ({class_date} {class_time}) tại {class_place}. Em chờ anh/chị tại lớp nhé!",
			'kb11' => "Em hiểu anh/chị muốn dời lịch. Em sẽ gửi khung lớp gần nhất — anh/chị chọn giúp em 1 buổi phù hợp nhé.",
			'kb12' => "Em ghi nhận anh/chị chưa sắp xếp được tham gia lớp Offline lần này. Nếu đổi ý hoặc muốn chương trình khác, cứ nhắn em hỗ trợ.",
			'kb_t5' => "Nhắc nội bộ: tạo nhắc hẹn Salework/Calendar cho 7h sáng ngày học {class_date}.",
		);
	}

	/**
	 * Vào / reset Bước 2 khi chốt lịch (tag ⑥).
	 */
	public static function onScheduleConfirmed($leadId, array $payload = array(), $userId = null) {
		$leadId = (int) $leadId;
		if ($leadId <= 0) {
			return array('success' => false, 'error' => 'invalid_lead');
		}
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$cfg = self::getConfig();
		$classDate = isset($payload['class_date']) ? trim((string) $payload['class_date']) : '';
		$classTime = isset($payload['class_time']) ? trim((string) $payload['class_time']) : '';
		$classPlace = isset($payload['class_place']) ? trim((string) $payload['class_place']) : '';
		if ($classTime === '') {
			$classTime = isset($cfg['default_class_time']) ? $cfg['default_class_time'] : '09:00';
		}
		if ($classPlace === '' && !empty($cfg['default_class_place'])) {
			$classPlace = (string) $cfg['default_class_place'];
		}
		if ($classDate !== '') {
			Leads_OfflineGd11Service::setClassDatePublic($leadId, $classDate);
		}
		$now = date('Y-m-d H:i:s');
		$adb->pquery(
			'UPDATE bace_lead_profile SET
				offline_preclass_confirm = 0,
				offline_class_time = ?,
				offline_class_place = ?,
				offline_step2_entered_at = ?,
				offline_step2_sent = ?,
				modified_at = ?
			 WHERE leadid = ?',
			array($classTime, $classPlace !== '' ? $classPlace : null, $now, '', $now, $leadId)
		);

		// T1 ngay (hoặc sáng hôm sau nếu quá cutoff).
		$t1 = self::dispatchMilestone($leadId, 't1', $userId, true);
		return array(
			'success' => true,
			'entered_at' => $now,
			't1' => $t1,
			'plan' => self::milestonePlanForLead($leadId),
		);
	}

	/**
	 * @param bool $detailed true = kèm plan + config (getLead / panel)
	 */
	public static function profileExtras(array $row, $detailed = false) {
		$sent = self::parseSent(isset($row['offline_step2_sent']) ? $row['offline_step2_sent'] : '');
		$out = array(
			'offline_class_time' => isset($row['offline_class_time']) && $row['offline_class_time']
				? (string) $row['offline_class_time'] : '09:00',
			'offline_class_place' => isset($row['offline_class_place']) ? (string) $row['offline_class_place'] : '',
			'offline_step2_entered_at' => (!empty($row['offline_step2_entered_at']) && $row['offline_step2_entered_at'] !== '0000-00-00 00:00:00')
				? (string) $row['offline_step2_entered_at'] : '',
			'offline_step2_sent' => $sent,
			'zalo_user_id' => isset($row['zalo_user_id']) ? (string) $row['zalo_user_id'] : '',
		);
		if ($detailed) {
			$out['offline_step2_plan'] = self::planFromRow($row);
			$out['offline_step2_config'] = self::getConfig();
			$out['offline_kb_step2'] = self::kbSnippetsStep2();
		}
		return $out;
	}

	public static function kbSnippetsStep2() {
		$out = array();
		foreach (self::kbTemplates() as $id => $text) {
			if (strpos($id, 'kb0') === 0 || $id === 'kb10' || $id === 'kb11' || $id === 'kb12') {
				$out[] = array('id' => $id, 'title' => strtoupper($id), 'text' => $text);
			}
		}
		return $out;
	}

	/**
	 * API actions Bước 2.
	 */
	public static function applyAction($leadId, $action, array $payload = array(), $userId = null) {
		$leadId = (int) $leadId;
		$action = strtolower(trim((string) $action));
		self::installSchema();
		require_once 'modules/Leads/models/ModernService.php';

		if ($action === 'set_zalo_user') {
			$uid = isset($payload['zalo_user_id']) ? trim((string) $payload['zalo_user_id']) : '';
			$adb = PearDatabase::getInstance();
			$adb->pquery(
				'UPDATE bace_lead_profile SET zalo_user_id = ?, modified_at = ? WHERE leadid = ?',
				array($uid !== '' ? $uid : null, date('Y-m-d H:i:s'), $leadId)
			);
			return self::okLead($leadId, $userId, array('zalo_user_id' => $uid));
		}

		if ($action === 'preclass_confirm') {
			self::setPreclassConfirm($leadId, 1);
			Leads_OfflineGd11Service::setNextActionHint($leadId, Leads_OfflineGd11Service::STATUS_DA_XN_LICH);
			return self::okLead($leadId, $userId, array('offline_preclass_confirm' => 1));
		}
		if ($action === 'preclass_unconfirm') {
			self::setPreclassConfirm($leadId, 0);
			return self::okLead($leadId, $userId, array('offline_preclass_confirm' => 0));
		}

		if ($action === 'hen_lich_lai') {
			$r = Leads_OfflineGd11Service::applyAction($leadId, 'hen_lich_lai', $payload, $userId);
			if (!empty($r['success'])) {
				self::sendKbToLead($leadId, 'kb11', $userId);
			}
			return $r;
		}

		if ($action === 'chot_lich_moi') {
			$classDate = isset($payload['class_date']) ? trim((string) $payload['class_date']) : '';
			if ($classDate === '') {
				return array('success' => false, 'error' => 'Thiếu ngày học mới');
			}
			$bump = Leads_OfflineGd11Service::bumpCounter($leadId, 'r3');
			if (!empty($bump['stopped'])) {
				Leads_OfflineGd11Service::applyStatus($leadId, Leads_OfflineGd11Service::STATUS_NGUNG_CSKH, $userId);
				Leads_OfflineGd11Service::setNextActionHint($leadId, Leads_OfflineGd11Service::STATUS_NGUNG_CSKH);
				return self::okLead($leadId, $userId, array('drop' => 'R3', 'status' => Leads_OfflineGd11Service::STATUS_NGUNG_CSKH));
			}
			Leads_OfflineGd11Service::applyStatus($leadId, Leads_OfflineGd11Service::STATUS_DA_XN_LICH, $userId);
			$step2 = self::onScheduleConfirmed($leadId, $payload, $userId);
			Leads_OfflineGd11Service::setNextActionHint($leadId, Leads_OfflineGd11Service::STATUS_DA_XN_LICH, $classDate);
			$cal = Leads_OfflineGd11Service::createFollowUpTask(
				$leadId,
				Leads_OfflineGd11Service::STATUS_DA_XN_LICH,
				$payload,
				$userId
			);
			return self::okLead($leadId, $userId, array('step2' => $step2, 'calendar' => $cal, 'r3' => $bump['count']));
		}

		if ($action === 'tu_choi_tham_gia') {
			self::sendKbToLead($leadId, 'kb12', $userId);
			Leads_OfflineGd11Service::applyStatus($leadId, Leads_OfflineGd11Service::STATUS_NGUNG_CSKH, $userId);
			Leads_OfflineGd11Service::setNextActionHint($leadId, Leads_OfflineGd11Service::STATUS_NGUNG_CSKH);
			return self::okLead($leadId, $userId, array('status' => Leads_OfflineGd11Service::STATUS_NGUNG_CSKH));
		}

		if ($action === 'send_milestone') {
			$key = isset($payload['milestone']) ? strtolower(trim((string) $payload['milestone'])) : '';
			if ($key === '') {
				return array('success' => false, 'error' => 'Thiếu milestone (t1–t6)');
			}
			$sent = self::dispatchMilestone($leadId, $key, $userId, true);
			return self::okLead($leadId, $userId, array('milestone' => $sent));
		}

		if ($action === 'enter_step2') {
			$step2 = self::onScheduleConfirmed($leadId, $payload, $userId);
			return self::okLead($leadId, $userId, array('step2' => $step2));
		}

		if ($action === 'save_class_meta') {
			$adb = PearDatabase::getInstance();
			$time = isset($payload['class_time']) ? trim((string) $payload['class_time']) : null;
			$place = isset($payload['class_place']) ? trim((string) $payload['class_place']) : null;
			$date = isset($payload['class_date']) ? trim((string) $payload['class_date']) : '';
			if ($date !== '') {
				Leads_OfflineGd11Service::setClassDatePublic($leadId, $date);
			}
			$sets = array();
			$params = array();
			if ($time !== null && $time !== '') {
				$sets[] = 'offline_class_time = ?';
				$params[] = $time;
			}
			if ($place !== null) {
				$sets[] = 'offline_class_place = ?';
				$params[] = $place !== '' ? $place : null;
			}
			if ($sets) {
				$sets[] = 'modified_at = ?';
				$params[] = date('Y-m-d H:i:s');
				$params[] = $leadId;
				$adb->pquery(
					'UPDATE bace_lead_profile SET ' . implode(', ', $sets) . ' WHERE leadid = ?',
					$params
				);
			}
			return self::okLead($leadId, $userId, array());
		}

		return array('success' => false, 'error' => 'Action Bước 2 không hợp lệ');
	}

	public static function setPreclassConfirm($leadId, $yes) {
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'UPDATE bace_lead_profile SET offline_preclass_confirm = ?, modified_at = ? WHERE leadid = ?',
			array($yes ? 1 : 0, date('Y-m-d H:i:s'), (int) $leadId)
		);
	}

	/**
	 * Cron: gửi mốc đến hạn cho lead tag Đã XN lịch.
	 */
	public static function processReminders($limit = 80) {
		self::installSchema();
		$adb = PearDatabase::getInstance();
		$limit = max(1, min(200, (int) $limit));
		$sql = "SELECT p.leadid, p.offline_class_date, p.offline_class_time, p.offline_class_place,
				p.offline_step2_sent, p.offline_step2_entered_at, p.zalo_user_id, p.offline_preclass_confirm
			FROM bace_lead_profile p
			INNER JOIN vtiger_crmentity ce ON ce.crmid = p.leadid AND ce.deleted = 0
			WHERE p.offline_status = ?
			  AND p.offline_class_date IS NOT NULL AND p.offline_class_date <> '0000-00-00'
			ORDER BY p.offline_class_date ASC
			LIMIT {$limit}";
		$res = $adb->pquery($sql, array(Leads_OfflineGd11Service::STATUS_DA_XN_LICH));
		$sent = 0;
		$skipped = 0;
		$errors = 0;
		if (!$res) {
			return array('sent' => 0, 'skipped' => 0, 'errors' => 0);
		}
		$now = time();
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$leadId = (int) $adb->query_result($res, $i, 'leadid');
			$row = array(
				'offline_class_date' => $adb->query_result($res, $i, 'offline_class_date'),
				'offline_class_time' => $adb->query_result($res, $i, 'offline_class_time'),
				'offline_class_place' => $adb->query_result($res, $i, 'offline_class_place'),
				'offline_step2_sent' => $adb->query_result($res, $i, 'offline_step2_sent'),
				'offline_step2_entered_at' => $adb->query_result($res, $i, 'offline_step2_entered_at'),
				'zalo_user_id' => $adb->query_result($res, $i, 'zalo_user_id'),
			);
			$plan = self::planFromRow($row);
			$already = self::parseSent($row['offline_step2_sent']);
			$enteredTs = !empty($row['offline_step2_entered_at']) ? strtotime($row['offline_step2_entered_at']) : 0;
			foreach ($plan as $key => $item) {
				if (in_array($key, $already, true)) {
					continue;
				}
				$due = isset($item['due_ts']) ? (int) $item['due_ts'] : 0;
				if ($due <= 0) {
					continue;
				}
				$required = !empty($item['required']);
				// Bỏ mốc đã trôi qua lúc mới vào Bước 2 (trừ t6 bắt buộc).
				if ($enteredTs && $due < $enteredTs && !$required) {
					self::markSent($leadId, $key);
					$skipped++;
					continue;
				}
				if ($now < $due) {
					continue;
				}
				// Cửa sổ gửi: không gửi mốc thường nếu đã quá hạn > 12h (trừ required).
				if (!$required && ($now - $due) > 12 * 3600) {
					self::markSent($leadId, $key);
					$skipped++;
					continue;
				}
				$r = self::dispatchMilestone($leadId, $key, null, false);
				if (!empty($r['success'])) {
					$sent++;
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
		if (!isset($milestones[$key])) {
			return array('success' => false, 'error' => 'unknown_milestone');
		}
		$meta = $milestones[$key];
		$row = self::loadRow($leadId);
		if (!$row) {
			return array('success' => false, 'error' => 'lead_not_found');
		}
		$sent = self::parseSent($row['offline_step2_sent']);
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
				$oa = self::sendKbToLead($leadId, $kb, $userId, $vars);
				$result = array_merge(array('milestone' => $key, 'channel' => 'oa'), $oa);
				// Fallback Calendar nếu chưa có OA id.
				if (empty($oa['success']) && !empty($oa['error']) && $oa['error'] === 'missing_zalo_user_id') {
					$cal = Leads_OfflineGd11Service::createFollowUpTask(
						$leadId,
						Leads_OfflineGd11Service::STATUS_DA_XN_LICH,
						array(
							'due_at' => date('Y-m-d H:i:s', time() + 3600),
							'class_date' => $vars['class_date'],
						),
						$userId
					);
					$result['calendar_fallback'] = $cal;
					$result['success'] = true;
					$result['note'] = 'Thiếu zalo_user_id — đã tạo Calendar thay OA';
				}
			}
		} elseif ($channel === 'call' || $channel === 'calendar') {
			$due = self::dueTsForMilestone($row, $key, $meta);
			if ($key === 't5' && !empty($meta['remind_class_time']) && !empty($row['offline_class_date'])) {
				$due = strtotime($row['offline_class_date'] . ' ' . $meta['remind_class_time'] . ':00');
			}
			$when = $due ? date('Y-m-d H:i:s', $due) : date('Y-m-d H:i:s', time() + 3600);
			$cal = Leads_OfflineGd11Service::createFollowUpTask(
				$leadId,
				Leads_OfflineGd11Service::STATUS_DA_XN_LICH,
				array('due_at' => $when, 'class_date' => $vars['class_date']),
				$userId
			);
			// Đổi subject nhẹ bằng description đã có; success theo calendar.
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

	public static function sendKbToLead($leadId, $kbKey, $userId = null, array $vars = null) {
		$row = self::loadRow($leadId);
		if (!$row) {
			return array('success' => false, 'error' => 'lead_not_found');
		}
		if ($vars === null) {
			$vars = self::templateVars($row);
		}
		$text = self::renderKb($kbKey, $vars);
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
			$r['kb'] = $kbKey;
			return $r;
		} catch (Exception $e) {
			return array('success' => false, 'error' => $e->getMessage(), 'text' => $text);
		} catch (Throwable $e) {
			return array('success' => false, 'error' => $e->getMessage(), 'text' => $text);
		}
	}

	public static function milestonePlanForLead($leadId) {
		$row = self::loadRow($leadId);
		return $row ? self::planFromRow($row) : array();
	}

	protected static function planFromRow(array $row) {
		$cfg = self::getConfig();
		$plan = array();
		$milestones = isset($cfg['milestones']) ? $cfg['milestones'] : array();
		foreach ($milestones as $key => $meta) {
			$due = self::dueTsForMilestone($row, $key, $meta);
			$plan[$key] = array(
				'label' => isset($meta['label']) ? $meta['label'] : $key,
				'kb' => isset($meta['kb']) ? $meta['kb'] : '',
				'channel' => isset($meta['channel']) ? $meta['channel'] : 'oa',
				'due_ts' => $due,
				'due_at' => $due ? date('Y-m-d H:i:s', $due) : '',
				'required' => !empty($meta['required']),
				'sent' => in_array($key, self::parseSent(isset($row['offline_step2_sent']) ? $row['offline_step2_sent'] : ''), true),
			);
		}
		return $plan;
	}

	protected static function dueTsForMilestone(array $row, $key, array $meta) {
		$classDate = isset($row['offline_class_date']) ? trim((string) $row['offline_class_date']) : '';
		$classTime = isset($row['offline_class_time']) && $row['offline_class_time']
			? trim((string) $row['offline_class_time']) : '09:00';
		$when = isset($meta['when']) ? $meta['when'] : '';
		$cfg = self::getConfig();

		if ($when === 'immediate' || $key === 't1') {
			$entered = !empty($row['offline_step2_entered_at']) ? strtotime($row['offline_step2_entered_at']) : time();
			$cutoff = isset($cfg['t1_cutoff_hour']) ? (float) $cfg['t1_cutoff_hour'] : 16.5;
			$hour = (int) date('G', $entered) + ((int) date('i', $entered)) / 60;
			if ($hour >= $cutoff) {
				return strtotime(date('Y-m-d', $entered) . ' +1 day 08:00:00');
			}
			return $entered;
		}

		if ($classDate === '' || $classDate === '0000-00-00') {
			return 0;
		}
		$classTs = strtotime($classDate . ' ' . $classTime . ':00');
		if (!$classTs) {
			return 0;
		}

		if ($when === 'hours_before') {
			$h = isset($meta['hours_before']) ? (float) $meta['hours_before'] : 2;
			return $classTs - (int) round($h * 3600);
		}

		if ($when === 'dow_before') {
			$targetDow = isset($meta['dow']) ? (int) $meta['dow'] : 1; // 1=Mon … 7=Sun
			$time = isset($meta['time']) ? $meta['time'] : '09:00';
			// Lùi từ class_date đến đúng dow trước (không cùng ngày lớp nếu dow trùng).
			$d = strtotime($classDate . ' 12:00:00');
			for ($i = 1; $i <= 7; $i++) {
				$d = strtotime('-1 day', $d);
				if ((int) date('N', $d) === $targetDow) {
					return strtotime(date('Y-m-d', $d) . ' ' . $time . ':00');
				}
			}
		}
		return 0;
	}

	protected static function templateVars(array $row) {
		$cfg = self::getConfig();
		$place = isset($row['offline_class_place']) ? trim((string) $row['offline_class_place']) : '';
		if ($place === '' && !empty($cfg['default_class_place'])) {
			$place = (string) $cfg['default_class_place'];
		}
		$date = isset($row['offline_class_date']) ? (string) $row['offline_class_date'] : '';
		$time = isset($row['offline_class_time']) && $row['offline_class_time']
			? (string) $row['offline_class_time'] : (isset($cfg['default_class_time']) ? $cfg['default_class_time'] : '09:00');
		return array(
			'class_date' => $date,
			'class_time' => $time,
			'class_place' => $place,
		);
	}

	protected static function renderKb($kbKey, array $vars) {
		$all = self::kbTemplates();
		$text = isset($all[$kbKey]) ? $all[$kbKey] : (string) $kbKey;
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
		$parts = preg_split('/[\s,;]+/', $raw);
		$out = array();
		foreach ($parts as $p) {
			$p = strtolower(trim($p));
			if ($p !== '') {
				$out[] = $p;
			}
		}
		return array_values(array_unique($out));
	}

	protected static function markSent($leadId, $key) {
		$row = self::loadRow($leadId);
		$sent = $row ? self::parseSent($row['offline_step2_sent']) : array();
		if (!in_array($key, $sent, true)) {
			$sent[] = $key;
		}
		$adb = PearDatabase::getInstance();
		$adb->pquery(
			'UPDATE bace_lead_profile SET offline_step2_sent = ?, modified_at = ? WHERE leadid = ?',
			array(implode(',', $sent), date('Y-m-d H:i:s'), (int) $leadId)
		);
	}

	protected static function loadRow($leadId) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT offline_status, offline_class_date, offline_class_time, offline_class_place,
				offline_step2_entered_at, offline_step2_sent, offline_preclass_confirm, zalo_user_id
			 FROM bace_lead_profile WHERE leadid = ?',
			array((int) $leadId)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return null;
		}
		return array(
			'offline_status' => $adb->query_result($res, 0, 'offline_status'),
			'offline_class_date' => $adb->query_result($res, 0, 'offline_class_date'),
			'offline_class_time' => $adb->query_result($res, 0, 'offline_class_time'),
			'offline_class_place' => $adb->query_result($res, 0, 'offline_class_place'),
			'offline_step2_entered_at' => $adb->query_result($res, 0, 'offline_step2_entered_at'),
			'offline_step2_sent' => $adb->query_result($res, 0, 'offline_step2_sent'),
			'offline_preclass_confirm' => $adb->query_result($res, 0, 'offline_preclass_confirm'),
			'zalo_user_id' => $adb->query_result($res, 0, 'zalo_user_id'),
		);
	}

	protected static function okLead($leadId, $userId, array $extra) {
		$lead = Leads_ModernService::getLead((string) $leadId, $userId);
		return array_merge(array('success' => true, 'lead' => $lead), $extra);
	}
}
