<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

vimport('~~/vtlib/Vtiger/Module.php');

/**
 * Schedule Module Model Class
 * Extends Calendar functionality with Google Calendar-like features
 */
class Schedule_Module_Model extends Calendar_Module_Model {

	/**
	 * Function returns the default view for the Schedule module
	 * @return <String>
	 */
	public function getDefaultViewName() {
		return $this->getCalendarViewName();
	}

	/**
	 * Function returns the calendar view name
	 * @return <String>
	 */
	public function getCalendarViewName() {
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$arrayofViews = array('ListView' => 'List', 'MyCalendar' => 'Calendar','SharedCalendar'=>'SharedCalendar');

		$calendarViewName = $currentUserModel->get('defaultcalendarview');
		if(array_key_exists($calendarViewName, $arrayofViews)) {
			$calendarViewName = $arrayofViews[$calendarViewName];
		}
		if(empty($calendarViewName)) {
			$calendarViewName = 'Calendar';
		}
		return $calendarViewName;
	}

	/**
	 *  Function returns the url for Calendar view
	 * @return <String>
	 */
	public function getCalendarViewUrl() {
		return 'index.php?module='.$this->get('name').'&view=Calendar';
	}

	/**
	 * Function returns the URL for creating Schedule Events
	 * @return <String>
	 */
	public function getCreateEventRecordUrl() {
		return 'index.php?module='.$this->get('name').'&view='.$this->getEditViewName().'&mode=Events';
	}

	/**
	 * Function returns the URL for creating Schedule Task
	 * @return <String>
	 */
	public function getCreateTaskRecordUrl() {
		return 'index.php?module='.$this->get('name').'&view='.$this->getEditViewName().'&mode=Calendar';
	}
}
