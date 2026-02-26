<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Settings_Vtiger_AnnouncementSaveAjax_Action extends Settings_Vtiger_Basic_Action {
    
    public function process(Vtiger_Request $request) {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $annoucementModel = Settings_Vtiger_Announcement_Model::getInstanceByCreator($currentUser);
        $annoucementModel->set('announcement', $request->get('announcement'));
        if ($request->has('title')) {
            $annoucementModel->set('title', $request->get('title'));
        }
        if ($request->has('subscriber_ids')) {
            $annoucementModel->set('subscriber_ids', $request->get('subscriber_ids'));
        }
        if ($request->has('subscriber_group_ids')) {
            $annoucementModel->set('subscriber_group_ids', $request->get('subscriber_group_ids'));
        }
        $annoucementModel->save();
        $responce = new Vtiger_Response();
        $responce->setResult(array('success'=>true));
        $responce->emit();
    }
    
    public function validateRequest(Vtiger_Request $request) {
        $request->validateWriteAccess();
    }
}