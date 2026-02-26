<?php
class Plans_Module_Model extends Vtiger_Module_Model {
    
    /**
     * Get module icon HTML
     */
    public function getModuleIcon() {
        $title = vtranslate('Plans', 'Plans');
        return "<i class='fa fa-calendar' title='$title'></i>";
    }
}
?>