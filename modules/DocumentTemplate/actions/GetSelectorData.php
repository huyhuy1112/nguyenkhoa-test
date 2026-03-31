<?php
class DocumentTemplate_GetSelectorData_Action extends Vtiger_Action_Controller {

	public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}

	public function checkPermission($request) {
		return true;
	}

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);

		try {
			$targetModule = (string) $request->get('targetModule');
			$targetRecord = (int) $request->get('targetRecord');

			require_once 'modules/DocumentTemplate/helpers/TemplateSetup.php';
			DocumentTemplate_TemplateSetup_Helper::runAll();

			$db = PearDatabase::getInstance();

			$feature = '';
			if ($targetModule === 'Invoice') $feature = 'Invoice';
			if ($targetModule === 'Quotes') $feature = 'Quote';

			$options = array();
			$selectedId = 0;

			if ($feature !== '') {
				$options = DocumentTemplate_TemplateSetup_Helper::getTemplateOptionsByFeature($db, $feature);
				// Creating new record => no binding yet, keep selectedId = 0
				if ($targetRecord > 0) {
					$selectedId = DocumentTemplate_TemplateSetup_Helper::getBoundTemplateId($db, $targetModule, $targetRecord);
				}
			}

			$response->setResult(array(
				'options' => $options,
				'selectedId' => (int) $selectedId,
				'optionCount' => count($options),
				'feature' => $feature,
				'targetModule' => $targetModule,
				'targetRecord' => (int) $targetRecord,
			));
			$response->emit();
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
			$response->emit();
		}
	}
}
?>

