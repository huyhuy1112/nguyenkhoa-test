<?php
/**
 * Cài đặt bảng phân quyền folder Documents.
 * Chạy một lần: php modules/Documents/install_folder_sharing.php
 *
 * Nếu lỗi kết nối DB (host 'db' dùng trong Docker):
 * mysql -u root -p TDB1 < modules/Documents/install_folder_sharing.sql
 */
$crmRoot = dirname(dirname(__DIR__));
chdir($crmRoot);
require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';

try {
    $db = PearDatabase::getInstance();
} catch (Exception $e) {
    echo "Lỗi kết nối DB. Chạy SQL thủ công:\n  mysql -u root -p TDB1 < modules/Documents/install_folder_sharing.sql\n";
    exit(1);
}

$sql = "CREATE TABLE IF NOT EXISTS vtiger_documentfolder_sharing (
    folderid INT(11) NOT NULL,
    sharetype VARCHAR(30) NOT NULL,
    shareid INT(11) NOT NULL,
    PRIMARY KEY (folderid, sharetype, shareid),
    KEY folderid (folderid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$db->pquery($sql, array());
echo "Table vtiger_documentfolder_sharing created.\n";
