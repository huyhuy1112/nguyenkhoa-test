<?php
/*+***********************************************************************************
 * Opportunity interaction timeline — Lead Last Touch + Calendar + stage history.
 * Lightweight SQL only (no heavy Record_Model loads on getLog).
 *************************************************************************************/

require_once 'modules/Leads/models/ConvertService.php';
require_once 'modules/Leads/models/LastTouchCallService.php';

class Potentials_InteractionLogService {

	/**
	 * @param int $potentialId
	 * @return array
	 */
	public static function getLog($potentialId) {
		$potentialId = (int) $potentialId;
		$out = array(
			'phone' => '',
			'contact_id' => 0,
			'contact_name' => '',
			'lead_id' => 0,
			'items' => array(),
		);
		if ($potentialId <= 0) {
			return $out;
		}

		try {
			$adb = PearDatabase::getInstance();
			$contactId = 0;
			$phone = '';
			$contactName = '';

			$res = $adb->pquery(
				'SELECT p.contact_id,
					cd.firstname, cd.lastname, cd.phone AS c_phone, cd.mobile AS c_mobile
				 FROM vtiger_potential p
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = p.potentialid AND ce.deleted = 0
				 LEFT JOIN vtiger_contactdetails cd ON cd.contactid = p.contact_id
				 WHERE p.potentialid = ?
				 LIMIT 1',
				array($potentialId)
			);
			if (!$res || !$adb->num_rows($res)) {
				return $out;
			}
			$contactId = (int) $adb->query_result($res, 0, 'contact_id');
			$contactName = trim(
				self::decode($adb->query_result($res, 0, 'firstname')) . ' ' .
				self::decode($adb->query_result($res, 0, 'lastname'))
			);
			$phone = self::pickPhone(
				$adb->query_result($res, 0, 'c_phone'),
				$adb->query_result($res, 0, 'c_mobile')
			);

			$leadId = 0;
			try {
				$leadId = (int) Leads_ConvertService::getLinkedLeadIdByPotential($potentialId);
			} catch (Exception $e) {
				$leadId = 0;
			}
			if ($leadId > 0 && $phone === '') {
				$phone = self::leadPhone($leadId);
			}

			$out['phone'] = $phone;
			$out['contact_id'] = $contactId;
			$out['contact_name'] = $contactName;
			$out['lead_id'] = $leadId;

			$crmIds = array($potentialId);
			$stageById = array($potentialId => 'opportunity');
			if ($leadId > 0) {
				$crmIds[] = $leadId;
				$stageById[$leadId] = 'lead';
			}
			if ($contactId > 0) {
				$crmIds[] = $contactId;
				$stageById[$contactId] = 'contact';
			}

			$items = array();
			if ($leadId > 0) {
				try {
					$items = array_merge($items, self::leadLastTouchItems($leadId));
				} catch (Exception $e) {
					// ignore last-touch failures
				}
			}
			try {
				$items = array_merge($items, self::calendarItemsForCrmIds($crmIds, $stageById));
			} catch (Exception $e) {
				// ignore calendar failures
			}
			try {
				$items = array_merge($items, self::stageHistoryItems($potentialId));
			} catch (Exception $e) {
				// ignore stage history failures
			}

			$out['items'] = self::dedupeAndSort($items);
			return $out;
		} catch (Exception $e) {
			return $out;
		}
	}

	/**
	 * @param int $potentialId
	 * @param string $resultLabel
	 * @param string $note
	 * @return array
	 */
	public static function logCall($potentialId, $resultLabel, $note = '') {
		$potentialId = (int) $potentialId;
		if ($potentialId <= 0) {
			throw new Exception('Opportunity id is required.');
		}
		$resultLabel = trim((string) $resultLabel);
		if ($resultLabel === '') {
			$resultLabel = 'Gọi';
		}
		$note = trim((string) $note);

		$meta = self::getContactMeta($potentialId);
		$subject = 'Gọi KH — Opp #' . $potentialId;
		if ($meta['contact_name'] !== '') {
			$subject .= ' · ' . $meta['contact_name'];
		}
		$subject .= ' · ' . $resultLabel;

		$activityId = self::createCallActivity(
			$potentialId,
			$meta['contact_id'],
			$subject,
			$note,
			$resultLabel
		);
		return array(
			'success' => true,
			'activity_id' => $activityId,
			'log' => self::getLog($potentialId),
		);
	}

	protected static function getContactMeta($potentialId) {
		$adb = PearDatabase::getInstance();
		$meta = array('contact_id' => 0, 'contact_name' => '', 'phone' => '');
		$res = $adb->pquery(
			'SELECT p.contact_id, cd.firstname, cd.lastname, cd.phone, cd.mobile
			 FROM vtiger_potential p
			 LEFT JOIN vtiger_contactdetails cd ON cd.contactid = p.contact_id
			 WHERE p.potentialid = ? LIMIT 1',
			array((int) $potentialId)
		);
		if ($res && $adb->num_rows($res)) {
			$meta['contact_id'] = (int) $adb->query_result($res, 0, 'contact_id');
			$meta['contact_name'] = trim(
				self::decode($adb->query_result($res, 0, 'firstname')) . ' ' .
				self::decode($adb->query_result($res, 0, 'lastname'))
			);
			$meta['phone'] = self::pickPhone(
				$adb->query_result($res, 0, 'phone'),
				$adb->query_result($res, 0, 'mobile')
			);
		}
		return $meta;
	}

