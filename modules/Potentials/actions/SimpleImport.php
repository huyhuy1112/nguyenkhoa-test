<?php
/*+***********************************************************************************
 * Potentials one-step CSV/Excel import with auto column mapping.
 *************************************************************************************/

require_once 'modules/Potentials/helpers/SimpleImport.php';
require_once 'modules/Import/views/Main.php';
require_once 'modules/Import/actions/Data.php';
require_once 'modules/Import/actions/Queue.php';
require_once 'modules/Import/actions/Lock.php';

class Potentials_SimpleImport_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if (!Users_Privileges_Model::isPermitted($moduleName, 'Import')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}

	public function process(Vtiger_Request $request) {
		global $VTIGER_BULK_SAVE_MODE;
		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);

		$previousBulkSaveMode = $VTIGER_BULK_SAVE_MODE;
		$VTIGER_BULK_SAVE_MODE = true;

		try {
			if ($request->getModule() !== 'Potentials') {
				throw new Exception('Invalid module');
			}

			if (empty($_FILES['import_file']) || empty($_FILES['import_file']['tmp_name'])) {
				throw new Exception('Chưa chọn file hoặc trình duyệt không gửi được file. Chọn lại file CSV rồi bấm Import ngay.');
			}

			$user = Users_Record_Model::getCurrentUserModel();
			Import_Utils_Helper::clearUserImportInfo($user);

			if (!$request->get('delimiter')) {
				$request->set('delimiter', ',');
			}
			if (!$request->get('has_header')) {
				$request->set('has_header', 'on');
			}
			$request->set('merge_type', 0);
			$request->set('auto_merge', 0);
			$request->set('merge_fields', '');

			if (!Import_Utils_Helper::validateFileUpload($request)) {
				$errorMessage = $request->get('error_message');
				throw new Exception($errorMessage ? $errorMessage : vtranslate('LBL_FILE_UPLOAD_FAILED', 'Import'));
			}

			$fieldMapping = Potentials_SimpleImport_Helper::buildFieldMapping($request, $user);
			if (!array_key_exists('cf_857', $fieldMapping) && !array_key_exists('potentialname', $fieldMapping)) {
				throw new Exception('Không nhận diện được cột Project Name hoặc Opportunity Name trong file CSV.');
			}

			$request->set('field_mapping', $fieldMapping);
			$request->set('default_values', array(
				'order_category' => 'Internal',
				'sales_stage' => 'Prospecting',
				'closingdate' => Potentials_SimpleImport_Helper::getDefaultClosingDate(),
			));

			$importController = new Import_Main_View($request, $user);
			$importController->saveMap();
			if (!$importController->copyFromFileToDB()) {
				throw new Exception('Không đọc được dữ liệu từ file CSV. Kiểm tra file có header + ít nhất 1 dòng dữ liệu.');
			}
			$importController->queueDataImport();

			$importInfo = Import_Queue_Action::getImportInfo('Potentials', $user);
			if ($importInfo == null) {
				throw new Exception('Không thể khởi tạo import.');
			}

			$importDataController = new Import_Data_Action($importInfo, $user);
			if (!$importDataController->initializeImport()) {
				throw new Exception(vtranslate('ERR_FAILED_TO_LOCK_MODULE', 'Import'));
			}

			$importDataController->importData();
			$codeStats = Potentials_SimpleImport_Helper::applyProjectCodesAfterImport($user);
			$importStatusCount = $importDataController->getImportStatusCount();
			$failedSamples = Potentials_SimpleImport_Helper::getFailedRowSamples($user);
			$importDataController->finishImport();

			$imported = (int)$importStatusCount['IMPORTED'];
			$failed = (int)$importStatusCount['FAILED'];
			$skipped = (int)$importStatusCount['SKIPPED'];
			$total = (int)$importStatusCount['TOTAL'];
			$message = Potentials_SimpleImport_Helper::buildResultMessage($importStatusCount, $failedSamples, $codeStats);

			$response->setResult(array(
				'success' => ($imported > 0),
				'imported' => $imported,
				'failed' => $failed,
				'skipped' => $skipped,
				'total' => $total,
				'mapped_columns' => count($fieldMapping),
				'failed_samples' => $failedSamples,
				'message' => $message,
			));
		} catch (Throwable $e) {
			$response->setError($e->getMessage());
		}

		$VTIGER_BULK_SAVE_MODE = $previousBulkSaveMode;
		$response->emit();
	}
}
