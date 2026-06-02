<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

include_once 'include/utils/utils.php';

/**
 * Plans Module (entity)
 * High-level marketing plan aggregating Campaign performance.
 */
class Plans extends CRMEntity {
	var $log;
	var $db;

	var $moduleName = 'Plans';
	public static $moduleNameStatic = 'Plans';

	var $table_name = 'vtiger_plans';
	var $table_index = 'planid';

	var $tab_name = array('vtiger_crmentity', 'vtiger_plans', 'vtiger_planscf');
	var $tab_name_index = array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_plans' => 'planid',
		'vtiger_planscf' => 'planid',
	);

	var $entity_table = 'vtiger_crmentity';
	var $customFieldTable = array('vtiger_planscf', 'planid');

	var $column_fields = array();
	var $sortby_fields = array('planname', 'start_date', 'end_date', 'plan_status', 'createdtime');

	var $list_fields = array(
		'Plan Name' => array('plans' => 'planname'),
		'Status' => array('plans' => 'plan_status'),
		'Start Date' => array('plans' => 'start_date'),
		'End Date' => array('plans' => 'end_date'),
		'Owner' => array('crmentity' => 'smownerid'),
	);

	var $list_fields_name = array(
		'Plan Name' => 'planname',
		'Status' => 'plan_status',
		'Start Date' => 'start_date',
		'End Date' => 'end_date',
		'Owner' => 'assigned_user_id',
	);

	var $list_link_field = 'planname';

	var $search_fields = array(
		'Plan Name' => array('plans' => 'planname'),
	);

	var $search_fields_name = array(
		'Plan Name' => 'planname',
	);

	var $required_fields = array('planname', 'assigned_user_id');
	var $mandatory_fields = array('planname', 'assigned_user_id', 'createdtime', 'modifiedtime');
    
    function __construct() {
		$this->column_fields = getColumnFields(get_class($this));
		$this->db = PearDatabase::getInstance();
        global $log;
        $this->log = $log;
	}

	/**
	 * CRMEntity expects this hook to exist for entity modules.
	 * Plans aggregation is handled via PlansHandler events.
	 */
	function save_module($module) {
		// Auto-generate Plan Code if missing (requires vtiger_plans.plan_code)
		global $adb;
		if (!empty($this->id)) {
			$res = $adb->pquery("SELECT plan_code FROM vtiger_plans WHERE planid = ?", array($this->id));
			$code = ($res && $adb->num_rows($res) > 0) ? (string)$adb->query_result($res, 0, 'plan_code') : '';
			if (trim($code) === '') {
				$newCode = 'PL-' . str_pad((string)$this->id, 6, '0', STR_PAD_LEFT);
				$adb->pquery("UPDATE vtiger_plans SET plan_code = ? WHERE planid = ?", array($newCode, $this->id));
			}
		}
	}

	/**
	 * vtlib hook: register/unregister event handler on install/update.
	 */
	function vtlib_handler($moduleName, $eventType) {
		if ($eventType === 'module.postinstall' || $eventType === 'module.postupdate') {
			require_once 'vtlib/Vtiger/Event.php';
			$em = new VTEventsManager($this->db);
			$em->registerHandler('vtiger.entity.aftersave', 'modules/Plans/PlansHandler.php', 'PlansHandler');
			$em->registerHandler('vtiger.entity.afterdelete', 'modules/Plans/PlansHandler.php', 'PlansHandler');
		} elseif ($eventType === 'module.preuninstall') {
			require_once 'vtlib/Vtiger/Event.php';
			$em = new VTEventsManager($this->db);
			$em->unregisterHandler('PlansHandler');
		}
	}
}
