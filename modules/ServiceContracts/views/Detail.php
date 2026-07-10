<?php
/* +***********************************************************************************
 * ServiceContracts Detail: calendar activities + POS list inline dropdown.
 * *********************************************************************************** */

class ServiceContracts_Detail_View extends Vtiger_Detail_View {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('showListInlineDetail');
	}

	public function showListInlineDetail(Vtiger_Request $request) {
		if (strtoupper((string) $request->get('app')) !== 'SALES') {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$recordId = $request->get('record');
		if (empty($recordId)) {
			return '';
		}

		$moduleName = 'ServiceContracts';
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
		$viewer = $this->getViewer($request);

		$title = trim(html_entity_decode(strip_tags((string) $recordModel->getDisplayValue('subject')), ENT_QUOTES, 'UTF-8'));
		if ($title === '') {
			$title = trim((string) $recordModel->getName());
		}
		$subtitle = trim(html_entity_decode(strip_tags((string) $recordModel->getDisplayValue('contract_no')), ENT_QUOTES, 'UTF-8'));
		$notes = trim(strip_tags(decode_html((string) $recordModel->get('description'))));

		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('INLINE_TITLE', $title !== '' ? $title : '—');
		$viewer->assign('INLINE_SUBTITLE', $subtitle);
		$viewer->assign('INLINE_NOTES', $notes);
		$viewer->assign('INLINE_EDIT_URL', $recordModel->getEditViewUrl() . '&app=SALES');
		$viewer->assign('INLINE_DETAIL_URL', $recordModel->getDetailViewUrl() . '&app=SALES');
		$viewer->assign('INLINE_INFO_FIELDS', $this->getServiceContractsInlineInfoFields($moduleModel, $recordModel));

		return $viewer->view('partials/MkSalesPosInlineDetail.tpl', 'Vtiger', true);
	}

	protected function getServiceContractsInlineInfoFields(Vtiger_Module_Model $moduleModel, Vtiger_Record_Model $recordModel) {
		$candidates = array(
			array('contract_status', 'Trạng thái'),
			array('sc_related_to', 'Liên quan tới'),
			array('account_id', 'Tổ chức'),
			array('start_date', 'Ngày bắt đầu'),
			array('end_date', 'Ngày kết thúc'),
			array('assigned_user_id', 'Phụ trách'),
			array('createdtime', 'Ngày tạo'),
		);
		$fields = array();
		$seen = array();
		foreach ($candidates as $pair) {
			$fieldName = $pair[0];
			$fieldModel = $moduleModel->getField($fieldName);
			if (!$fieldModel || !$fieldModel->isViewable() || isset($seen[$fieldName])) {
				continue;
			}
			$value = trim(html_entity_decode(strip_tags((string) $recordModel->getDisplayValue($fieldName)), ENT_QUOTES, 'UTF-8'));
			$fields[] = array(
				'name' => $fieldName,
				'label' => $pair[1],
				'value' => $value !== '' ? $value : '—',
			);
			$seen[$fieldName] = true;
		}
		return $fields;
	}

	/**
	 * @param Vtiger_Request $request
	 * @return string
	 */
	public function getActivities(Vtiger_Request $request) {
		$moduleName = 'Calendar';
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
			return '';
		}

		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$pageNumber = $request->get('page');
		if (empty($pageNumber)) {
			$pageNumber = 1;
		}
		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page', $pageNumber);
		$pagingModel->set('limit', 10);

		if (!$this->record) {
			$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();
		$moduleModel = $recordModel->getModule();

		$relatedActivities = $moduleModel->getCalendarActivities('', $pagingModel, 'all', $recordId);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('PAGE_NUMBER', $pageNumber);
		$viewer->assign('ACTIVITIES', $relatedActivities);

		return $viewer->view('RelatedActivities.tpl', $moduleName, true);
	}
}
