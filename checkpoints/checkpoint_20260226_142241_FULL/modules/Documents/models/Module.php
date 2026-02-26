<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Documents_Module_Model extends Vtiger_Module_Model {

	/**
	 * Functions tells if the module supports workflow
	 * @return boolean
	 */
	public function isWorkflowSupported() {
		return true;
	}

	/**
	 * Function to check whether the module is summary view supported
	 * @return <Boolean> - true/false
	 */
	public function isSummaryViewSupported() {
		return false;
	}
	
	/**
	 * Function returns the url which gives Documents that have Internal file upload
	 * @return string
	 */
	public function getInternalDocumentsURL() {
		return 'view=Popup&module=Documents&src_module=Emails&src_field=composeEmail';
	}

	/**
	 * Trả về folderid thực tế của folder Default trong DB.
	 * Default có thể có folderid=0 hoặc 1 tùy cách cài đặt.
	 */
	public static function getDefaultFolderId() {
		$db = PearDatabase::getInstance();
		$res = $db->pquery("SELECT folderid FROM vtiger_attachmentsfolder WHERE foldername = ? LIMIT 1", array('Default'));
		if ($db->num_rows($res) > 0) {
			return $db->query_result($res, 0, 'folderid');
		}
		return 0;
	}

	/**
	 * Function returns list of folders (chỉ folder user được phép xem)
	 * @return <Array> folder list
	 */
	public static function getAllFolders() {
		return Documents_Folder_Model::getAccessibleFolders();
	}

	/**
	 * Trả về danh sách owner id (user/group) mà user hiện tại được phép xem document theo quản lý – nhân sự, phòng ban.
	 * Admin xem tất cả; user thường chỉ xem: bản thân + cùng phòng ban + nhân viên báo cáo (reports_to).
	 * @return array|null Null nếu không áp dụng lọc (admin), ngược lại array id.
	 */
	public static function getAccessibleOwnerIdsForDocuments() {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if ($currentUser->isAdminUser()) {
			return null;
		}
		$db = PearDatabase::getInstance();
		$uid = (int) $currentUser->getId();
		$userRow = $db->pquery("SELECT department, reports_to_id FROM vtiger_users WHERE id = ? AND status = 'Active'", array($uid));
		if (!$db->num_rows($userRow)) {
			return array($uid);
		}
		$department = $db->query_result($userRow, 0, 'department');
		$reportsToId = $db->query_result($userRow, 0, 'reports_to_id');
		$ownerIds = array($uid);
		if ($department !== null && trim($department) !== '') {
			$res = $db->pquery("SELECT id FROM vtiger_users WHERE status = 'Active' AND TRIM(COALESCE(department,'')) = ?", array(trim($department)));
			for ($i = 0; $i < $db->num_rows($res); $i++) {
				$ownerIds[] = (int) $db->query_result($res, $i, 'id');
			}
		}
		if ($reportsToId) {
			$res = $db->pquery("SELECT id FROM vtiger_users WHERE status = 'Active' AND reports_to_id = ?", array($uid));
			for ($i = 0; $i < $db->num_rows($res); $i++) {
				$ownerIds[] = (int) $db->query_result($res, $i, 'id');
			}
		}
		$ownerIds = array_unique($ownerIds);
		return array_values($ownerIds);
	}

	/**
	 * Function to get list view query for popup window
	 * @param <String> $sourceModule Parent module
	 * @param <String> $field parent fieldname
	 * @param <Integer> $record parent id
	 * @param <String> $listQuery
	 * @return <String> Listview Query
	 */
	public function getQueryByModuleField($sourceModule, $field, $record, $listQuery) {
		if($sourceModule === 'Emails' && $field === 'composeEmail') {
			$condition = ' (( vtiger_notes.filelocationtype LIKE "%I%")) AND vtiger_notes.filename != "" AND vtiger_notes.filestatus = 1';
		} else {
            		$db = PearDatabase::getInstance();
			$condition = " vtiger_notes.notesid NOT IN (SELECT notesid FROM vtiger_senotesrel WHERE crmid = ?) AND vtiger_notes.filestatus = 1";
            		$condition = $db->convert2Sql($condition, array($record));
		}
		$pos = stripos($listQuery, 'where');
		if($pos) {
			$split = preg_split('/where/i', $listQuery);
			$overRideQuery = $split[0] . ' WHERE ' . $split[1] . ' AND ' . $condition;
		} else {
			$overRideQuery = $listQuery. ' WHERE ' . $condition;
		}
		return $overRideQuery;
	}

	/**
	 * Funtion that returns fields that will be showed in the record selection popup
	 * @return <Array of fields>
	 */
	public function getPopupViewFieldsList() { 
		$popupFileds = $this->getSummaryViewFieldsList();
		$reqPopUpFields = array('File Status' => 'filestatus', 
								'File Size' => 'filesize', 
								'File Location Type' => 'filelocationtype'); 
		foreach ($reqPopUpFields as $fieldLabel => $fieldName) {
			$fieldModel = Vtiger_Field_Model::getInstance($fieldName,$this); 
			if ($fieldModel->getPermissions('readonly')) { 
				$popupFileds[$fieldName] = $fieldModel; 
			}
		}
		return array_keys($popupFileds); 
	}

	/**
	 * Function to get the url for add folder from list view of the module
	 * @return <string> - url
	 */
	public function getAddFolderUrl() {
		return 'index.php?module='.$this->getName().'&view=AddFolder';
	}
	
	/**
	 * Function to get Alphabet Search Field 
	 */
	public function getAlphabetSearchField(){
		return 'notes_title';
	}
	
	/**
     * Function that returns related list header fields that will be showed in the Related List View
     * @return <Array> returns related fields list.
     */
	public function getRelatedListFields() {
		$relatedListFields = parent::getRelatedListFields();
		
		//Adding filestatus, filelocationtype in the related list to be used for file download
		$relatedListFields['filestatus'] = 'filestatus';
		$relatedListFields['filelocationtype'] = 'filelocationtype';
		
		return $relatedListFields;
	}
    
    /**
	* Function is used to give links in the All menu bar
	*/
	public function getQuickMenuModels() {
		if($this->isEntityModule()) {
			$moduleName = $this->getName();
            
			$createPermission = Users_Privileges_Model::isPermitted($moduleName, 'CreateView');
            if($createPermission) {
                $basicListViewLinks[] = array(
					'linktype' => 'LISTVIEW',
					'linklabel' => 'LBL_INTERNAL_DOCUMENT_TYPE',
					'linkurl' => 'javascript:Vtiger_Header_Js.getQuickCreateFormForModule("index.php?module=Documents&view=EditAjax&type=I","Documents")',
					'linkicon' => ''
				);
                $basicListViewLinks[] = array(
					'linktype' => 'LISTVIEW',
					'linklabel' => 'LBL_EXTERNAL_DOCUMENT_TYPE',
					'linkurl' => 'javascript:Vtiger_Header_Js.getQuickCreateFormForModule("index.php?module=Documents&view=EditAjax&type=E")',
					'linkicon' => ''
				);
                $basicListViewLinks[] = array(
					'linktype' => 'LISTVIEW',
					'linklabel' => 'LBL_WEBDOCUMENT_TYPE',
					'linkurl' => 'javascript:Vtiger_Header_Js.getQuickCreateFormForModule("index.php?module=Documents&view=EditAjax&type=W")',
					'linkicon' => ''
				);
            }
           
		}
		if($basicListViewLinks) {
			foreach($basicListViewLinks as $basicListViewLink) {
				if(is_array($basicListViewLink)) {
					$links[] = Vtiger_Link_Model::getInstanceFromValues($basicListViewLink);
				} else if(is_a($basicListViewLink, 'Vtiger_Link_Model')) {
					$links[] = $basicListViewLink;
				}
			}
		}
		return $links;
	}
    
    /*
     * Function to get supported utility actions for a module
     */
    function getUtilityActionsNames() {
        return array('Export');
    }

	public function getConfigureRelatedListFields() {
		$showRelatedFieldModel = $this->getHeaderAndSummaryViewFieldsList();
		$relatedListFields = array();
        $defaultFields = array();
		if(php7_count($showRelatedFieldModel) > 0) {
			foreach ($showRelatedFieldModel as $key => $field) {
				$relatedListFields[$field->get('column')] = $field->get('name');
			}
            $defaultFields = array(
                'title' => 'notes_title',
                'filename' => 'filename'
            );
		}

		foreach($defaultFields as $columnName => $fieldName) {
			if(!array_key_exists($columnName, $relatedListFields)) {
				$relatedListFields[$columnName] = $fieldName;
			}
		}
		return $relatedListFields;
	}

	public function isFieldsDuplicateCheckAllowed() {
		return false;
	}
}
