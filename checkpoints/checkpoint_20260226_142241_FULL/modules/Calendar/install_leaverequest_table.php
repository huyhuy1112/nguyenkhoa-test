<?php
/**
 * Chạy một lần để tạo bảng đơn nghỉ phép (chỉ dùng cho mini calendar).
 * Gọi: index.php?module=Calendar&action=InstallLeaveRequestTable
 * Hoặc chạy trực tiếp: php -f modules/Calendar/install_leaverequest_table.php (cần include config)
 */
$adb = PearDatabase::getInstance();
$table = 'vtiger_leaverequest';
if (Vtiger_Utils::CheckTable($table)) {
	echo "Table $table already exists.\n";
	return;
}
$sql = "CREATE TABLE `vtiger_leaverequest` (
  `leaverequestid` int(11) NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) DEFAULT '',
  `leave_type` varchar(50) DEFAULT 'paid',
  `approval_status` varchar(50) DEFAULT 'pending',
  `half_day` tinyint(1) DEFAULT 0,
  `date_start` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `description` text,
  `created_user_id` int(11) DEFAULT NULL,
  `createdtime` datetime DEFAULT NULL,
  PRIMARY KEY (`leaverequestid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
$adb->pquery($sql, array());
echo "Table $table created.\n";
