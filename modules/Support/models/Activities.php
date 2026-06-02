<?php
/*+***********************************************************************************
 * Support_Activities_Model
 * Read-only provider for Support -> Activities dashboard.
 ************************************************************************************/

class Support_Activities_Model {

	/** @var array|null */
	protected static $textColumns = null;

	/**
	 * Text columns that exist on vtiger_activities (schema varies by install).
	 *
	 * @return array
	 */
	protected static function getTextColumns() {
		if (self::$textColumns !== null) {
			return self::$textColumns;
		}

		$db = PearDatabase::getInstance();
		self::$textColumns = array();
		$allowed = array('title' => true, 'content' => true, 'activity_name' => true);
		$res = $db->pquery('SHOW COLUMNS FROM vtiger_activities', array());
		while ($res && ($row = $db->fetchByAssoc($res))) {
			$field = isset($row['Field']) ? (string)$row['Field'] : '';
			if ($field !== '' && isset($allowed[$field])) {
				self::$textColumns[] = $field;
			}
		}
		// Preserve preferred display order regardless of DB column order.
		$ordered = array();
		foreach (array('title', 'content', 'activity_name') as $column) {
			if (in_array($column, self::$textColumns, true)) {
				$ordered[] = $column;
			}
		}
		self::$textColumns = $ordered;
		return self::$textColumns;
	}

	/**
	 * @return string
	 */
	protected static function getSubjectSelectExpr() {
		$columns = self::getTextColumns();
		if (empty($columns)) {
			return "CONCAT('Activity ', a.activityid)";
		}

		$parts = array();
		foreach ($columns as $column) {
			$parts[] = "NULLIF(TRIM(a.{$column}), '')";
		}
		return 'COALESCE(' . implode(', ', $parts) . ", CONCAT('Activity ', a.activityid))";
	}

	/**
	 * @param array $params
	 * @param string $keyword
	 * @return string
	 */
	protected static function appendKeywordFilter(array &$params, $keyword) {
		$keyword = trim((string)$keyword);
		if ($keyword === '') {
			return '';
		}

		$columns = self::getTextColumns();
		if (empty($columns)) {
			return '';
		}

		$like = '%' . $keyword . '%';
		$clauses = array();
		foreach ($columns as $column) {
			$clauses[] = "a.{$column} LIKE ?";
			$params[] = $like;
		}
		return ' AND (' . implode(' OR ', $clauses) . ') ';
	}

	/**
	 * @param array $row
	 * @return array
	 */
	protected static function normalizeListRow(array $row) {
		$activityType = trim((string)($row['activitytype'] ?? ''));
		$normalizedType = strtolower($activityType);
		$row['tagClass'] = self::getTagClass($activityType);
		$row['type_icon'] = self::getTypeIcon($normalizedType);
		$assignedName = trim((string)($row['assigned_name'] ?? ''));
		$row['assigned_display'] = $assignedName !== ''
			? $assignedName
			: (string)($row['assigned_to'] ?? '');
		$row['detail_url'] = 'index.php?module=Activities&view=Detail&record=' . (int)$row['activityid'] . '&app=SUPPORT';
		if (empty($row['subject'])) {
			$row['subject'] = 'Activity ' . (int)$row['activityid'];
		}
		return $row;
	}

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
		$subjectExpr = self::getSubjectSelectExpr();

