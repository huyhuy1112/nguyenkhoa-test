<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * Placeholder module: History
 * This is a lightweight module for menu structure only.
 * No database tables, no business logic.
 */
class History extends CRMEntity {
    var $db, $log;
    var $column_fields = Array();
    
    /** Indicator if this is a custom module */
    var $IsCustomModule = true;
    
    /**
     * This module is NOT an entity type (no database tables)
     */
    var $isentitytype = false;
    
    function __construct() {
        global $log;
        $this->log = $log;
        $this->db = PearDatabase::getInstance();
    }
    
    /**
     * Invoked when special actions are performed on the module.
     */
    function vtlib_handler($modulename, $event_type) {
        // Placeholder module - no special handling needed
    }
}
?>