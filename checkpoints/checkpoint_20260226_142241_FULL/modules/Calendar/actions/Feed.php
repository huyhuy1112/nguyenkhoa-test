<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

vimport ('~~/include/Webservices/Query.php');

class Calendar_Feed_Action extends Vtiger_BasicAjax_Action {

	public function process(Vtiger_Request $request) {
		if($request->get('mode') === 'batch') {
			$feedsRequest = $request->get('feedsRequest',array());
			$result = array();
			if(php7_count($feedsRequest)) {
				foreach($feedsRequest as $key=>$value) {
					$value = vtlib_array($value); // isset guarded.

					$requestParams = array();
					$requestParams['start'] = $value['start'];
					$requestParams['end'] = $value['end'];
					$requestParams['type'] = $value['type'];
					$requestParams['userid'] = $value['userid'];
					$requestParams['color'] = $value['color'];
					$requestParams['textColor'] = $value['textColor'];
					$requestParams['targetModule'] = $value['targetModule'];
					$requestParams['fieldname'] = $value['fieldname'];
					$requestParams['group'] = $value['group'];
					$requestParams['mapping'] = $value['mapping'];
					$requestParams['conditions'] = $value['conditions'];
					$result[$key] = $this->_process($requestParams);
				}
			}
			echo json_encode($result);
		} else {
			$requestParams = array();
			$requestParams['start'] = $request->get('start');
			$requestParams['end'] = $request->get('end');
			$requestParams['type'] = $request->get('type');
			$requestParams['userid'] = $request->get('userid');
			$requestParams['color'] = $request->get('color');
			$requestParams['textColor'] = $request->get('textColor');
			$requestParams['targetModule'] = $request->get('targetModule');
			$requestParams['fieldname'] = $request->get('fieldname');
			$requestParams['group'] = $request->get('group');
			$requestParams['mapping'] = $request->get('mapping');
			$requestParams['conditions'] = $request->get('conditions','');
			echo $this->_process($requestParams);
		}
	}

	public function _process($request) {
		try {
			$start = $request['start'];
			$end = $request['end'];
			$type = $request['type'];
			$userid = $request['userid'];
			$color = $request['color'];
			$textColor = $request['textColor'];
			// Màu mặc định để calendar luôn hiện khung màu khi feed không cấu hình màu
			if (empty($color) && $type === 'Calendar') {
				$color = '#2e7d32';
				if (empty($textColor)) { $textColor = '#ffffff'; }
			}
			if (empty($color) && $type === 'Events') {
				$color = '#3f51b5';
				if (empty($textColor)) { $textColor = '#ffffff'; }
			}
			$targetModule = $request['targetModule'];
			$fieldName = $request['fieldname'];
			$isGroupId = $request['group'];
			$mapping = $request['mapping'];
			$conditions = $request['conditions'];
			$result = array();
			switch ($type) {
				case 'Events'			:	if($fieldName == 'date_start,due_date' || $userid) {
												$this->pullEvents($start, $end, $result,$userid,$color,$textColor,$isGroupId,$conditions);
											} else {
												$this->pullDetails($start, $end, $result, $type, $fieldName, $color, $textColor, $conditions);
											}
											break;
				case 'Calendar'			:	if($fieldName == 'date_start,due_date') {
												$this->pullTasks($start, $end, $result,$color,$textColor);
											} else {
												$this->pullDetails($start, $end, $result, $type, $fieldName, $color, $textColor);
											}
											break;
				case 'MultipleEvents'	:	$this->pullMultipleEvents($start,$end, $result,$mapping);break;
				case $type				:	$this->pullDetails($start, $end, $result, $type, $fieldName, $color, $textColor);break;
			}
			return json_encode($result);
		} catch (Exception $ex) {
			return $ex->getMessage();
		}
	}

	private function valForSql($value) {
		return Vtiger_Util_Helper::validateStringForSql($value);
	}

