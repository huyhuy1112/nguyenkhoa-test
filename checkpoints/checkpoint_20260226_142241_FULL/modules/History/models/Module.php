<?php
class History_Module_Model extends Vtiger_Module_Model {
    
    /**
     * Get module icon HTML
     */
    public function getModuleIcon() {
        $title = vtranslate('History', 'History');
        return "<i class='fa fa-history' title='$title'></i>";
    }
}
?>