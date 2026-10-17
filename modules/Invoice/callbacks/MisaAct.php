<?php
/*+***********************************************************************************
 * MISA gọi URL này khi cập nhật trạng thái Đơn đặt hàng / chứng từ liên quan.
 * Khai trong MISA → Thiết lập → Kết nối ứng dụng → API kết nối.
 *************************************************************************************/
chdir(dirname(__FILE__) . '/../../../');
include_once 'includes/main/WebUI.php';
require_once 'modules/Invoice/models/MisaSyncService.php';

header('Content-Type: application/json; charset=UTF-8');

$raw = file_get_contents('php://input');
$payload = json_decode((string) $raw, true);
if (!is_array($payload)) {
	$payload = $_POST;
}
if (!is_array($payload)) {
	$payload = array();
}

try {
	$result = Invoice_MisaSyncService::applyCallback($payload);
} catch (Exception $e) {
	$result = array('Success' => false, 'ErrorMessage' => $e->getMessage());
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
