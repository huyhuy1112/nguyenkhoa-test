<?php
/*+***********************************************************************************
 * Lưu trạng thái enabled của 1 kết nối Integration Hub.
 *************************************************************************************/

require_once 'modules/Vtiger/helpers/NkApi/NkApiConnection.php';

class Settings_Vtiger_SaveConnectionStatus_Action extends Vtiger_Action_Controller
{

    public function checkPermission(Vtiger_Request $request)
    {
        file_put_contents('/tmp/nk_debug.log', date('H:i:s') . " [checkPermission] called\n", FILE_APPEND);
        if (!Users_Privileges_Model::isPermitted('Vtiger', 'DetailView')) {
            file_put_contents('/tmp/nk_debug.log', date('H:i:s') . " [checkPermission] DENIED\n", FILE_APPEND);
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        }
        file_put_contents('/tmp/nk_debug.log', date('H:i:s') . " [checkPermission] OK\n", FILE_APPEND);
    }

    public function process(Vtiger_Request $request)
    {
        file_put_contents('/tmp/nk_debug.log', date('H:i:s') . " [process START] code=" . $request->get('code') . " enabled=" . $request->get('enabled') . "\n", FILE_APPEND);

        $response = new Vtiger_Response();
        try {
            $code = trim((string) $request->get('code'));
            $enabled = (int) $request->get('enabled');
            $userId = Users_Record_Model::getCurrentUserModel()->getId();

            file_put_contents('/tmp/nk_debug.log', date('H:i:s') . " [parsed] code=$code enabled=$enabled userId=$userId\n", FILE_APPEND);

            if ($code === '') {
                throw new Exception('Thiếu mã kết nối.');
            }

            $adapter = NkApiConnection::adapter($code);
            file_put_contents('/tmp/nk_debug.log', date('H:i:s') . " [adapter OK]\n", FILE_APPEND);

            $current = NkApiConnection::getRow($code);
            $status = $current['status'];
            file_put_contents('/tmp/nk_debug.log', date('H:i:s') . " [current] status=$status\n", FILE_APPEND);

            if ($enabled && ($status === 'not_configured' || $status === '' || $status === null)) {
                $status = !empty($current['base_url']) || !empty($current['credentials'])
                    ? 'idle'
                    : 'not_configured';
            }

            NkApiConnection::saveRow($code, array(
                'enabled' => $enabled ? 1 : 0,
                'status' => $status,
            ), $userId);

            file_put_contents('/tmp/nk_debug.log', date('H:i:s') . " [saved] enabled=" . ($enabled ? 1 : 0) . " status=$status\n", FILE_APPEND);

            $adapter->getConfigForAdmin();
            file_put_contents('/tmp/nk_debug.log', date('H:i:s') . " [getConfigForAdmin OK]\n", FILE_APPEND);

            $response->setResult(array(
                'success' => true,
                'config' => $adapter->getConfigForAdmin(),
            ));

            file_put_contents('/tmp/nk_debug.log', date('H:i:s') . " [result set]\n", FILE_APPEND);
        } catch (Exception $e) {
            file_put_contents('/tmp/nk_debug.log', date('H:i:s') . " [EXCEPTION] " . $e->getMessage() . "\n", FILE_APPEND);
            $response->setError(500, $e->getMessage());
        }

        file_put_contents('/tmp/nk_debug.log', date('H:i:s') . " [before emit]\n", FILE_APPEND);
        $response->emit();
        file_put_contents('/tmp/nk_debug.log', date('H:i:s') . " [after emit]\n", FILE_APPEND);
    }

    public function validateRequest(Vtiger_Request $request)
    {
        file_put_contents('/tmp/nk_debug.log', date('H:i:s') . " [validateRequest] called\n", FILE_APPEND);
        $request->validateWriteAccess();
        file_put_contents('/tmp/nk_debug.log', date('H:i:s') . " [validateRequest] OK\n", FILE_APPEND);
    }
}