	protected static function pickPhone($a, $b) {
		$a = trim(self::decode($a));
		$b = trim(self::decode($b));
		if ($a !== '' && $a !== '0') {
			return $a;
		}
		if ($b !== '' && $b !== '0') {
			return $b;
		}
		return '';
	}

	protected static function leadPhone($leadId) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT phone, mobile FROM vtiger_leadaddress WHERE leadaddressid = ?',
			array((int) $leadId)
		);
		if ($res && $adb->num_rows($res) > 0) {
			return self::pickPhone(
				$adb->query_result($res, 0, 'phone'),
				$adb->query_result($res, 0, 'mobile')
			);
		}
		return '';
	}

	protected static function leadLastTouchItems($leadId) {
		$items = array();
		try {
			$calls = Leads_LastTouchCallService::getCalls($leadId);
		} catch (Exception $e) {
			return $items;
		}
		foreach ($calls as $c) {
			$ts = !empty($c['called_at']) ? strtotime($c['called_at']) : 0;
			$result = self::decode(isset($c['result']) ? $c['result'] : '');
			$note = self::decode(isset($c['note']) ? $c['note'] : '');
			$items[] = array(
				'id' => 'lt-' . (int) $c['id'],
				'type' => 'call',
				'stage' => 'lead',
				'stage_label' => 'Lead',
				'title' => 'Gọi lần ' . (int) $c['n'] . ' — ' . $result,
				'text' => $note,
				'by' => '',
				'at' => $ts ? date('c', $ts) : '',
				'at_label' => !empty($c['called_at_label']) ? (string) $c['called_at_label'] : '',
			);
		}
		return $items;
	}

	/**
	 * One query for Lead + Contact + Opp calendar activities.
	 *
	 * @param int[] $crmIds
	 * @param array<int,string> $stageById
	 * @return array
	 */
	protected static function calendarItemsForCrmIds(array $crmIds, array $stageById) {
		$crmIds = array_values(array_unique(array_filter(array_map('intval', $crmIds))));
		if (!$crmIds) {
			return array();
		}
		$adb = PearDatabase::getInstance();
		$marks = generateQuestionMarks($crmIds);
		$userNameSql = getSqlForNameInDisplayFormat(
			array('first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'),
			'Users'
		);
		$sql = "SELECT rel.crmid AS parent_id, a.activityid, a.activitytype, a.subject,
				a.date_start, a.time_start, ce.createdtime, ce.description,
				CASE WHEN (vtiger_users.user_name NOT LIKE '') THEN {$userNameSql} ELSE vtiger_groups.groupname END AS user_name
			FROM vtiger_seactivityrel rel
			INNER JOIN vtiger_activity a ON a.activityid = rel.activityid
			INNER JOIN vtiger_crmentity ce ON ce.crmid = a.activityid AND ce.deleted = 0
			LEFT JOIN vtiger_users ON vtiger_users.id = ce.smownerid
			LEFT JOIN vtiger_groups ON vtiger_groups.groupid = ce.smownerid
			WHERE rel.crmid IN ({$marks})
			  AND a.activitytype NOT IN ('Emails')
			ORDER BY ce.createdtime DESC
			LIMIT 120";
		$res = $adb->pquery($sql, $crmIds);
		$items = array();
		if (!$res) {
			return $items;
		}
		while ($row = $adb->fetchByAssoc($res)) {
			$parentId = (int) $row['parent_id'];
			$stageKey = isset($stageById[$parentId]) ? $stageById[$parentId] : 'opportunity';
			$typeRaw = strtolower(trim(self::decode($row['activitytype'])));
			$type = 'task';
			if ($typeRaw === 'call' || strpos($typeRaw, 'call') !== false || strpos($typeRaw, 'goi') !== false) {
				$type = 'call';
			} elseif ($typeRaw === 'meeting' || strpos($typeRaw, 'meeting') !== false) {
				$type = 'meeting';
			}
			$created = (string) $row['createdtime'];
			$ts = $created ? strtotime($created) : 0;
			$dateStart = (string) $row['date_start'];
			$timeStart = (string) $row['time_start'];
			$whenTs = $dateStart ? strtotime(trim($dateStart . ' ' . $timeStart)) : $ts;
			$items[] = array(
				'id' => 'act-' . (int) $row['activityid'] . '-' . $stageKey,
				'type' => $type,
				'stage' => $stageKey,
				'stage_label' => self::stageLabel($stageKey),
				'title' => self::decode($row['subject']),
				'text' => self::decode(trim((string) $row['description'])),
				'by' => self::decode($row['user_name']),
				'at' => $whenTs ? date('c', $whenTs) : '',
				'at_label' => $whenTs ? date('H:i d/m/Y', $whenTs) : '',
			);
		}
		return $items;
	}

	protected static function stageHistoryItems($potentialId) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT stage, lastmodified
			 FROM vtiger_potstagehistory
			 WHERE potentialid = ?
			 ORDER BY lastmodified ASC, historyid ASC
			 LIMIT 50',
			array((int) $potentialId)
		);
		$items = array();
		if (!$res) {
			return $items;
		}
		while ($row = $adb->fetchByAssoc($res)) {
			$mod = (string) $row['lastmodified'];
			$ts = $mod ? strtotime($mod) : 0;
			$stage = self::decode($row['stage']);
			$items[] = array(
				'id' => 'stage-' . $potentialId . '-' . ($ts ?: uniqid()),
				'type' => 'stage',
				'stage' => 'opportunity',
				'stage_label' => 'Cơ hội',
				'title' => 'Chuyển giai đoạn → ' . $stage,
				'text' => '',
				'by' => '',
				'at' => $ts ? date('c', $ts) : '',
				'at_label' => $ts ? date('H:i d/m/Y', $ts) : '',
			);
		}
		return $items;
	}

	protected static function createCallActivity($potentialId, $contactId, $subject, $note, $resultLabel) {
		global $current_user;
		$userId = $current_user && !empty($current_user->id) ? (int) $current_user->id : 1;
		$date = date('Y-m-d');
		$timeStart = date('H:i:s');
		$endTs = time() + 15 * 60;
		$timeEnd = date('H:i:s', $endTs);
		$dueDate = date('Y-m-d', $endTs);

		$record = Vtiger_Record_Model::getCleanInstance('Calendar');
		$record->set('mode', '');
		$record->set('subject', $subject);
		$record->set('activitytype', 'Call');
		$record->set('date_start', $date);
		$record->set('time_start', $timeStart);
		$record->set('due_date', $dueDate);
		$record->set('time_end', $timeEnd);
		$record->set('assigned_user_id', $userId);
		$record->set('parent_id', (int) $potentialId);
		$record->set('visibility', 'Public');
		$record->set('description', $note !== '' ? $note : $resultLabel);
		$record->set('eventstatus', 'Held');
		$record->set('taskstatus', 'Completed');
		if ($contactId > 0) {
			$record->set('contact_id', (int) $contactId);
		}
		$record->save();
		$activityId = (int) $record->getId();
		if ($activityId <= 0) {
			throw new Exception('Không tạo được hoạt động cuộc gọi.');
		}

		$adb = PearDatabase::getInstance();
		$exists = $adb->pquery(
			'SELECT 1 FROM vtiger_seactivityrel WHERE crmid = ? AND activityid = ? LIMIT 1',
			array((int) $potentialId, $activityId)
		);
		if (!$exists || !$adb->num_rows($exists)) {
			$adb->pquery(
				'INSERT INTO vtiger_seactivityrel (crmid, activityid) VALUES (?, ?)',
				array((int) $potentialId, $activityId)
			);
		}
		if ($contactId > 0) {
			$adb->pquery(
				'INSERT IGNORE INTO vtiger_cntactivityrel (contactid, activityid) VALUES (?, ?)',
				array((int) $contactId, $activityId)
			);
		}
		return $activityId;
	}

	protected static function dedupeAndSort(array $items) {
		$seen = array();
		$out = array();
		foreach ($items as $it) {
			$key = isset($it['id']) ? (string) $it['id'] : md5(json_encode($it));
			$soft = strtolower(
				(isset($it['type']) ? $it['type'] : '') . '|' .
				(isset($it['title']) ? $it['title'] : '') . '|' .
				(isset($it['at']) ? $it['at'] : '')
			);
			if (isset($seen[$key]) || isset($seen[$soft])) {
				continue;
			}
			$seen[$key] = true;
			$seen[$soft] = true;
			$out[] = $it;
		}
		usort($out, function ($a, $b) {
			$ta = !empty($a['at']) ? strtotime($a['at']) : 0;
			$tb = !empty($b['at']) ? strtotime($b['at']) : 0;
			if ($ta === $tb) {
				return 0;
			}
			return ($tb < $ta) ? -1 : 1;
		});
		return $out;
	}

	protected static function stageLabel($key) {
		$map = array(
			'lead' => 'Lead',
			'contact' => 'Liên hệ',
			'opportunity' => 'Cơ hội',
		);
		return isset($map[$key]) ? $map[$key] : $key;
	}

	/** Decode DB HTML entities (Pear often returns m&aacute;y). */
	protected static function decode($value) {
		$value = (string) $value;
		if ($value === '') {
			return '';
		}
		$prev = null;
		$i = 0;
		while ($value !== $prev && $i < 3) {
			$prev = $value;
			$value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
			$i++;
		}
		return $value;
	}
}