	protected function pullDetails($start, $end, &$result, $type, $fieldName, $color = null, $textColor = 'white', $conditions = '') {
		//+angelo
		$start = DateTimeField::convertToDBFormat($start);
		$end = DateTimeField::convertToDBFormat($end);
		//-angelo
		$moduleModel = Vtiger_Module_Model::getInstance($type);
		$nameFields = $moduleModel->getNameFields();
		foreach($nameFields as $i => $nameField) {
			$fieldInstance = $moduleModel->getField($nameField);
			if(!$fieldInstance->isViewable()) {
				unset($nameFields[$i]);
			}
		}
		$nameFields = array_values($nameFields);
		$selectFields = implode(',', $nameFields);		
		$fieldsList = explode(',', $fieldName);
		if(php7_count($fieldsList) == 2) {
			$db = PearDatabase::getInstance();
			$user = Users_Record_Model::getCurrentUserModel();
			$userAndGroupIds = array_merge(array($user->getId()),$this->getGroupsIdsForUsers($user->getId()));
			$queryGenerator = new QueryGenerator($moduleModel->get('name'), $user);
			$meta = $queryGenerator->getMeta($moduleModel->get('name'));

			$queryGenerator->setFields(array_merge(array_merge($nameFields, array('id')), $fieldsList));
			$query = $queryGenerator->getQuery();
			$startDateColumn = Vtiger_Util_Helper::validateStringForSql($fieldsList[0]);
			$endDateColumn = Vtiger_Util_Helper::validateStringForSql($fieldsList[1]);
			$query.= " AND (($startDateColumn >= ? AND $endDateColumn < ?) OR ($endDateColumn >= ?)) ";
			$params = array($start,$end,$start);
			$query.= " AND vtiger_crmentity.smownerid IN (".generateQuestionMarks($userAndGroupIds).")";
			$params = array_merge($params, $userAndGroupIds);
			$queryResult = $db->pquery($query, $params);

			$records = array();
			while($rowData = $db->fetch_array($queryResult)) {
				$records[] = DataTransform::sanitizeDataWithColumn($rowData, $meta);
			}
		} else {
			if($fieldName == 'birthday') {
				$startDateComponents = explode('-', $start);
				$endDateComponents = explode('-', $end);

				$year = $startDateComponents[0];
				$db = PearDatabase::getInstance();
				$user = Users_Record_Model::getCurrentUserModel();
				$userAndGroupIds = array_merge(array($user->getId()),$this->getGroupsIdsForUsers($user->getId()));
				$queryGenerator = new QueryGenerator($moduleModel->get('name'), $user);
				$meta = $queryGenerator->getMeta($moduleModel->get('name'));

				$queryGenerator->setFields(array_merge(array_merge($nameFields, array('id')), $fieldsList));
				$query = $queryGenerator->getQuery();
				$query.= " AND ((CONCAT(?, date_format(birthday,'%m-%d')) >= ? AND CONCAT(?, date_format(birthday,'%m-%d')) <= ? )";
				$params = array("$year-",$start,"$year-",$end);
				$endDateYear = $endDateComponents[0]; 
				if ($year !== $endDateYear) {
					$query .= " OR (CONCAT(?, date_format(birthday,'%m-%d')) >= ?  AND CONCAT(?, date_format(birthday,'%m-%d')) <= ? )"; 
					$params = array_merge($params,array("$endDateYear-",$start,"$endDateYear-",$end));
				} 
				$query .= ")";
				$query.= " AND vtiger_crmentity.smownerid IN (".  generateQuestionMarks($userAndGroupIds).")";
				$params = array_merge($params,$userAndGroupIds);
				$queryResult = $db->pquery($query, $params);
				$records = array();
				while($rowData = $db->fetch_array($queryResult)) {
					$records[] = DataTransform::sanitizeDataWithColumn($rowData, $meta);
				}
			} else {
				$query = "SELECT $selectFields, $fieldsList[0] FROM $type";
				$query.= " WHERE $fieldsList[0] >= '$start' AND $fieldsList[0] <= '$end' ";


				if(!empty($conditions)) {
					$conditions = Zend_Json::decode(Zend_Json::decode($conditions));
					$query .=  'AND '.$this->generateCalendarViewConditionQuery($conditions);
				}

				if($type == 'PriceBooks') {
					$records = $this->queryForRecords($query, false);
				} else {
					$records = $this->queryForRecords($query);
				}
			}
		}
		foreach ($records as $record) {
			$item = array();
			list ($modid, $crmid) = vtws_getIdComponents($record['id']);
			$item['id'] = $crmid;
			$item['title'] = decode_html($record[$nameFields[0]]);
			if(php7_count($nameFields) > 1) {
				$item['title'] = decode_html(trim($record[$nameFields[0]].' '.$record[$nameFields[1]]));
			}
			if(!empty($record[$fieldsList[0]])) {
				$item['start'] = $record[$fieldsList[0]];
			} else {
				$item['start'] = $record[$fieldsList[1]];
			}
			if(php7_count($fieldsList) == 2) {
				$item['end'] = $record[$fieldsList[1]];
			}
			if($fieldName == 'birthday') {
				$recordDateTime = new DateTime($record[$fieldName]); 

				$calendarYear = $year; 
				if($recordDateTime->format('m') < $startDateComponents[1]) { 
						$calendarYear = $endDateYear; 
				} 
				$recordDateTime->setDate($calendarYear, $recordDateTime->format('m'), $recordDateTime->format('d'));
				$item['start'] = $recordDateTime->format('Y-m-d');
			}

			$urlModule = $type;
			if ($urlModule === 'Events') {
				$urlModule = 'Calendar';
			}
			$item['url']   = sprintf('index.php?module='.$urlModule.'&view=Detail&record=%s', $crmid);
			$item['color'] = $color;
			$item['textColor'] = $textColor;
			$item['module'] = $moduleModel->getName();
			$item['sourceModule'] = $moduleModel->getName();
			$item['fieldName'] = $fieldName;
			$item['conditions'] = '';
			$item['end'] = date('Y-m-d', strtotime((isset($item['end']) ? $item['end']: $item['start']).' +1day'));
                        if(!empty($conditions)) {
                            $item['conditions'] = Zend_Json::encode(Zend_Json::encode($conditions));
                        }
                        $result[] = $item;
		}
	}

