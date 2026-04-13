<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Calendar_DragDropAjax_Action extends Calendar_SaveAjax_Action {

	function __construct() {
		$this->exposeMethod('updateDeltaOnResize');
		$this->exposeMethod('updateDeltaOnDrop');
	}

	public function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		if(!empty($mode) && $this->isMethodExposed($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}

	}

	public function updateDeltaOnResize(Vtiger_Request $request){
		$moduleName = $request->getModule();
		$activityType = $request->get('activitytype');
		$recordId = $request->get('id');
		$dayDelta = $request->get('dayDelta');
		$minuteDelta = $request->get('minuteDelta');
		$secondsDelta = $request->get('secondsDelta',NULL);
		$recurringEditMode = $request->get('recurringEditMode');
		$newDateStart = $request->get('new_date_start');
		$newDueDate = $request->get('new_due_date');
		$newTimeStart = $request->get('new_time_start');
		$newTimeEnd = $request->get('new_time_end');

		$actionname = 'EditView';
		$response = new Vtiger_Response();
		try {
			if(isPermitted($moduleName, $actionname, $recordId) === 'no'){
				$result = array('ispermitted'=>false,'error'=>false);
				$response->setResult($result);
			} else {
				$result = array('ispermitted'=>true,'error'=>false);
				$record = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
				$record->set('mode','edit');

				// Client gửi ngày/giờ mới trực tiếp sau khi FullCalendar mutate (tránh strtotime(secondsDelta) và lệch timezone).
				if (!empty($newDateStart) && !empty($newDueDate)) {
					$record->set('date_start', $newDateStart);
					$record->set('due_date', $newDueDate);
					if ($newTimeStart !== null && $newTimeStart !== '') {
						$record->set('time_start', Vtiger_Time_UIType::getTimeValueWithSeconds($newTimeStart));
					}
					if ($newTimeEnd !== null && $newTimeEnd !== '') {
						$record->set('time_end', Vtiger_Time_UIType::getTimeValueWithSeconds($newTimeEnd));
					}
					$startDateTime = new DateTime(trim($newDateStart) . ' ' . trim((string)$record->get('time_start')));
					$endDateTime = new DateTime(trim($newDueDate) . ' ' . trim((string)$record->get('time_end')));
					if ($startDateTime <= $endDateTime) {
						$this->setRecurrenceInfo($record);
						$record->save();
					} else {
						$result['error'] = true;
					}
					$result['recurringRecords'] = false;
				} else {
					$startDateTime = $this->getFormattedDateTime($record->get('date_start'), $record->get('time_start'));
					$oldDateTime = $this->getFormattedDateTime($record->get('due_date'), $record->get('time_end'));

					$resultDateTime = $this->changeDateTime($oldDateTime,$dayDelta,$minuteDelta,$secondsDelta);
					$interval = strtotime($resultDateTime) - strtotime($startDateTime);

					if(!empty($recurringEditMode) && $recurringEditMode != 'current') {
						$recurringRecordsList = $record->getRecurringRecordsList();
						foreach($recurringRecordsList as $parent=>$childs) {
							$parentRecurringId = $parent;
							$childRecords = $childs;
						}
						if($recurringEditMode == 'future') {
							$parentKey = array_keys($childRecords, $recordId);
							$childRecords = array_slice($childRecords, $parentKey[0]);
						}
						foreach($childRecords as $childId) {
							$recordModel = Vtiger_Record_Model::getInstanceById($childId, 'Events');
							$recordModel->set('mode','edit');

							$startDateTime = $this->getFormattedDateTime($recordModel->get('date_start'), $recordModel->get('time_start'));
							$dueDate = strtotime($startDateTime) + $interval;
							$formatDate = date("Y-m-d H:i:s", $dueDate);
							$parts = explode(' ',$formatDate);
							$startDateTime = new DateTime($startDateTime);

							$recordModel->set('due_date',$parts[0]);
							if($activityType != 'Task') {
								$recordModel->set('time_end',$parts[1]);
							}

							$endDateTime = $this->getFormattedDateTime($recordModel->get('due_date'), $recordModel->get('time_end'));
							$endDateTime = new DateTime($endDateTime);

							if($startDateTime <= $endDateTime) {
								$this->setRecurrenceInfo($recordModel);
								$recordModel->save();
							} else {
								$result['error'] = true;
							}
						}
						$result['recurringRecords'] = true;
					} else {
						$oldDateTime = $this->getFormattedDateTime($record->get('due_date'), $record->get('time_end'));
						$resultDateTime = $this->changeDateTime($oldDateTime,$dayDelta,$minuteDelta,$secondsDelta);
						$parts = explode(' ',$resultDateTime);
						$record->set('due_date',$parts[0]);
						if($activityType != 'Task') {
							$record->set('time_end',$parts[1]);
						}

						$startDateTime = $this->getFormattedDateTime($record->get('date_start'), $record->get('time_start'));
						$startDateTime = new DateTime($startDateTime);

						$endDateTime = $this->getFormattedDateTime($record->get('due_date'), $record->get('time_end'));
						$endDateTime = new DateTime($endDateTime);
						if($startDateTime <= $endDateTime) {
							$this->setRecurrenceInfo($record);
							$record->save();
						} else {
							$result['error'] = true;
						}
						$result['recurringRecords'] = false;
					}
				}

				$response->setResult($result);
			}
		} catch (DuplicateException $e) {
			$response->setError($e->getMessage(), $e->getDuplicationMessage(), $e->getMessage());
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	function setRecurrenceInfo($recordModel) {
		//Activity.php insertIntoRecurringTable api depends on $_REQUEST mode edit
		$_REQUEST['mode'] = 'edit';
		// Luôn đẩy đúng giá trị đang trên record (định dạng DB). convertToUserTimeZone() coi chuỗi
		// "Y-m-d H:i:s" theo timezone DB/PHP — sau kéo thả từ FullCalendar dễ bị lệch −1 ngày / sai giờ.
		$_REQUEST['date_start'] = $recordModel->get('date_start');
		$_REQUEST['due_date'] = $recordModel->get('due_date');
		$_REQUEST['time_start'] = substr((string)$recordModel->get('time_start'), 0, 5);
		$_REQUEST['time_end'] = substr((string)$recordModel->get('time_end'), 0, 5);

		$recurringInfo = $recordModel->getRecurrenceInformation();
		$_REQUEST['recurringcheck'] = $recurringInfo['recurringcheck'];
		$_REQUEST['repeat_frequency'] = $recurringInfo['repeat_frequency'];
		$_REQUEST['recurringtype'] = $recurringInfo['eventrecurringtype'];
		$_REQUEST['calendar_repeat_limit_date'] = $recurringInfo['recurringenddate'];

		if ($recurringInfo['eventrecurringtype'] == 'Weekly') {
			$_REQUEST['sun_flag'] = $recurringInfo['week0'];
			$_REQUEST['mon_flag'] = $recurringInfo['week1'];
			$_REQUEST['tue_flag'] = $recurringInfo['week2'];
			$_REQUEST['wed_flag'] = $recurringInfo['week3'];
			$_REQUEST['thu_flag'] = $recurringInfo['week4'];
			$_REQUEST['fri_flag'] = $recurringInfo['week5'];
			$_REQUEST['sat_flag'] = $recurringInfo['week6'];
		}

		if ($recurringInfo['eventrecurringtype'] == 'Monthly') {
			if ($recurringInfo['repeatMonth'] == 'date') {
				$_REQUEST['repeatMonth'] = $recurringInfo['repeatMonth'];
				$_REQUEST['repeatMonth_date'] = $recurringInfo['repeatMonth_date'];
			} else if ($recurringInfo['repeatMonth'] == 'day') {
				$_REQUEST['repeatMonth_daytype'] = $recurringInfo['repeatMonth_daytype'];
				$_REQUEST['repeatMonth_day'] = $recurringInfo['repeatMonth_day'];
			}
		}
	}

	public function updateDeltaOnDrop(Vtiger_Request $request){
		$moduleName = $request->getModule();
		$activityType = $request->get('activitytype');
		$recordId = $request->get('id');
		$dayDelta = $request->get('dayDelta');
		$minuteDelta = $request->get('minuteDelta');
		$secondsDelta = $request->get('secondsDelta');
		$recurringEditMode = $request->get('recurringEditMode');
		$newDateStart = $request->get('new_date_start');
		$newDueDate = $request->get('new_due_date');
		$newTimeStart = $request->get('new_time_start');
		$newTimeEnd = $request->get('new_time_end');
		$actionname = 'EditView';

		$response = new Vtiger_Response();
		try {
			if(isPermitted($moduleName, $actionname, $recordId) === 'no'){
				$result = array('ispermitted'=>false);
				$response->setResult($result);
			} else {
				$result = array('ispermitted'=>true);
				$record = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
				$record->set('mode','edit');

				// All-day Task: client gửi ngày mới trực tiếp (tránh lệch khi kéo)
				if (!empty($newDateStart) && !empty($newDueDate)) {
					$record->set('date_start', $newDateStart);
					$record->set('due_date', $newDueDate);
					if ($newTimeStart !== null && $newTimeStart !== '') {
						$record->set('time_start', Vtiger_Time_UIType::getTimeValueWithSeconds($newTimeStart));
					}
					if ($newTimeEnd !== null && $newTimeEnd !== '') {
						$record->set('time_end', Vtiger_Time_UIType::getTimeValueWithSeconds($newTimeEnd));
					}
					$startDT = new DateTime(trim($newDateStart) . ' ' . trim((string)$record->get('time_start')));
					$endDT = new DateTime(trim($newDueDate) . ' ' . trim((string)$record->get('time_end')));
					if ($startDT <= $endDT) {
						$this->setRecurrenceInfo($record);
						$record->save();
						$result['recurringRecords'] = false;
					} else {
						$result['error'] = true;
					}
				} else {
					$oldStartDateTime = $this->getFormattedDateTime($record->get('date_start'), $record->get('time_start'));
					$resultDateTime = $this->changeDateTime($oldStartDateTime, $dayDelta, $minuteDelta, $secondsDelta);
					$startDateInterval = strtotime($resultDateTime) - strtotime($oldStartDateTime);

					$oldEndDateTime = $this->getFormattedDateTime($record->get('due_date'), $record->get('time_end'));
					$resultDateTime = $this->changeDateTime($oldEndDateTime, $dayDelta, $minuteDelta, $secondsDelta);
					$endDateInterval = strtotime($resultDateTime) - strtotime($oldEndDateTime);

					if (!empty($recurringEditMode) && $recurringEditMode != 'current') {
						$recurringRecordsList = $record->getRecurringRecordsList();
						foreach ($recurringRecordsList as $parent => $childs) {
							$parentRecurringId = $parent;
							$childRecords = $childs;
						}
						if ($recurringEditMode == 'future') {
							$parentKey = array_keys($childRecords, $recordId);
							$childRecords = array_slice($childRecords, $parentKey[0]);
						}
						foreach ($childRecords as $childId) {
							$recordModel = Vtiger_Record_Model::getInstanceById($childId, 'Events');
							$recordModel->set('mode', 'edit');

							$startDateTime = $this->getFormattedDateTime($recordModel->get('date_start'), $recordModel->get('time_start'));
							$startDate = strtotime($startDateTime) + $startDateInterval;
							$formatStartDate = date("Y-m-d H:i:s", $startDate);
							$parts = explode(' ', $formatStartDate);
							$startDateTime = new DateTime($startDateTime);

							$recordModel->set('date_start', $parts[0]);
							if ($activityType != 'Task')
								$recordModel->set('time_start', $parts[1]);

							$endDateTime = $this->getFormattedDateTime($recordModel->get('due_date'), $recordModel->get('time_end'));
							$endDate = strtotime($endDateTime) + $endDateInterval;
							$formatEndDate = date("Y-m-d H:i:s", $endDate);
							$endDateParts = explode(' ', $formatEndDate);
							$endDateTime = new DateTime($endDateTime);
							$recordModel->set('due_date', $endDateParts[0]);
							if ($activityType != 'Task')
								$recordModel->set('time_end', $endDateParts[1]);

							$this->setRecurrenceInfo($recordModel);
							$recordModel->save();
						}
						$result['recurringRecords'] = true;
					} else {
						$oldStartDateTime = $this->getFormattedDateTime($record->get('date_start'), $record->get('time_start'));
						$resultDateTime = $this->changeDateTime($oldStartDateTime,$dayDelta,$minuteDelta,$secondsDelta);
						$parts = explode(' ',$resultDateTime);
						$record->set('date_start',$parts[0]);
						$record->set('time_start',$parts[1]);

						$oldEndDateTime = $this->getFormattedDateTime($record->get('due_date'), $record->get('time_end'));
						$resultDateTime = $this->changeDateTime($oldEndDateTime,$dayDelta,$minuteDelta,$secondsDelta);
						$parts = explode(' ',$resultDateTime);
						$record->set('due_date',$parts[0]);
						if($activityType != 'Task') {
							$record->set('time_end',$parts[1]);
						}

						$this->setRecurrenceInfo($record);
						$record->save();
						$result['recurringRecords'] = false;
					}
				}
			}
			$response->setResult($result);
		} catch (DuplicateException $e) {
			$response->setError($e->getMessage(), $e->getDuplicationMessage(), $e->getMessage());
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}
	/* *
	 * Function adds days and minutes to datetime string
	 */
	public function changeDateTime($datetime,$daysToAdd,$minutesToAdd,$secondsDelta=NULL){
		$datetime = strtotime($datetime);
		if(!$secondsDelta) {
			$secondsDelta = (60*$minutesToAdd)+(24*60*60*$daysToAdd);
		}
		$futureDate = $datetime+$secondsDelta;
		$formatDate = date("Y-m-d H:i:s", $futureDate);
		return $formatDate;
	}

}
?>
