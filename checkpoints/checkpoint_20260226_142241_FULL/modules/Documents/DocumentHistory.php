<?php
/**
 * Lịch sử document: chỉnh sửa, di chuyển, xóa, upload, download.
 * Bảng: vtiger_document_history (chạy script install_document_history_table.php một lần để tạo bảng).
 */
class Documents_History_Helper {

	const ACTION_CREATE = 'create';
	const ACTION_EDIT = 'edit';
	const ACTION_MOVE = 'move';
	const ACTION_DELETE = 'delete';
	const ACTION_UPLOAD = 'upload';
	const ACTION_DOWNLOAD = 'download';

	/**
	 * Ghi một dòng lịch sử.
	 * @param int $notesId Document (notesid)
	 * @param string $action create|edit|move|delete|upload|download
	 * @param string $extra Mô tả thêm (vd. folder cũ -> mới)
	 */
	public static function log($notesId, $action, $extra = '') {
		$db = PearDatabase::getInstance();
		$table = 'vtiger_document_history';
		$result = $db->pquery("SHOW TABLES LIKE ?", array($table));
		if ($db->num_rows($result) === 0) {
			return;
		}
		$userId = (int) Users_Record_Model::getCurrentUserModel()->getId();
		$db->pquery(
			"INSERT INTO {$table} (notesid, action, user_id, created_at, extra) VALUES (?, ?, ?, NOW(), ?)",
			array($notesId, $action, $userId, $extra)
		);
	}

	/**
	 * Lấy lịch sử của một document, mới nhất trước.
	 * @param int $notesId
	 * @param int $limit
	 * @return array
	 */
	public static function getHistory($notesId, $limit = 50) {
		$db = PearDatabase::getInstance();
		$table = 'vtiger_document_history';
		$result = $db->pquery("SHOW TABLES LIKE ?", array($table));
		if ($db->num_rows($result) === 0) {
			return array();
		}
		$res = $db->pquery(
			"SELECT h.*, u.user_name, u.first_name, u.last_name FROM {$table} h " .
			"LEFT JOIN vtiger_users u ON u.id = h.user_id WHERE h.notesid = ? ORDER BY h.created_at DESC LIMIT ?",
			array($notesId, (int) $limit)
		);
		$list = array();
		for ($i = 0; $i < $db->num_rows($res); $i++) {
			$action = $db->query_result($res, $i, 'action');
			$list[] = array(
				'action' => $action,
				'action_label' => self::getActionLabel($action),
				'user_name' => $db->query_result($res, $i, 'user_name'),
				'first_name' => $db->query_result($res, $i, 'first_name'),
				'last_name' => $db->query_result($res, $i, 'last_name'),
				'created_at' => $db->query_result($res, $i, 'created_at'),
				'extra' => $db->query_result($res, $i, 'extra'),
			);
		}
		return $list;
	}

	public static function getActionLabel($action) {
		$labels = array(
			self::ACTION_CREATE => 'Tạo mới',
			self::ACTION_EDIT => 'Chỉnh sửa',
			self::ACTION_MOVE => 'Di chuyển',
			self::ACTION_DELETE => 'Xóa',
			self::ACTION_UPLOAD => 'Upload',
			self::ACTION_DOWNLOAD => 'Download',
		);
		return isset($labels[$action]) ? $labels[$action] : $action;
	}
}
