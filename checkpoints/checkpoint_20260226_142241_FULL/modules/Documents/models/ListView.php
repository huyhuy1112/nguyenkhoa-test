<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Documents_ListView_Model extends Vtiger_ListView_Model {

	/**
	 * Function to get the list of listview links for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associate array of Link Type to List of Vtiger_Link_Model instances
	 */
	public function getListViewLinks($linkParams) {
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$moduleModel = $this->getModule();

		$linkTypes = array('LISTVIEWBASIC', 'LISTVIEW', 'LISTVIEWSETTING');
		$links = Vtiger_Link_Model::getAllByType($moduleModel->getId(), $linkTypes, $linkParams);

		$createPermission = Users_Privileges_Model::isPermitted($moduleModel->getName(), 'CreateView');
		if($createPermission) {
			$folderId = $this->get('folder_id');
			$folderValue = $this->get('folder_value');
			$folderParam = '';
			if ($folderId !== '' && $folderId !== null) {
				$folderParam = '&folder_id=' . urlencode($folderId) . '&folder_value=' . urlencode($folderValue !== null ? $folderValue : '');
			}
            $vtigerDocumentTypes = array(
                array(
                    'type' => 'I',
                    'label' => 'LBL_INTERNAL_DOCUMENT_TYPE',
                    'url' => 'index.php?module=Documents&view=EditAjax&type=I' . $folderParam
                ),
                array(
                    'type' => 'E',
                    'label' => 'LBL_EXTERNAL_DOCUMENT_TYPE',
                    'url' => 'index.php?module=Documents&view=EditAjax&type=E' . $folderParam
                ),
                array(
                    'type' => 'W',
                    'label' => 'LBL_WEBDOCUMENT_TYPE',
                    'url' => 'index.php?module=Documents&view=EditAjax&type=W' . $folderParam
                )
            );
			$createUrl = $moduleModel->getCreateRecordUrl() . $folderParam;
			$basicLinks = array(
					array(
							'linktype' => 'LISTVIEWBASIC',
							'linklabel' => 'Vtiger',
							'linkurl' => $createUrl,
							'linkicon' => 'Vtiger.png',
                            'linkdropdowns' => $vtigerDocumentTypes,
                            'linkclass' => 'addDocumentToVtiger',
					),
                    array(
                            'linktype' => 'LISTVIEWBASIC',
							'linklabel' => 'LBL_ADD_FOLDER',
							'linkurl' => 'javascript:Documents_List_Js.triggerAddFolder("'.$moduleModel->getAddFolderUrl().'")',
							'linkicon' => ''
					)
			);
			foreach($basicLinks as $basicLink) {
				$links['LISTVIEWBASIC'][] = Vtiger_Link_Model::getInstanceFromValues($basicLink);
			}
		}

		$exportPermission = Users_Privileges_Model::isPermitted($moduleModel->getName(), 'Export');
		if($exportPermission) {
			$advancedLink = array(
					'linktype' => 'LISTVIEW',
					'linklabel' => 'LBL_EXPORT',
					'linkurl' => 'javascript:Vtiger_List_Js.triggerExportAction("'.$moduleModel->getExportUrl().'")',
					'linkicon' => ''
			);
			$links['LISTVIEW'][] = Vtiger_Link_Model::getInstanceFromValues($advancedLink);
		}

		if($currentUserModel->isAdminUser()) {
			$settingsLinks = $this->getSettingLinks();
			foreach($settingsLinks as $settingsLink) {
				$links['LISTVIEWSETTING'][] = Vtiger_Link_Model::getInstanceFromValues($settingsLink);
			}
		}
		return $links;
	}

	/**
	 * Function to get the list of Mass actions for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associative array of Link type to List of  Vtiger_Link_Model instances for Mass Actions
	 */
	public function getListViewMassActions($linkParams) {
		$currentUserModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$moduleModel = $this->getModule();

		$linkTypes = array('LISTVIEWMASSACTION');
		$links = Vtiger_Link_Model::getAllByType($moduleModel->getId(), $linkTypes, $linkParams);
        
		//Opensource fix to make documents module mass editable
        if($currentUserModel->hasModuleActionPermission($moduleModel->getId(), 'EditView')) {
            $massActionLink = array(
                    'linktype' => 'LISTVIEWMASSACTION',
                    'linklabel' => 'LBL_EDIT',
                    'linkurl' => 'javascript:Vtiger_List_Js.triggerMassEdit("index.php?module='.$moduleModel->get('name').'&view=MassActionAjax&mode=showMassEditForm");',
                    'linkicon' => ''
            );
            $links['LISTVIEWMASSACTION'][] = Vtiger_Link_Model::getInstanceFromValues($massActionLink);
        }
        
		if ($currentUserModel->hasModuleActionPermission($moduleModel->getId(), 'Delete')) {
			$massActionLink = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_DELETE',
				'linkurl' => 'javascript:Vtiger_List_Js.massDeleteRecords("index.php?module=' . $moduleModel->getName() . '&action=MassDelete");',
				'linkicon' => ''
			);

			$links['LISTVIEWMASSACTION'][] = Vtiger_Link_Model::getInstanceFromValues($massActionLink);
		}

		$massActionLink = array(
			'linktype' => 'LISTVIEWMASSACTION',
			'linklabel' => 'LBL_MOVE',
			'linkurl' => 'javascript:Documents_List_Js.massMove("index.php?module=' . $moduleModel->getName() . '&view=MoveDocuments");',
			'linkicon' => ''
		);

		$links['LISTVIEWMASSACTION'][] = Vtiger_Link_Model::getInstanceFromValues($massActionLink);

		return $links;
	}

    /**
	 * Function to get the list view header - thêm cột Folder để hiển thị document thuộc folder nào
	 */
	public function getListViewHeaders() {
		$headers = parent::getListViewHeaders();
		$module = $this->getModule();
		if (!isset($headers['folderid'])) {
			$folderField = Vtiger_Field_Model::getInstance('folderid', $module);
			if ($folderField) {
				$folderField->set('listViewRawFieldName', $folderField->get('column'));
				$headers['folderid'] = $folderField;
			}
		}
		return $headers;
	}

	/**
	 * Function to get the list view entries
	 * @param Vtiger_Paging_Model $pagingModel
	 * @return <Array> - Associative array of record id mapped to Vtiger_Record_Model instance.
	 */
	public function getListViewEntries($pagingModel) {

		$db = PearDatabase::getInstance();

		$moduleName = $this->getModule()->get('name');
		$moduleFocus = CRMEntity::getInstance($moduleName);
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$queryGenerator = $this->get('query_generator');
		// Đảm bảo query lấy folderid (để hiển thị cột Folder)
		$currentFields = $queryGenerator->getFields();
		if (!in_array('folderid', $currentFields)) {
			$queryGenerator->setFields(array_merge($currentFields, array('folderid')));
		}
		$listViewContoller = $this->get('listview_controller');

        $folderId = $this->get('folder_id');
        $folderValue = $this->get('folder_value');
        // Resolve folder_id từ folder_value nếu chưa có (khi load qua Ajax/pjax)
        if (($folderId === '' || $folderId === null) && !empty($folderValue)) {
            if ((string)$folderValue === 'Default') {
                $folderId = Documents_Module_Model::getDefaultFolderId();
            } else {
                $db = PearDatabase::getInstance();
                $r = $db->pquery("SELECT folderid FROM vtiger_attachmentsfolder WHERE foldername = ? LIMIT 1", array($folderValue));
                $folderId = ($db->num_rows($r) > 0) ? $db->query_result($r, 0, 'folderid') : null;
            }
        }
        $hasFolderFilter = ($folderId !== '' && $folderId !== null);
        // KHÔNG dùng addCondition: folderid là reference field, QueryGenerator so sánh theo foldername thay vì id.
        // Sẽ thêm điều kiện vtiger_notes.folderid trực tiếp vào SQL sau getQuery().

        $searchParams = $this->get('search_params');
        if(empty($searchParams)) {
            $searchParams = array();
        }

        $glue = "";
        if(php7_count($queryGenerator->getWhereFields()) > 0 && (php7_count($searchParams)) == 1 && php7_count($searchParams[0]) > 0) { // searchParams do exist but first array is empty, so added a check
            $glue = QueryGenerator::$AND;
        }
        $queryGenerator->parseAdvFilterList($searchParams, $glue);

		$searchKey = $this->get('search_key');
		$searchValue = $this->get('search_value');
		$operator = $this->get('operator');
		if(!empty($searchKey)) {
			$queryGenerator->addUserSearchConditions(array('search_field' => $searchKey, 'search_text' => $searchValue, 'operator' => $operator));
		}
        
        $orderBy = $this->getForSql('orderby');
		$sortOrder = $this->getForSql('sortorder');

        if(!empty($orderBy)){
			$queryGenerator = $this->get('query_generator');
			$fieldModels = $queryGenerator->getModuleFields();
			$orderByFieldModel = $fieldModels[$orderBy];
            if($orderByFieldModel && ($orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::REFERENCE_TYPE ||
					$orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::OWNER_TYPE)){
                $queryGenerator->addWhereField($orderBy);
            }
        }
        //Document source required in list view for managing delete 
		$listViewFields = $queryGenerator->getFields();
        if(!in_array('document_source', $listViewFields)){
            $listViewFields[] = 'document_source';
        }
        $queryGenerator->setFields($listViewFields);
		$listQuery = $this->getQuery();

		// Lọc theo folder: thêm trực tiếp vtiger_notes.folderid (vì folderid là reference field, addCondition so sánh sai)
		if ($hasFolderFilter) {
			$listQuery .= ' AND vtiger_notes.folderid = ' . (int)$folderId;
		}

		// Phân quyền theo owner: chỉ áp dụng khi KHÔNG xem theo folder.
		// Khi xem theo folder: quyền đã kiểm tra ở userCanAccessFolder; hiển thị tất cả document trong folder.
		$folderId = $this->get('folder_id');
		if ($folderId === '' || $folderId === null) {
			$accessibleOwnerIds = Documents_Module_Model::getAccessibleOwnerIdsForDocuments();
			if ($accessibleOwnerIds !== null && php7_count($accessibleOwnerIds) > 0) {
				$idsSql = implode(',', array_map('intval', $accessibleOwnerIds));
				$listQuery .= ' AND vtiger_crmentity.smownerid IN (' . $idsSql . ')';
			}
		}

		$sourceModule = $this->get('src_module');
		if(!empty($sourceModule)) {
			if(method_exists($moduleModel, 'getQueryByModuleField')) {
				$overrideQuery = $moduleModel->getQueryByModuleField($sourceModule, $this->get('src_field'), $this->get('src_record'), $listQuery);
				if(!empty($overrideQuery)) {
					$listQuery = $overrideQuery;
				}
			}
            //allow source module to modify the query
            $sourceModuleModel = Vtiger_Module_Model::getInstance($sourceModule);
            if(method_exists($sourceModuleModel, 'getQueryByModuleField')) {
                $sourceOverrideQuery = $sourceModuleModel->getQueryByModuleField($moduleModel->getName(),$this->get('src_field'),$this->get('src_record'),$listQuery);
                if(!empty($sourceOverrideQuery)) {
                    $listQuery = $sourceOverrideQuery;
                }
            }
		}

		$startIndex = $pagingModel->getStartIndex();
		$pageLimit = $pagingModel->getPageLimit();

		if(!empty($orderBy) && $orderByFieldModel) {
			$listQuery .= ' ORDER BY '.$queryGenerator->getOrderByColumn($orderBy).' '.$sortOrder;
		} else if(empty($orderBy) && empty($sortOrder)){
			//List view will be displayed on recently created/modified records
			$listQuery .= ' ORDER BY vtiger_crmentity.modifiedtime DESC';
		}

		$viewid = ListViewSession::getCurrentView($moduleName);
        if(empty($viewid)){
            $viewid = $pagingModel->get('viewid');
        }
        $_SESSION['lvs'][$moduleName][$viewid]['start'] = $pagingModel->get('page');
		ListViewSession::setSessionQuery($moduleName, $listQuery, $viewid);

		$listQuery .= " LIMIT $startIndex,".($pageLimit+1);

		$listResult = $db->pquery($listQuery, array());

		$listViewRecordModels = array();
		$listViewEntries =  $listViewContoller->getListViewRecords($moduleFocus,$moduleName, $listResult);

		$pagingModel->calculatePageRange($listViewEntries);

		if($db->num_rows($listResult) > $pageLimit){
			array_pop($listViewEntries);
			$pagingModel->set('nextPageExists', true);
		}else{
			$pagingModel->set('nextPageExists', false);
		}

		$index = 0;
		foreach($listViewEntries as $recordId => $record) {
			$rawData = $db->query_result_rowdata($listResult, $index++);
			$record['id'] = $recordId;
			$listViewRecordModels[$recordId] = $moduleModel->getRecordFromArray($record, $rawData);
		}
		return $listViewRecordModels;
	}

	/**
	 * Override: thêm lọc folder trực tiếp vào SQL (vtiger_notes.folderid) thay vì addCondition,
	 * vì folderid là reference field và QueryGenerator so sánh theo foldername, không phải id.
	 */
	public function getListViewCount() {
		$db = PearDatabase::getInstance();
		$queryGenerator = $this->get('query_generator');
		$searchParams = $this->get('search_params');
		if (empty($searchParams)) {
			$searchParams = array();
		}

		// Resolve folder_id giống getListViewEntries
		$folderId = $this->get('folder_id');
		$folderValue = $this->get('folder_value');
		if (($folderId === '' || $folderId === null) && !empty($folderValue)) {
			if ((string)$folderValue === 'Default') {
				$folderId = Documents_Module_Model::getDefaultFolderId();
			} else {
				$r = $db->pquery("SELECT folderid FROM vtiger_attachmentsfolder WHERE foldername = ? LIMIT 1", array($folderValue));
				$folderId = ($db->num_rows($r) > 0) ? $db->query_result($r, 0, 'folderid') : null;
			}
		}
		$hasFolderFilter = ($folderId !== '' && $folderId !== null);
		// KHÔNG dùng addCondition cho folderid – xem getListViewEntries

		$glue = "";
		if (php7_count($queryGenerator->getWhereFields()) > 0 && (php7_count($searchParams)) > 0) {
			$glue = QueryGenerator::$AND;
		}
		$queryGenerator->parseAdvFilterList($searchParams, $glue);

		$searchKey = $this->get('search_key');
		$searchValue = $this->get('search_value');
		$operator = $this->get('operator');
		if (!empty($searchKey)) {
			$queryGenerator->addUserSearchConditions(array('search_field' => $searchKey, 'search_text' => $searchValue, 'operator' => $operator));
		}

		$moduleModel = Vtiger_Module_Model::getInstance($this->getModule()->get('name'));
		$listQuery = $this->getQuery();

		// Lọc folder trực tiếp vào SQL
		if ($hasFolderFilter) {
			$listQuery .= ' AND vtiger_notes.folderid = ' . (int)$folderId;
		}

		// Phân quyền owner: chỉ áp dụng khi KHÔNG xem theo folder (giống getListViewEntries)
		if (!$hasFolderFilter) {
			$accessibleOwnerIds = Documents_Module_Model::getAccessibleOwnerIdsForDocuments();
			if ($accessibleOwnerIds !== null && php7_count($accessibleOwnerIds) > 0) {
				$listQuery .= ' AND vtiger_crmentity.smownerid IN (' . implode(',', array_map('intval', $accessibleOwnerIds)) . ')';
			}
		}

		$sourceModule = $this->get('src_module');
		if (!empty($sourceModule)) {
			if (method_exists($moduleModel, 'getQueryByModuleField')) {
				$overrideQuery = $moduleModel->getQueryByModuleField($sourceModule, $this->get('src_field'), $this->get('src_record'), $listQuery);
				if (!empty($overrideQuery)) {
					$listQuery = $overrideQuery;
				}
			}
		}

		$position = stripos($listQuery, ' from ');
		if ($position) {
			$split = preg_split('/ from /i', $listQuery);
			$splitCount = php7_count($split);
			$meta = $queryGenerator->getMeta($this->getModule()->getName());
			$columnIndex = $meta->getObectIndexColumn();
			$baseTable = $meta->getEntityBaseTable();
			$listQuery = "SELECT count(distinct($baseTable.$columnIndex)) AS count ";
			for ($i = 1; $i < $splitCount; $i++) {
				$listQuery = $listQuery . ' FROM ' . $split[$i];
			}
		}

		$listResult = $db->pquery($listQuery, array());
		return $db->query_result($listResult, 0, 'count');
	}

}
