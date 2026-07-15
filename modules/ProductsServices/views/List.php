<?php
/*+***********************************************************************************
 * ProductsServices List — fixed columns: name, type, price, supplier, unit.
 *************************************************************************************/

class ProductsServices_List_View extends Vtiger_List_View {

	const NAME_FIELD = 'productsservicesname';

	/** Fixed list columns (data fields only — checkbox is vtiger control column). */
	protected function getCanonicalListHeaders() {
		return array(
			self::NAME_FIELD,
			'item_type',
			'price',
			'supplier',
			'unit',
		);
	}

	protected function getHeaderLabelOverrides() {
		return array(
			'productsservicesname' => 'Tên sản phẩm',
			'item_type' => 'Loại',
			'price' => 'Giá',
			'supplier' => 'Nhà cung cấp',
			'unit' => 'Đơn vị',
		);
	}

	/**
	 * Always use canonical columns — ignore broken session list_headers.
	 */
	protected function normalizeListHeaders($listHeaders) {
		return $this->getCanonicalListHeaders();
	}

	protected function ensureNameInListHeaders(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$cvId = $this->viewName;
		if (!$cvId) {
			$customView = new CustomView();
			$cvId = $customView->getViewId($moduleName);
			$this->viewName = $cvId;
		}
		$tag = $request->get('tag');
		if (!is_numeric($tag)) {
			$tag = '';
		}
		$listViewSessionKey = $moduleName . '_' . $cvId;
		if (!empty($tag)) {
			$listViewSessionKey .= '_' . $tag;
		}

		$listHeaders = $this->getCanonicalListHeaders();
		if (!$this->listViewModel) {
			$this->listViewModel = ProductsServices_ListView_Model::getInstance($moduleName, $cvId, array());
		}
		$orderParams = $this->listViewModel->getSortParamsSession($listViewSessionKey);
		if (!is_array($orderParams)) {
			$orderParams = array();
		}
		$orderParams['list_headers'] = $listHeaders;
		$this->listViewModel->setSortParamsSession($listViewSessionKey, $orderParams);
		$request->set('list_headers', $listHeaders);

		$this->listViewModel = ProductsServices_ListView_Model::getInstance($moduleName, $cvId, $listHeaders);
		$this->listViewModel->forceProductNameColumn();
		return $listHeaders;
	}

	protected function forceNameAndRefreshAssign(Vtiger_Request $request, Vtiger_Viewer $viewer) {
		if (!$this->listViewModel || !method_exists($this->listViewModel, 'forceProductNameColumn')) {
			return;
		}
		$this->listViewModel->forceProductNameColumn();
		$this->listViewHeaders = $this->listViewModel->getListViewHeaders();
		if (!isset($this->listViewHeaders[self::NAME_FIELD])) {
			$nameField = Vtiger_Field_Model::getInstance(self::NAME_FIELD, $this->listViewModel->getModule());
			if ($nameField) {
				$nameField->set('listViewRawFieldName', $nameField->get('column') ?: self::NAME_FIELD);
				$this->listViewHeaders = array(self::NAME_FIELD => $nameField) + $this->listViewHeaders;
			}
		}
		$viewer->assign('LISTVIEW_HEADERS', $this->listViewHeaders);
		$viewer->assign('LIST_HEADER_FIELDS', json_encode(array_keys($this->listViewHeaders)));
		$viewer->assign('LISTVIEW_HEADER_LABEL_OVERRIDES', $this->getHeaderLabelOverrides());
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if (!$this->viewName) {
			$this->viewName = $request->get('viewname');
		}
		$this->ensureNameInListHeaders($request);

		parent::preProcess($request, false);

		$this->listViewHeaders = false;
		$this->listViewEntries = false;
		$this->noOfEntries = false;
		$this->listviewinitcalled = false;
		$viewer = $this->getViewer($request);
		$this->initializeListViewContents($request, $viewer);
		$this->forceNameAndRefreshAssign($request, $viewer);

		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	public function process(Vtiger_Request $request) {
		$this->ensureNameInListHeaders($request);
		$this->listViewHeaders = false;
		$this->listViewEntries = false;
		$this->noOfEntries = false;
		$this->listviewinitcalled = false;

		parent::process($request);

		$viewer = $this->getViewer($request);
		$this->forceNameAndRefreshAssign($request, $viewer);
	}

	public function initializeListViewContents(Vtiger_Request $request, Vtiger_Viewer $viewer) {
		if ($this->listViewModel && method_exists($this->listViewModel, 'forceProductNameColumn')) {
			$this->listViewModel->forceProductNameColumn();
		}
		parent::initializeListViewContents($request, $viewer);
		$this->forceNameAndRefreshAssign($request, $viewer);
	}
}