	protected function generateCalendarViewConditionQuery($conditions) {
		$conditionQuery = $operator = '';
		switch ($conditions['operator']) {
			case 'e' : $operator = '=';
		}

		if(!empty($operator) && !empty($conditions['fieldname']) && !empty($conditions['value'])) {
			$fieldname = vtlib_purifyForSql($conditions['fieldname']);
			if (empty($fieldname)) throw new Exception('Invalid fieldname.');
			$conditionQuery = ' '.$fieldname.$operator.'\'' .Vtiger_Functions::realEscapeString($conditions['value']).'\' ';
		}
		return $conditionQuery;
	}

	protected function getGroupsIdsForUsers($userId) {
		vimport('~~/include/utils/GetUserGroups.php');

		$userGroupInstance = new GetUserGroups();
		$userGroupInstance->getAllUserGroups($userId);
		return $userGroupInstance->user_groups;
	}

	protected function queryForRecords($query, $onlymine=true) {
		$user = Users_Record_Model::getCurrentUserModel();
		if ($onlymine) {
			$groupIds = $this->getGroupsIdsForUsers($user->getId());
			$groupWsIds = array();
			foreach($groupIds as $groupId) {
				$groupWsIds[] = vtws_getWebserviceEntityId('Groups', $groupId);
			}
			$userwsid = vtws_getWebserviceEntityId('Users', $user->getId());
			$userAndGroupIds = array_merge(array($userwsid),$groupWsIds);
			$query .= " AND assigned_user_id IN ('".implode("','",$userAndGroupIds)."')";
		}
		// TODO take care of pulling 100+ records
		return vtws_query($query.';', $user);
	}

