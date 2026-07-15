<?php
/*+***********************************************************************************
 * ProductsServices List — force product name column into query before list renders.
 *************************************************************************************/

class ProductsServices_List_View extends Vtiger_List_View {

	protected function ensureNameInListHeaders(Vtiger_Request $request) {
		$listHeaders = $request->get('list_headers', array());
		if (!is_array($listHeaders)) {
			$listHeaders = array();
		}
		// Only prepend when headers already chosen (session/request); otherwise Custom View is used.
		if (php7_count($listHeaders) > 0 && !in_array('productsservicesname', $listHeaders, true)) {
			array_unshift($listHeaders, 'productsservicesname');
			$request->set('list_headers', $listHeaders);
		}
	}

	protected function reinitWithProductName(Vtiger_Request $request) {
		if (!$this->listViewModel || !method_exists($this->listViewModel, 'forceProductNameColumn')) {
			return;
		}
		$this->listViewModel->forceProductNameColumn();
		$this->listViewHeaders = false;
		$this->listViewEntries = false;
		$this->noOfEntries = false;
		$this->listviewinitcalled = false;
		$viewer = $this->getViewer($request);
		$this->initializeListViewContents($request, $viewer);
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->ensureNameInListHeaders($request);
		parent::preProcess($request, false);
		$this->reinitWithProductName($request);
		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	public function initializeListViewContents(Vtiger_Request $request, Vtiger_Viewer $viewer) {
		$this->ensureNameInListHeaders($request);
		if ($this->listViewModel && method_exists($this->listViewModel, 'forceProductNameColumn')) {
			$this->listViewModel->forceProductNameColumn();
		}
		parent::initializeListViewContents($request, $viewer);
		// Parent may have restored session headers without the name — force-assign again.
		if ($this->listViewModel && method_exists($this->listViewModel, 'forceProductNameColumn')) {
			$this->listViewModel->forceProductNameColumn();
			$this->listViewHeaders = $this->listViewModel->getListViewHeaders();
			$viewer->assign('LISTVIEW_HEADERS', $this->listViewHeaders);
			$viewer->assign('LIST_HEADER_FIELDS', json_encode(array_keys($this->listViewHeaders)));
		}
	}
}
