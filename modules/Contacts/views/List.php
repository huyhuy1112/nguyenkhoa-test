<?php
/*+**********************************************************************************
 * Contacts List — SALES Lovable UI (same shell as Leads / Potentials).
 ************************************************************************************/

class Contacts_List_View extends Vtiger_List_View {

	public function initializeListViewContents(Vtiger_Request $request, Vtiger_Viewer $viewer) {
		if ($this->isMkModernSalesContactsList($request)) {
			return;
		}
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
					if ($pageCount <= 0) {
						$pageCount = 1;
					}
					$viewer->assign('PAGE_COUNT', $pageCount);
				}
			}
		}
	}

	protected function assignModernListContext(Vtiger_Request $request) {
		require_once 'modules/Contacts/models/ModernService.php';
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$viewer->assign('SELECTED_MENU_CATEGORY', 'SALES');
		$viewer->assign('VIEW', 'List');
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Contacts');
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('MK_CONTACTS_ASSIGNABLE_USERS', Contacts_ModernService::listAssignableUsers());
	}

	protected function isMkModernSalesContactsList(Vtiger_Request $request) {
		if (strtolower((string)$request->get('view')) !== 'list') {
			return false;
		}
		$app = strtoupper((string)$request->get('app'));
		return $app === 'SALES' || $app === '';
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($this->isMkModernSalesContactsList($request)) {
			Vtiger_Index_View::preProcess($request, false);
			$this->assignModernListContext($request);
			if ($display) {
				$this->preProcessDisplay($request);
			}
			return;
		}
		parent::preProcess($request, $display);
	}

	public function process(Vtiger_Request $request) {
		if ($this->isMkModernSalesContactsList($request)) {
			$viewer = $this->getViewer($request);
			$this->assignModernListContext($request);
			$viewer->view('ListViewContents.tpl', $request->getModule());
			return;
		}
		parent::process($request);
	}
}
