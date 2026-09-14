<?php
/*+***********************************************************************************
 * Lưu trạng thái enabled của 1 kết nối Integration Hub.
 *************************************************************************************/

require_once 'modules/Vtiger/helpers/NkApiConnection.php';

class Settings_Vtiger_SaveConnectionStatus_Action extends Settings_Vtiger_Basic_Action
{

	public function checkPermission(Vtiger_Request $request)
	{
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		if (!$currentUserModel || !$currentUserModel->isAdminUser()) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function process(Vtiger_Request $request)
	{
		$response = new Vtiger_Response();
		try {
			$code = trim((string) $request->get('code'));
			$enabled = (int) $request->get('enabled');
			$userId = (int) Users_Record_Model::getCurrentUserModel()->getId();

			if ($code === '') {
				throw new Exception('Thiếu mã kết nối.');
			}

			$adapter = NkApiConnection::adapter($code);
			// Đi qua adapter->save để Google Sheet / Zalo đồng bộ đúng store cấu hình.
			$config = $adapter->save(array(
				'enabled' => $enabled ? 1 : 0,
			), $userId);
			if (!is_array($config) || empty($config)) {
				$config = $adapter->getConfigForAdmin();
			}

			$response->setResult(array(
				'success' => true,
				'config' => $config,
			));
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	public function validateRequest(Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