	protected function pullEvents($start, $end, &$result, $userid = false, $color = null, $textColor = 'white', $isGroupId = false, $conditions = '') {
		$dbStartDateOject = DateTimeField::convertToDBTimeZone($start);
		$dbStartDateTime = $dbStartDateOject->format('Y-m-d H:i:s');
		$dbStartDateTimeComponents = explode(' ', $dbStartDateTime);
		$dbStartDate = $dbStartDateTimeComponents[0];

		$dbEndDateObject = DateTimeField::convertToDBTimeZone($end);
		$dbEndDateTime = $dbEndDateObject->format('Y-m-d H:i:s');

		$currentUser = Users_Record_Model::getCurrentUserModel();
		$db = PearDatabase::getInstance();
		$groupsIds = Vtiger_Util_Helper::getGroupsIdsForUsers($currentUser->getId());
		require('user_privileges/user_privileges_'.$currentUser->id.'.php');
		require('user_privileges/sharing_privileges_'.$currentUser->id.'.php');

		$moduleModel = Vtiger_Module_Model::getInstance('Events');
		if($userid && !$isGroupId){
			$focus = new Users();
			$focus->id = $userid;
			$focus->retrieve_entity_info($userid, 'Users');
			$user = Users_Record_Model::getInstanceFromUserObject($focus);
			$userName = $user->getName();
			$queryGenerator = new QueryGenerator($moduleModel->get('name'), $user);
		}else{
			$queryGenerator = new QueryGenerator($moduleModel->get('name'), $currentUser);
		}

		$queryGenerator->setFields(array('subject', 'eventstatus', 'visibility','date_start','time_start','due_date','time_end','assigned_user_id','id','activitytype','recurringtype'));
		$query = $queryGenerator->getQuery();

		$query.= " AND vtiger_activity.activitytype NOT IN ('Emails','Task') AND ";
		$hideCompleted = $currentUser->get('hidecompletedevents');
		if($hideCompleted)
			$query.= "vtiger_activity.eventstatus != 'HELD' AND ";

		if(!empty($conditions)) {
			$conditions = Zend_Json::decode(Zend_Json::decode($conditions));
			$query .=  $this->generateCalendarViewConditionQuery($conditions).'AND ';
		}
		$query.= " ((concat(date_start, '', time_start)  >= ? AND concat(due_date, '', time_end) < ? ) OR ( due_date >= ? ))";
		$params=array($dbStartDateTime,$dbEndDateTime,$dbStartDate);
		if(empty($userid)){
			$eventUserId  = $currentUser->getId();
		}else{
			$eventUserId = $userid;
		}
		$userIds = array_merge(array($eventUserId), $this->getGroupsIdsForUsers($eventUserId));
		
		$query.= " AND vtiger_crmentity.smownerid IN (".  generateQuestionMarks($userIds).")";
		$params= array_merge($params,$userIds);
		$queryResult = $db->pquery($query, $params);

		while($record = $db->fetchByAssoc($queryResult)){
			$item = array();
			$crmid = $record['activityid'];
			$visibility = $record['visibility'];
			$activitytype = $record['activitytype'];
			$status = $record['eventstatus'];
			$ownerId = $record['smownerid'];
			$item['id'] = $crmid;
			$item['visibility'] = $visibility;
			$item['activitytype'] = $activitytype;
			$item['status'] = $status;
			$recordBusy = true;
			if(in_array($ownerId, $groupsIds)) {
				$recordBusy = false;
			} else if($ownerId == $currentUser->getId()){
				$recordBusy = false;
			}
			// if the user is having view all permission then it should show the record
			// as we are showing in detail view
			if($profileGlobalPermission[1] ==0 || $profileGlobalPermission[2] ==0) {
				$recordBusy = false;
			}

			if(!$currentUser->isAdminUser() && $visibility == 'Private' && $userid && $userid != $currentUser->getId() && $recordBusy) {
				$item['title'] = decode_html($userName).' - '.decode_html(vtranslate('Busy','Events')).'*';
				$item['url']   = '';
			} else {
				$item['title'] = decode_html($record['subject']).' - ('.decode_html(vtranslate($record['eventstatus'],'Calendar')).')';
				$item['url']   = sprintf('index.php?module=Calendar&view=Detail&record=%s', $crmid);
			}

			// All-day: giống Task — time_start 00:00, time_end 23:59 → allDay true, start/end date-only
			$timeStart = isset($record['time_start']) ? trim($record['time_start']) : '';
			$timeEnd = isset($record['time_end']) ? trim($record['time_end']) : '';
			$isAllDay = (empty($timeStart) || $timeStart === '00:00:00' || $timeStart === '00:00') && (empty($timeEnd) || $timeEnd === '23:59:59' || $timeEnd === '23:59:00' || $timeEnd === '23:59');

			$dateTimeFieldInstance = new DateTimeField($record['date_start'].' '.$record['time_start']);
			$userDateTimeString = $dateTimeFieldInstance->getDisplayDateTimeValue($currentUser);
			$dateTimeComponents = explode(' ',$userDateTimeString);
			$dateComponent = isset($dateTimeComponents[0]) ? $dateTimeComponents[0] : '';
			$startDateYmd = DateTimeField::__convertToDBFormat($dateComponent, $currentUser->get('date_format'));
			$startTimePart = isset($dateTimeComponents[1]) ? $dateTimeComponents[1] : '';

			$dateTimeFieldInstanceEnd = new DateTimeField($record['due_date'].' '.$record['time_end']);
			$userDateTimeStringEnd = $dateTimeFieldInstanceEnd->getDisplayDateTimeValue($currentUser);
			$dateTimeComponentsEnd = explode(' ',$userDateTimeStringEnd);
			$dateComponentEnd = isset($dateTimeComponentsEnd[0]) ? $dateTimeComponentsEnd[0] : $record['due_date'];
			$endDateYmd = DateTimeField::__convertToDBFormat($dateComponentEnd, $currentUser->get('date_format'));
			$endTimePart = isset($dateTimeComponentsEnd[1]) ? $dateTimeComponentsEnd[1] : '';

			if (!$isAllDay && $startTimePart !== '' && $endTimePart !== '') {
				$item['start'] = $startDateYmd . ' ' . $startTimePart;
				$item['end'] = $endDateYmd . ' ' . $endTimePart;
				$item['allDay'] = false;
			} else {
				// All-day: gửi end inclusive (due_date) để tránh client hiển thị thêm 1 ngày
				$item['start'] = $startDateYmd;
				$item['end'] = $endDateYmd;
				$item['allDay'] = true;
			}

			$item['className'] = $cssClass;
			$item['color'] = !empty($color) ? $color : '#3f51b5';
			$item['textColor'] = !empty($textColor) ? $textColor : '#ffffff';
			$item['module'] = $moduleModel->getName();
			$recurringCheck = false;
			if($record['recurringtype'] != '' && $record['recurringtype'] != '--None--') {
				$recurringCheck = true;
			}
			$item['recurringcheck'] = $recurringCheck;
			$item['userid'] = $eventUserId;
			$item['fieldName'] = 'date_start,due_date';
			$item['conditions'] = '';
			if(!empty($conditions)) {
				$item['conditions'] = Zend_Json::encode(Zend_Json::encode($conditions));
			}
			$result[] = $item;
		}
	}

