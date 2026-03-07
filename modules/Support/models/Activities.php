<?php
/*+***********************************************************************************
 * Support_Activities_Model
 * Read-only provider for Support -> Activities dashboard.
 ************************************************************************************/

class Support_Activities_Model {

	/**
	 * Fetch support activities from custom table vtiger_activities only.
	 *
	 * @param string|null $fromDate Optional date filter (Y-m-d). Pass null to load all.
	 * @param int $limit
	 * @return array
	 */
	public static function getUpcomingData($fromDate = null, $limit = 0) {
		$db = PearDatabase::getInstance();
		$limit = (int)$limit;
		$params = array();
		$where = " WHERE 1=1 ";
		if (!empty($fromDate)) {
			$where .= " AND a.activity_date >= ? ";
			$params[] = $fromDate;
		}
		$limitSql = $limit > 0 ? (" LIMIT " . $limit) : "";

		$sql = "SELECT
					a.activityid,
					a.content AS subject,
					a.activity_type AS activitytype,
					a.activity_date AS date_start,
					a.activity_date AS due_date,
					a.status,
					a.assigned_user_id AS assigned_to,
					'—' AS related_to
				FROM vtiger_activities a
				{$where}
				ORDER BY
					a.activity_date ASC,
					a.activityid ASC
				{$limitSql}";

		$result = $db->pquery($sql, $params);
		$rows = array();
		$buckets = array(
			'tasks' => array(),
			'events' => array(),
			'anniversaries' => array(),
		);

		while ($result && ($row = $db->fetchByAssoc($result))) {
			$activityType = trim((string)$row['activitytype']);
			$normalizedType = strtolower($activityType);
			$row['tagClass'] = self::getTagClass($activityType);
			$row['detail_url'] = 'index.php?module=Activities&view=Detail&record=' . (int)$row['activityid'];
			$rows[] = $row;

			if ($normalizedType === 'task') {
				$buckets['tasks'][] = $row;
			} elseif ($normalizedType === 'anniversary') {
				$buckets['anniversaries'][] = $row;
			} elseif ($normalizedType === 'meeting' || $normalizedType === 'call' || $normalizedType === 'event') {
				$buckets['events'][] = $row;
			}
		}

		return array(
			'all' => $rows,
			'tasks' => $buckets['tasks'],
			'events' => $buckets['events'],
			'anniversaries' => $buckets['anniversaries'],
			'counts' => array(
				'all' => count($rows),
				'tasks' => count($buckets['tasks']),
				'events' => count($buckets['events']),
				'anniversaries' => count($buckets['anniversaries']),
			),
		);
	}

	/**
	 * Color tags requested by business.
	 *
	 * @param string $activityType
	 * @return string
	 */
	protected static function getTagClass($activityType) {
		$type = strtolower(trim((string)$activityType));
		if ($type === 'task') {
			return 'activity-tag-task';
		}
		if ($type === 'meeting') {
			return 'activity-tag-meeting';
		}
		if ($type === 'call') {
			return 'activity-tag-call';
		}
		if ($type === 'anniversary') {
			return 'activity-tag-anniversary';
		}
		return 'activity-tag-default';
	}
}

