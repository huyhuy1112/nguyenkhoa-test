<?php
/*
 * Campaigns Edit view override
 * UI-only: remove legacy field "product" from record structure before rendering.
 */

class Campaigns_Edit_View extends Vtiger_Edit_View {
	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$record = $request->get('record');

		if(!empty($record) && $request->get('isDuplicate') == true) {
			$recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName);
			$viewer->assign('MODE', '');

			// While duplicating record, remove related record info if the related record is deleted.
			$mandatoryFieldModels = $recordModel->getModule()->getMandatoryFieldModels();
			foreach ($mandatoryFieldModels as $fieldModel) {
				if ($fieldModel->isReferenceField()) {
					$fieldName = $fieldModel->get('name');
					if (Vtiger_Util_Helper::checkRecordExistance($recordModel->get($fieldName))) {
						$recordModel->set($fieldName, '');
					}
				}
			}
		}else if(!empty($record)) {
			$recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName);
			$viewer->assign('RECORD_ID', $record);
			$viewer->assign('MODE', 'edit');
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$viewer->assign('MODE', '');
		}

		if(!$this->record){
			$this->record = $recordModel;
		}

		$moduleModel = $recordModel->getModule();
		$fieldList = $moduleModel->getFields();
		$requestFieldList = array_intersect_key($request->getAllPurified(), $fieldList);

		// Documents: when creating new from list (URL has folder_id), assign folder into form so it saves into correct folder.
		if ($moduleName == 'Documents' && empty($record)) {
			$folderId = $request->get('folder_id');
			if ($folderId !== '' && $folderId !== null) {
				$requestFieldList['folderid'] = $folderId;
				$viewer->assign('DOCUMENTS_FOLDER_ID', $folderId);
			}
		}

		$relContactId = $request->get('contact_id');
		if ($relContactId && $moduleName == 'Calendar') {
			$contactRecordModel = Vtiger_Record_Model::getInstanceById($relContactId);
			$requestFieldList['parent_id'] = $contactRecordModel->get('account_id');
		}

		foreach($requestFieldList as $fieldName=>$fieldValue){
			$fieldModel = $fieldList[$fieldName];
			$specialField = false;

			// We collate date and time part together in the EditView UI handling.
			if ($moduleName == 'Calendar' && empty($record) && $fieldName == 'time_start' && !empty($fieldValue)) {
				$specialField = true;
				$fieldValue = DateTimeField::convertToDBTimeZone($fieldValue)->format("H:i");
			}

			if ($moduleName == 'Calendar' && empty($record) && $fieldName == 'date_start' && !empty($fieldValue)) {
				$startTime = Vtiger_Time_UIType::getTimeValueWithSeconds($requestFieldList['time_start']);
				$startDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($fieldValue." ".$startTime);
				list($startDate, $startTime) = explode(' ', $startDateTime);
				$fieldValue = Vtiger_Date_UIType::getDisplayDateValue($startDate);
			}

			if($fieldModel->isEditable() || $specialField) {
				$recordModel->set($fieldName, $fieldModel->getDBInsertValue($fieldValue));
			}
		}

		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel(
			$recordModel,
			Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT
		);

		// Campaigns-only: remove legacy "product" field from record structure (Create and Edit).
		if ($moduleName === 'Campaigns') {
			$structure = $recordStructureInstance->getStructure();
			foreach ($structure as $blockLabel => &$blockFields) {
				if (!is_array($blockFields)) {
					continue;
				}
				foreach ($blockFields as $fieldName => $fieldModel) {
					$fieldModelName = null;
					if (is_object($fieldModel)) {
						if (method_exists($fieldModel, 'getName')) {
							$fieldModelName = $fieldModel->getName();
						} elseif (method_exists($fieldModel, 'get')) {
							// Fallback for older field models
							$fieldModelName = $fieldModel->get('name');
						}
					}
					if ($fieldName === 'product' || $fieldModelName === 'product') {
						unset($blockFields[$fieldName]);
					}
				}
			}
			unset($blockFields);
		} else {
			$structure = $recordStructureInstance->getStructure();
		}

		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);

		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', Vtiger_Functions::jsonEncode($picklistDependencyDatasource));
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $structure);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());

		$isRelationOperation = $request->get('relationOperation');
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if($isRelationOperation) {
			$viewer->assign('SOURCE_MODULE', $request->get('sourceModule'));
			$viewer->assign('SOURCE_RECORD', $request->get('sourceRecord'));
		}

		// added to set the return values
		if($request->get('returnview')) {
			$request->setViewerReturnValues($viewer);
		}
		$viewer->assign('MAX_UPLOAD_LIMIT_MB', Vtiger_Util_Helper::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT_BYTES', Vtiger_Util_Helper::getMaxUploadSizeInBytes());

		if ($moduleName === 'Campaigns') {
			$pc = $recordModel->get('campaign_phase_count');
			if ($pc === '' || $pc === null) {
				$pc = 2;
			}
			$pc = (int) $pc;
			if ($pc < 2) {
				$pc = 2;
			}
			if ($pc > 5) {
				$pc = 5;
			}
			$viewer->assign('CAMPAIGN_INITIAL_PHASE_COUNT', $pc);
		}

		if($request->get('displayMode')=='overlay'){
			$viewer->assign('SCRIPTS',$this->getOverlayHeaderScripts($request));
			$viewer->view('OverlayEditView.tpl', $moduleName);
		}
		else{
			$viewer->view('EditView.tpl', $moduleName);
		}
	}
}

