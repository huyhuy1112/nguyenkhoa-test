<?php
class Evaluate_Module_Model extends Vtiger_Module_Model {
    
    /**
     * Get module icon HTML
     */
    public function getModuleIcon() {
        $title = vtranslate('Evaluate', 'Evaluate');
        return "<i class='fa fa-bar-chart' title='$title'></i>";
    }
}
?>