		$sql = "SELECT
					a.activityid,
					{$subjectExpr} AS subject,
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
			$row = self::normalizeListRow($row);
			$activityType = trim((string)$row['activitytype']);
			$normalizedType = strtolower($activityType);
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
	/**
	 * Paginated list with filters for Activities module list view.
	 *
	 * @param array $filters status, activity_type, assigned_user_id, activity_date, q, sort
	 * @param int $page
	 * @param int $pageLimit
	 * @return array
	 */
	public static function getListData(array $filters = array(), $page = 1, $pageLimit = 25) {
		$db = PearDatabase::getInstance();
		$page = max(1, (int)$page);
		$pageLimit = max(1, min(100, (int)$pageLimit));
		$offset = ($page - 1) * $pageLimit;

		$where = ' WHERE 1=1 ';
		$params = array();

		$status = isset($filters['status']) ? trim((string)$filters['status']) : '';
		if ($status !== '') {
			$where .= ' AND a.status = ? ';
			$params[] = $status;
		}

		$type = isset($filters['activity_type']) ? trim((string)$filters['activity_type']) : '';
		if ($type !== '') {
			$where .= ' AND a.activity_type = ? ';
			$params[] = $type;
		}

		$assignedId = isset($filters['assigned_user_id']) ? (int)$filters['assigned_user_id'] : 0;
		if ($assignedId > 0) {
			$where .= ' AND a.assigned_user_id = ? ';
			$params[] = $assignedId;
		}

		$activityDate = isset($filters['activity_date']) ? trim((string)$filters['activity_date']) : '';
		if ($activityDate !== '') {
			$where .= ' AND DATE(a.activity_date) = ? ';
			$params[] = $activityDate;
		}

		$fromDate = isset($filters['from_date']) ? trim((string)$filters['from_date']) : '';
		if ($fromDate !== '') {
			$where .= ' AND a.activity_date >= ? ';
			$params[] = $fromDate;
		}

		$keyword = isset($filters['q']) ? (string)$filters['q'] : '';
		$where .= self::appendKeywordFilter($params, $keyword);

		$sort = isset($filters['sort']) ? (string)$filters['sort'] : 'latest';
		$orderSql = ($sort === 'oldest')
			? ' ORDER BY a.activity_date ASC, a.activityid ASC '
			: ' ORDER BY a.activity_date DESC, a.activityid DESC ';

		$subjectExpr = self::getSubjectSelectExpr();

		$countSql = "SELECT COUNT(*) AS total FROM vtiger_activities a {$where}";
		$countRes = $db->pquery($countSql, $params);
		$total = ($countRes && $db->num_rows($countRes) > 0)
			? (int)$db->query_result($countRes, 0, 'total')
			: 0;

		$listSql = "SELECT
				a.activityid,
				{$subjectExpr} AS subject,
				a.activity_type AS activitytype,
				a.activity_date AS date_start,
				a.status,
				a.assigned_user_id AS assigned_to,
				TRIM(CONCAT(IFNULL(u.first_name, ''), ' ', IFNULL(u.last_name, ''))) AS assigned_name
			FROM vtiger_activities a
			LEFT JOIN vtiger_users u ON u.id = a.assigned_user_id
			{$where}
			{$orderSql}
			LIMIT {$offset}, {$pageLimit}";

		$listRes = $db->pquery($listSql, $params);
		$rows = array();
		$buckets = array(
			'tasks' => array(),
			'events' => array(),
			'anniversaries' => array(),
		);

		if ($total > 0 && (!$listRes || $db->num_rows($listRes) === 0)) {
			$fallbackSql = "SELECT
					a.activityid,
					{$subjectExpr} AS subject,
					a.activity_type AS activitytype,
					a.activity_date AS date_start,
					a.status,
					a.assigned_user_id AS assigned_to,
					'' AS assigned_name
				FROM vtiger_activities a
				{$where}
				{$orderSql}
				LIMIT {$offset}, {$pageLimit}";
			$listRes = $db->pquery($fallbackSql, $params);
		}

		while ($listRes && ($row = $db->fetchByAssoc($listRes))) {
			$row = self::normalizeListRow($row);
			$activityType = trim((string)$row['activitytype']);
			$normalizedType = strtolower($activityType);
			$rows[] = $row;

			if ($normalizedType === 'task') {
				$buckets['tasks'][] = $row;
			} elseif ($normalizedType === 'anniversary') {
				$buckets['anniversaries'][] = $row;
			} elseif (in_array($normalizedType, array('meeting', 'call', 'event'), true)) {
				$buckets['events'][] = $row;
			}
		}

		$statsSql = "SELECT
				COUNT(*) AS total_all,
				SUM(CASE WHEN LOWER(TRIM(a.activity_type)) = 'task' THEN 1 ELSE 0 END) AS total_tasks,
				SUM(CASE WHEN LOWER(TRIM(a.activity_type)) IN ('meeting','call','event') THEN 1 ELSE 0 END) AS total_events,
				SUM(CASE WHEN LOWER(TRIM(a.activity_type)) = 'anniversary' THEN 1 ELSE 0 END) AS total_anniversaries
			FROM vtiger_activities a
			{$where}";
		$statsRes = $db->pquery($statsSql, $params);
		$counts = array(
			'all' => $total,
			'tasks' => 0,
			'events' => 0,
			'anniversaries' => 0,
		);
		if ($statsRes && $db->num_rows($statsRes) > 0) {
			$counts['all'] = (int)$db->query_result($statsRes, 0, 'total_all');
			$counts['tasks'] = (int)$db->query_result($statsRes, 0, 'total_tasks');
			$counts['events'] = (int)$db->query_result($statsRes, 0, 'total_events');
			$counts['anniversaries'] = (int)$db->query_result($statsRes, 0, 'total_anniversaries');
		}

		$pageCount = $pageLimit > 0 ? (int)ceil($total / $pageLimit) : 1;
		$showFrom = $total > 0 ? $offset + 1 : 0;
		$showTo = min($offset + count($rows), $total);

		return array(
			'rows' => $rows,
			'counts' => $counts,
			'total' => $total,
			'page' => $page,
			'page_count' => $pageCount,
			'page_limit' => $pageLimit,
			'show_from' => $showFrom,
			'show_to' => $showTo,
		);
	}

	/**
	 * @param string $normalizedType lowercase activity type
	 * @return string
	 */
	protected static function getTypeIcon($normalizedType) {
		if ($normalizedType === 'task') {
			return '✔';
		}
		if ($normalizedType === 'meeting') {
			return '📅';
		}
		if ($normalizedType === 'call') {
			return '📞';
		}
		if ($normalizedType === 'anniversary') {
			return '🎂';
		}
		return '•';
	}

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

