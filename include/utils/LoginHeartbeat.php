<?php
/**
 * Heartbeat cho Team Status: cập nhật login_time của phiên "Signed in" để user được coi là đang hoạt động (trong 30 phút).
 * Gọi từ mọi trang (WebUI) để mọi thao tác đều được tính.
 */
class LoginHeartbeat {

	public static function update($userName) {
		if (empty($userName)) {
			return;
		}
		try {
			$db = PearDatabase::getInstance();
			$db->pquery(
				"UPDATE vtiger_loginhistory lh
				INNER JOIN (SELECT user_name, MAX(login_id) AS mid FROM vtiger_loginhistory WHERE LOWER(TRIM(status)) = 'signed in' GROUP BY user_name) t
				ON lh.user_name = t.user_name AND lh.login_id = t.mid
				SET lh.login_time = NOW()
				WHERE lh.user_name = ?",
				array($userName)
			);
		} catch (Exception $e) {
			// ignore
		}
	}
}
