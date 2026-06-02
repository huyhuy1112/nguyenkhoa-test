<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class Calendar_Edit_View extends Vtiger_Edit_View {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('Events');
		$this->exposeMethod('Calendar');
	}

	/**
	 * Events + Calendar Task full-page create/edit — MANAGEMENT dashboard shell.
	 */
	protected function isMkModernActivityForm(Vtiger_Request $request) {
		if ($request->get('displayMode') === 'overlay') {
			return false;
		}
		$app = strtoupper((string) $request->get('app'));
		if ($app !== 'MANAGEMENT' && $app !== '') {
			return false;
		}
		$mode = $request->get('mode');
		$module = $request->getModule();
		$recordId = $request->get('record');

		if ($mode === 'Events' || $module === 'Events') {
			return true;
		}
		if (!empty($recordId) && getSalesEntityType($recordId) === 'Events') {
			return true;
		}
		if ($module === 'Calendar') {
			if (!empty($recordId) && getSalesEntityType($recordId) === 'Events') {
				return false;
			}
			return true;
		}
		return false;
	}

	protected function isMkModernEventForm(Vtiger_Request $request) {
		return $this->isMkModernActivityForm($request);
	}

	protected function ensureManagementApp(Vtiger_Request $request) {
		if (empty($request->get('app'))) {
			$request->set('app', 'MANAGEMENT');
			$_REQUEST['app'] = 'MANAGEMENT';
		}
	}

	protected function assignModernActivityContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('SELECTED_MENU_CATEGORY', 'MANAGEMENT');
		$viewer->assign('SELECTED_MENU_CATEGORY_LABEL', vtranslate('LBL_MANAGEMENT', 'Vtiger'));
		$menuGroupedByParent = Settings_MenuEditor_Module_Model::getAllVisibleModules();
		if (isset($menuGroupedByParent['MANAGEMENT'])) {
			$viewer->assign('SELECTED_CATEGORY_MENU_LIST', $menuGroupedByParent['MANAGEMENT']);
		}
		$viewer->assign('VIEW', 'Edit');
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Calendar');
		$viewer->assign('MK_MODERN_ACTIVITY_CREATE', true);
		$viewer->assign('MK_MODERN_EVENT_CREATE', true);
		$viewer->assign('MK_CALENDAR_URL', 'index.php?module=Calendar&view=Calendar&app=MANAGEMENT');
		$viewer->assign('MK_ACTIVITY_MODULE', $moduleName === 'Events' ? 'Events' : 'Calendar');
	}

	protected function assignModernEventContext(Vtiger_Request $request) {
		$this->assignModernActivityContext($request);
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($this->isMkModernActivityForm($request)) {
			$this->ensureManagementApp($request);
			parent::preProcess($request, false);
			$this->assignModernActivityContext($request);
			if ($display) {
				$this->preProcessDisplay($request);
			}
			return;
		}
		parent::preProcess($request, $display);
	}

	public function preProcessTplName(Vtiger_Request $request) {
		if ($this->isMkModernActivityForm($request)) {
			return 'EditViewPreProcess.tpl';
		}
		return parent::preProcessTplName($request);
	}

	public function postProcess(Vtiger_Request $request) {
		if ($this->isMkModernActivityForm($request)) {
			$viewer = $this->getViewer($request);
			$module = $request->getModule();
			$viewer->view('EditViewPostProcess.tpl', $module === 'Events' ? 'Events' : 'Calendar');
			Vtiger_Basic_View::postProcess($request);
			return;
		}
		parent::postProcess($request);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		if ($this->isMkModernActivityForm($request)) {
			$cssFileNames = array(
				'~layouts/v7/modules/Calendar/resources/EventMkEdit.css',
			);
			$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
			return array_merge($headerCssInstances, $cssInstances);
		}
		return $headerCssInstances;
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		if ($this->isMkModernActivityForm($request)) {
			$jsFileNames = array(
				'layouts.v7.modules.Calendar.resources.Edit',
				'layouts.v7.modules.Calendar.resources.EventMkEdit',
			);
			$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
			return array_merge($headerScriptInstances, $jsScriptInstances);
		}
		return $headerScriptInstances;
	}

	public function checkPermission(Vtiger_Request $request) {
		parent::checkPermission($request);
		$moduleName = $request->getModule();
		$record = $request->get('record');
		if ($record) {
			$activityModulesList = array('Calendar', 'Events');
			$recordEntityName = getSalesEntityType($record);

			if (!in_array($recordEntityName, $activityModulesList) || !in_array($moduleName, $activityModulesList)) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
			}
		}
	}

	function process(Vtiger_Request $request) {
		$mode = $request->getMode();

		$recordId = $request->get('record');
		if(!empty($recordId)) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId);
			$mode = $recordModel->getType();
		}

		if(!empty($mode)) {
			$this->invokeExposedMethod($mode, $request, $mode);
			return;
		}
		$this->Calendar($request, 'Calendar');
	}

	function Events($request, $moduleName) {
		if ($this->isMkModernActivityForm($request)) {
			$this->ensureManagementApp($request);
			$this->assignModernActivityContext($request);
		}

		$currentUser = Users_Record_Model::getCurrentUserModel();

		$viewer = $this->getViewer ($request);
		$record = $request->get('record');
		$followUpTime='';
		$followUpDate='';
		 if(!empty($record) && $request->get('isDuplicate') == true) {
			$recordModel = Vtiger_Record_Model::getInstanceById($record, $moduleName);
			$viewer->assign('MODE', '');
		}else if(!empty($record)) {
			$recordModel = Vtiger_Record_Model::getInstanceById($record, $moduleName);
			$viewer->assign('MODE', 'edit');
			$viewer->assign('RECORD_ID', $record);
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$viewer->assign('MODE', '');
			$viewer->assign('RECORD_ID', '');
		}
		$eventModule = Vtiger_Module_Model::getInstance($moduleName);
		$recordModel->setModuleFromInstance($eventModule);

		$moduleModel = $recordModel->getModule();
		$fieldList = $moduleModel->getFields();
		$requestFieldList = array_intersect_key($request->getAllPurified(), $fieldList);

		$relContactId = $request->get('contact_id');
		if ($relContactId) {
			$contactRecordModel = Vtiger_Record_Model::getInstanceById($relContactId);
			$accountId = $contactRecordModel->get('account_id');
			if ($accountId) {
				$requestFieldList['parent_id'] = $accountId;
			}
		}
		foreach($requestFieldList as $fieldName=>$fieldValue){
			$fieldModel = $fieldList[$fieldName];
			$specialField = false;
			// We collate date and time part together in the EditView UI handling 
			// so a bit of special treatment is required if we come from QuickCreate 
			if (empty($record) && ($fieldName == 'time_start' || $fieldName == 'time_end') && !empty($fieldValue)) { 
				$specialField = true; 
				// Convert the incoming user-picked time to GMT time 
				// which will get re-translated based on user-time zone on EditForm 
				$fieldValue = DateTimeField::convertToDBTimeZone($fieldValue)->format("H:i"); 
			} 
			if (empty($record) && ($fieldName == 'date_start' || $fieldName == 'due_date') && !empty($fieldValue)) { 
				if($fieldName == 'date_start'){
					$startTime = Vtiger_Time_UIType::getTimeValueWithSeconds($requestFieldList['time_start']);
					$startDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($fieldValue." ".$startTime);
					list($startDate, $startTime) = explode(' ', $startDateTime);
					$fieldValue = Vtiger_Date_UIType::getDisplayDateValue($startDate);
				}else{
					$endTime = Vtiger_Time_UIType::getTimeValueWithSeconds($requestFieldList['time_end']);
					$endDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($fieldValue." ".$endTime);
					list($endDate, $endTime) = explode(' ', $endDateTime);
					$fieldValue = Vtiger_Date_UIType::getDisplayDateValue($endDate);
				}
			}

			if($fieldModel->isEditable() || $specialField) { 
				$recordModel->set($fieldName, $fieldModel->getDBInsertValue($fieldValue));
			}
		}
		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel,
									Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);

		$viewMode = $request->get('view_mode');
		if(!empty($viewMode)) {
			$viewer->assign('VIEW_MODE', $viewMode);
		}

		$userChangedEndDateTime = $request->get('userChangedEndDateTime');
		if(!empty($record) && $request->get('isDuplicate') != true) {
			$userChangedEndDateTime = 1;
		}
		//If followup value is passed from request to process the value and sent to client
		$requestFollowUpDate = $request->get('followup_date_start');
		$requestFollowUpTime = $request->get('followup_time_start');
		$followUpStatus = $request->get('followup');
		$eventStatus = $request->get('eventstatus');

		if(!empty($requestFollowUpDate)){
			$followUpDate = $requestFollowUpDate;
		}
		if(!empty($requestFollowUpTime)){
			$followUpTime = $requestFollowUpTime;
		}
		if($followUpStatus == 'on'){
			$viewer->assign('FOLLOW_UP_STATUS',TRUE);
		}
		if($eventStatus == 'Held'){
			$viewer->assign('SHOW_FOLLOW_UP',TRUE);
		}else{
			$viewer->assign('SHOW_FOLLOW_UP',FALSE);
		}

		$remainder = $request->get('set_reminder');
		if($remainder){
			$remainderValues[0]=$request->get('remdays');
			$remainderValues[1]=$request->get('remhrs');
			$remainderValues[2]=$request->get('remmin');
			$viewer->assign('REMINDER_VALUES',$remainderValues);
		}

		$viewer->assign('USER_CHANGED_END_DATE_TIME',$userChangedEndDateTime);
		$viewer->assign('FOLLOW_UP_DATE',$followUpDate);
		$viewer->assign('FOLLOW_UP_TIME',$followUpTime);
		$viewer->assign('RECURRING_INFORMATION', $recordModel->getRecurrenceInformation($request));
		$viewer->assign('TOMORROWDATE', Vtiger_Date_UIType::getDisplayDateValue(date('Y-m-d', time()+86400)));

		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$existingRelatedContacts = $recordModel->getRelatedContactInfo();

		//To add contact ids that is there in the request . Happens in gotoFull form mode of quick create
		$requestContactIdValue = $request->get('contact_id');
		if(!empty($requestContactIdValue)) {
			$existingRelatedContacts[] = array('name' => decode_html(Vtiger_Util_Helper::getRecordName($requestContactIdValue)) ,'id' => $requestContactIdValue);
		}
		//If already selected contact ids, then in gotoFull form should show those selected contact ids
		$idsList = $request->get('contactidlist');
		if(!empty($idsList)) {
			$contactIdsList = explode (';', $idsList);
			foreach($contactIdsList as $contactId) {
				$existingRelatedContacts[] = array('name' => decode_html(Vtiger_Util_Helper::getRecordName($contactId)) ,'id' => $contactId);
			}
		}

		$viewer->assign('RELATED_CONTACTS', $existingRelatedContacts);

		$isRelationOperation = $request->get('relationOperation');

		//if it is relation edit
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if($isRelationOperation) {
			$viewer->assign('SOURCE_MODULE', $request->get('sourceModule'));
			$viewer->assign('SOURCE_RECORD', $request->get('sourceRecord'));
		}
		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);
		$accessibleUsers = $currentUser->getAccessibleUsers();

		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE',Vtiger_Functions::jsonEncode($picklistDependencyDatasource));
		$viewer->assign('ACCESSIBLE_USERS', $accessibleUsers);
		$viewer->assign('INVITIES_SELECTED', $recordModel->getInvities());
		$viewer->assign('CURRENT_USER', $currentUser);

		// added to set the return values
		if($request->get('returnview')) {
			$request->setViewerReturnValues($viewer);
		}   

		if($request->get('displayMode')=='overlay'){
			$viewer->assign('SCRIPTS',$this->getOverlayHeaderScripts($request)); 
			$viewer->view('OverlayEditView.tpl', $moduleName);
		} else {
			$viewer->view('EditView.tpl', $moduleName);
		}
	}
	public function getOverlayHeaderScripts(Vtiger_Request $request) {
		parent::getOverlayHeaderScripts($request);
	}

	function Calendar($request, $moduleName) {
		if ($this->isMkModernActivityForm($request)) {
			$this->ensureManagementApp($request);
			$this->assignModernActivityContext($request);
		}
		parent::process($request);
	}
}