	protected function pullMultipleEvents($start, $end, &$result, $data) {

		foreach ($data as $id=>$backgroundColorAndTextColor) {
			$userEvents = array();
			$colorComponents = explode(',',$backgroundColorAndTextColor);
			$this->pullEvents($start, $end, $userEvents ,$id, $colorComponents[0], $colorComponents[1], $colorComponents[2]);
			$result[$id] = $userEvents;
		}
	}

	protected function pullTasks($start, $end, &$result, $color = null,$textColor = 'white') {
		$user = Users_Record_Model::getCurrentUserModel();
		$db = PearDatabase::getInstance();

		$moduleModel = Vtiger_Module_Model::getInstance('Calendar');
		$userAndGroupIds = array_merge(array($user->getId()),$this->getGroupsIdsForUsers($user->getId()));
		$queryGenerator = new QueryGenerator($moduleModel->get('name'), $user);

		$queryGenerator->setFields(array('activityid','subject', 'taskstatus','activitytype', 'date_start','time_start','due_date','time_end','id'));
		$query = $queryGenerator->getQuery();

		$currentUser = Users_Record_Model::getCurrentUserModel();
		$query.= " AND vtiger_activity.activitytype = 'Task' AND ";
		$hideCompleted = $currentUser->get('hidecompletedevents');
		if($hideCompleted)
			$query.= "(vtiger_activity.status IS NULL OR vtiger_activity.status != 'Completed') AND ";
		// Bao gồm task có due_date NULL (chỉ có date_start) hoặc trong khoảng ngày
		$query.= " ((date_start >= ? AND (due_date IS NULL OR due_date < ?)) OR (due_date IS NOT NULL AND due_date >= ?))";

		//+angelo
		$start = DateTimeField::__convertToDBFormat($start, $user->get('date_format'));
		$end = DateTimeField::__convertToDBFormat($end, $user->get('date_format'));
		//-angelo
		$params=array($start,$end,$start);
		$userIds = $userAndGroupIds;
		$query.= " AND vtiger_crmentity.smownerid IN (".generateQuestionMarks($userIds).")";
		$params=array_merge($params,$userIds);
		$queryResult = $db->pquery($query,$params);

		while($record = $db->fetchByAssoc($queryResult)){
			$item = array();
			$crmid = $record['activityid'];
			// DB có thể trả về 'status' hoặc 'taskstatus' tùy QueryGenerator
			$taskStatus = isset($record['status']) ? $record['status'] : (isset($record['taskstatus']) ? $record['taskstatus'] : '');
			$item['title'] = decode_html($record['subject']).' - ('.decode_html(vtranslate($taskStatus,'Calendar')).')';
			$item['status'] = $taskStatus;
			$item['activitytype'] = $record['activitytype'];
			$item['id'] = $crmid;
			// Dùng currentUser để timezone/giờ hiển thị khớp form và màu vẽ trên lịch
			$timeStart = isset($record['time_start']) ? trim($record['time_start']) : '';
			$timeEnd = isset($record['time_end']) ? trim($record['time_end']) : '';
			$isAllDay = (empty($timeStart) || $timeStart === '00:00:00' || $timeStart === '00:00') && (empty($timeEnd) || $timeEnd === '23:59:59' || $timeEnd === '23:59:00' || $timeEnd === '23:59');

			$dateTimeFieldInstance = new DateTimeField($record['date_start'].' '.$record['time_start']);
			$userDateTimeString = $dateTimeFieldInstance->getDisplayDateTimeValue($currentUser);
			$dateTimeComponents = explode(' ', $userDateTimeString);
			$dateComponent = isset($dateTimeComponents[0]) ? $dateTimeComponents[0] : '';
			$startDateYmd = DateTimeField::__convertToDBFormat($dateComponent, $currentUser->get('date_format'));
			$startTimePart = isset($dateTimeComponents[1]) ? $dateTimeComponents[1] : '';

			$dueDate = isset($record['due_date']) ? $record['due_date'] : $record['date_start'];
			$timeEndVal = isset($record['time_end']) ? $record['time_end'] : $record['time_start'];
			$dateTimeFieldInstanceEnd = new DateTimeField($dueDate.' '.$timeEndVal);
			$userDateTimeStringEnd = $dateTimeFieldInstanceEnd->getDisplayDateTimeValue($currentUser);
			$dateTimeComponentsEnd = explode(' ', $userDateTimeStringEnd);
			$dateComponentEnd = isset($dateTimeComponentsEnd[0]) ? $dateTimeComponentsEnd[0] : $dueDate;
			$endDateYmd = DateTimeField::__convertToDBFormat($dateComponentEnd, $currentUser->get('date_format'));
			$endTimePart = isset($dateTimeComponentsEnd[1]) ? $dateTimeComponentsEnd[1] : '';

			if (!$isAllDay && $startTimePart !== '' && $endTimePart !== '') {
				// Task có giờ bắt đầu/kết thúc → hiển thị trên lưới giờ (allDay: false)
				$item['start'] = $startDateYmd . ' ' . $startTimePart;
				$item['end'] = $endDateYmd . ' ' . $endTimePart;
				$item['allDay'] = false;
			} else {
				// Task all-day: gửi end inclusive (due_date), client sẽ +1 ngày cho FullCalendar (exclusive)
				$item['start'] = $startDateYmd;
				$item['end'] = $endDateYmd;
				$item['allDay'] = true;
			}

			$item['url']   = sprintf('index.php?module=Calendar&view=Detail&record=%s', $crmid);
			$item['color'] = !empty($color) ? $color : '#2e7d32';
			$item['textColor'] = !empty($textColor) ? $textColor : '#ffffff';
			$item['module'] = $moduleModel->getName();
			$item['fieldName'] = 'date_start,due_date';
			$item['conditions'] = '';
			$result[] = $item;
		}
	}

}
