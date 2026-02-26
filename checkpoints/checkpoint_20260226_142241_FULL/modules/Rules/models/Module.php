<?php
class Rules_Module_Model extends Vtiger_Module_Model {
    
    /**
     * Get module icon HTML
     */
    public function getModuleIcon() {
        $title = vtranslate('Rules', 'Rules');
        return "<i class='fa fa-gavel' title='$title'></i>";
    }
}
?>