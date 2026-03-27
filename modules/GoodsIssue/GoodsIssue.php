<?php
/*+**********************************************************************************
 * Placeholder module: GoodsIssue
 * Label: Xuất kho
 */
class GoodsIssue extends CRMEntity {
    var $db, $log;
    var $column_fields = Array();
    var $IsCustomModule = true;
    var $isentitytype = false;
    
    function __construct() {
        global $log;
        $this->log = $log;
        $this->db = PearDatabase::getInstance();
    }
    
    function vtlib_handler($modulename, $event_type) {
		if (in_array($event_type, array('module.postinstall', 'module.postupdate', 'module.enabled'), true)) {
			require_once 'modules/GoodsIssue/helpers/WorkflowSetup.php';
			GoodsIssue_WorkflowSetup_Helper::runAll();
		}
    }
}
?>