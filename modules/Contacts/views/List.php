<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Contacts_List_View extends Vtiger_List_View {
	/**
	 * Contacts list: always show real "X to Y of Z" count immediately.
	 * Scoped to Contacts to avoid global performance impact.
	 */
	public function initializeListViewContents(Vtiger_Request $request, Vtiger_Viewer $viewer) {
		parent::initializeListViewContents($request, $viewer);

		$totalCount = $viewer->getTemplateVars('LISTVIEW_COUNT');
		if ($totalCount === null || $totalCount === '' || $totalCount === false) {
			$listViewModel = $viewer->getTemplateVars('LIST_VIEW_MODEL');
			if ($listViewModel) {
				$totalCount = $listViewModel->getListViewCount();
				$viewer->assign('LISTVIEW_COUNT', $totalCount);

				$pagingModel = $viewer->getTemplateVars('PAGING_MODEL');
				if ($pagingModel) {
					$pageLimit = (int)$pagingModel->getPageLimit();
					$pageCount = $pageLimit > 0 ? (int)ceil(((int)$totalCount) / $pageLimit) : 1;
					if ($pageCount <= 0) $pageCount = 1;
					$viewer->assign('PAGE_COUNT', $pageCount);
				}
			}
		}
	}
}

