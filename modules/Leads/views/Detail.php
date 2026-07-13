<?php
/*+***********************************************************************************
 * Leads Detail: modern SALES UI + list inline dropdown panel.
 ************************************************************************************/

class Leads_Detail_View extends Vtiger_Index_View {

	protected function preProcessTplName(Vtiger_Request $request) {
		return 'DetailViewPreProcess.tpl';
	}

	protected function resolveAppCategory(Vtiger_Request $request) {
		return 'SALES';
	}

	protected function redirectMarketingToSales(Vtiger_Request $request) {
		$app = strtoupper((string) $request->get('app'));
		if ($app === 'MARKETING') {
			$query = array(
				'module' => 'Leads',
				'view' => 'Detail',
				'app' => 'SALES',
			);
			if ($request->get('record')) {
				$query['record'] = $request->get('record');
			}
			header('Location: index.php?' . http_build_query($query));
			exit;
		}
	}

	protected function assignModernContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$viewer->assign('SELECTED_MENU_CATEGORY', 'SALES');
		$viewer->assign('VIEW', 'Detail');
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Leads');
		$viewer->assign('MK_LEADS_DETAIL_RECORD', $request->get('record'));
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->redirectMarketingToSales($request);
		parent::preProcess($request, false);
		$this->assignModernContext($request);
		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('DetailViewPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
		$viewer->view('DetailViewScripts.tpl', $request->getModule());
	}

	public function process(Vtiger_Request $request) {
		$this->redirectMarketingToSales($request);
		$mode = $request->getMode();
		if ($mode === 'showListInlineDetail') {
			echo $this->showListInlineDetail($request);
			return;
		}
		$viewer = $this->getViewer($request);
		$this->assignModernContext($request);
		$viewer->view('DetailView.tpl', $request->getModule());
	}

	/**
	 * Expandable list-row detail panel (Quotes/SO style) with pencil inline edit.
	 */
	public function showListInlineDetail(Vtiger_Request $request) {
		if (strtoupper((string) $request->get('app')) !== 'SALES') {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$recordId = (int) $request->get('record');
		if ($recordId <= 0) {
			return '<div class="mk-so-inline-detail mk-so-inline-detail--error">Không tải được chi tiết.</div>';
		}

		require_once 'modules/Vtiger/helpers/MkSalesInlineDetailHelper.php';
		$moduleName = 'Leads';
		try {
			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
		} catch (Exception $e) {
			return '<div class="mk-so-inline-detail mk-so-inline-detail--error">Không tải được chi tiết lead.</div>';
		}

		$title = trim((string) $recordModel->getName());
		if ($title === '') {
			$title = trim(html_entity_decode(strip_tags((string) $recordModel->getDisplayValue('lastname')), ENT_QUOTES, 'UTF-8'));
		}
		$subtitle = trim(html_entity_decode(strip_tags((string) $recordModel->getDisplayValue('lead_no')), ENT_QUOTES, 'UTF-8'));
		$infoFields = Vtiger_MkSalesInlineDetailHelper::buildFields($moduleModel, $recordModel, array(
			array('phone', 'Điện thoại'),
			array('email', 'Email'),
			array('company', 'Công ty'),
			array('leadsource', 'Nguồn'),
			array('leadstatus', 'Trạng thái'),
			array('assigned_user_id', 'Phụ trách'),
			array('createdtime', 'Ngày tạo'),
		));

		$nextAction = '';
		try {
			$adb = PearDatabase::getInstance();
			$naRes = $adb->pquery('SELECT next_action FROM bace_lead_profile WHERE leadid = ?', array($recordId));
			if ($naRes && $adb->num_rows($naRes) > 0) {
				$nextAction = Vtiger_MkSalesInlineDetailHelper::decodeText($adb->query_result($naRes, 0, 'next_action'));
			}
		} catch (Exception $e) {
			$nextAction = '';
		}

		$viewer = $this->getViewer($request);
		Vtiger_MkSalesInlineDetailHelper::assignCommon($viewer, $recordModel, $moduleName, 'SALES', $infoFields, $title, $subtitle);
		$viewer->assign('INLINE_SHOW_NEXT_ACTION', true);
		$viewer->assign('INLINE_NEXT_ACTION', $nextAction);
		return $viewer->view('partials/MkSalesPosInlineDetail.tpl', 'Vtiger', true);
	}

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if (!Users_Privileges_Model::isPermitted($moduleName, 'index')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $moduleName));
		}
		return true;
	}
}
