<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Potentials_Detail_View extends Vtiger_Detail_View {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('showRelatedRecords');
		$this->exposeMethod('showListInlineDetail');
	}

	/**
	 * Expandable list-row detail panel with pencil inline edit (Sales Mk list).
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
		$moduleName = 'Potentials';
		try {
			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
		} catch (Exception $e) {
			return '<div class="mk-so-inline-detail mk-so-inline-detail--error">Không tải được chi tiết cơ hội.</div>';
		}

		$title = trim(html_entity_decode(strip_tags((string) $recordModel->getDisplayValue('potentialname')), ENT_QUOTES, 'UTF-8'));
		if ($title === '') {
			$title = trim((string) $recordModel->getName());
		}
		$subtitle = trim(html_entity_decode(strip_tags((string) $recordModel->getDisplayValue('potential_no')), ENT_QUOTES, 'UTF-8'));

		$profileAddress = '';
		$profileDistrict = '';
		$regionKey = '';
		try {
			require_once 'modules/Potentials/models/ModernService.php';
			Potentials_ModernService::ensureProfileSchema();
			$adb = PearDatabase::getInstance();
			$pp = $adb->pquery(
				'SELECT district, address_line FROM bace_potential_profile WHERE potentialid = ?',
				array($recordId)
			);
			if ($pp && $adb->num_rows($pp) > 0) {
				$profileDistrict = Vtiger_MkSalesInlineDetailHelper::decodeText($adb->query_result($pp, 0, 'district'));
				$profileAddress = Vtiger_MkSalesInlineDetailHelper::decodeText($adb->query_result($pp, 0, 'address_line'));
			}
			if ($profileAddress === '' && $profileDistrict === '') {
				$lp = $adb->pquery(
					'SELECT district, address_line FROM bace_lead_profile WHERE potential_id = ? LIMIT 1',
					array($recordId)
				);
				if ($lp && $adb->num_rows($lp) > 0) {
					$profileDistrict = Vtiger_MkSalesInlineDetailHelper::decodeText($adb->query_result($lp, 0, 'district'));
					$profileAddress = Vtiger_MkSalesInlineDetailHelper::decodeText($adb->query_result($lp, 0, 'address_line'));
				}
			}
		} catch (Exception $e) {
			$profileAddress = '';
			$profileDistrict = '';
		}
		if (preg_match('/khu\s*vực\s*([123])/iu', $profileDistrict, $rm)) {
			$regionKey = 'kv' . $rm[1];
		}

		$infoFields = Vtiger_MkSalesInlineDetailHelper::buildFields($moduleModel, $recordModel, array(
			array('closingdate', 'Ngày đóng'),
			array('assigned_user_id', 'Phụ trách'),
		));

		$locationFields = array(
			array(
				'name' => 'mk_region',
				'label' => 'Khu vực',
				'value' => $regionKey !== '' ? ('Khu vực ' . substr($regionKey, -1)) : '—',
				'raw_value' => $regionKey,
				'data_type' => 'picklist',
				'editable' => true,
				'picklist_values' => array(
					'' => '— Chọn khu vực —',
					'kv1' => 'Khu vực 1',
					'kv2' => 'Khu vực 2',
					'kv3' => 'Khu vực 3',
				),
			),
			array(
				'name' => 'mk_address',
				'label' => 'Địa chỉ',
				'value' => $profileAddress !== '' ? $profileAddress : '—',
				'raw_value' => $profileAddress,
				'data_type' => 'string',
				'editable' => true,
				'picklist_values' => array(),
			),
		);
		array_splice($infoFields, 1, 0, $locationFields);

		$inlineTags = Vtiger_MkSalesInlineDetailHelper::buildInlineTags($moduleName, $recordId);
		foreach ($inlineTags as $tag) {
			$key = isset($tag['key']) ? (string) $tag['key'] : '';
			if ($regionKey === '' && preg_match('/^kv([123])$/i', $key, $km)) {
				$regionKey = 'kv' . $km[1];
			}
		}
		if ($regionKey !== '') {
			foreach ($infoFields as &$f) {
				if (!empty($f['name']) && $f['name'] === 'mk_region') {
					$f['raw_value'] = $regionKey;
					$f['value'] = 'Khu vực ' . substr($regionKey, -1);
					break;
				}
			}
			unset($f);
		}

		$attendance = array(
			'eligible' => false,
			'can_edit' => false,
			'status' => '',
			'status_label' => '',
			'class_date' => '',
			'checked_in_at' => '',
			'checked_in_at_label' => '',
		);
		try {
			require_once 'modules/Leads/models/ConvertService.php';
			require_once 'modules/Leads/models/OfflineGd11Service.php';
			Leads_OfflineGd11Service::installSchema();
			$leadId = (int) Leads_ConvertService::getLinkedLeadIdByPotential($recordId);
			if ($leadId > 0) {
				$adb = PearDatabase::getInstance();
				$ores = $adb->pquery(
					'SELECT offline_status, offline_class_date, offline_checked_in_at
					 FROM bace_lead_profile WHERE leadid = ?',
					array($leadId)
				);
				if ($ores && $adb->num_rows($ores) > 0) {
					$status = trim((string) $adb->query_result($ores, 0, 'offline_status'));
					$classDate = (string) $adb->query_result($ores, 0, 'offline_class_date');
					if ($classDate === '0000-00-00') {
						$classDate = '';
					}
					$checkedRaw = (string) $adb->query_result($ores, 0, 'offline_checked_in_at');
					if ($checkedRaw === '0000-00-00 00:00:00') {
						$checkedRaw = '';
					}
					$labels = Leads_OfflineGd11Service::statusLabels();
					$eligibleStatuses = array(
						Leads_OfflineGd11Service::STATUS_DA_XN_LICH,
						Leads_OfflineGd11Service::STATUS_HEN_LICH_LAI,
						Leads_OfflineGd11Service::STATUS_KHONG_THAM_GIA,
						Leads_OfflineGd11Service::STATUS_DA_THAM_GIA,
					);
					$editableStatuses = array(
						Leads_OfflineGd11Service::STATUS_DA_XN_LICH,
						Leads_OfflineGd11Service::STATUS_HEN_LICH_LAI,
					);
					$attendance['status'] = $status;
					$attendance['status_label'] = isset($labels[$status]) ? $labels[$status] : $status;
					$attendance['class_date'] = $classDate;
					$attendance['checked_in_at'] = $checkedRaw !== '' ? date('c', strtotime($checkedRaw)) : '';
					$attendance['checked_in_at_label'] = $checkedRaw !== ''
						? date('d/m/Y H:i', strtotime($checkedRaw)) : '';
					$attendance['eligible'] = ($status !== '' && in_array($status, $eligibleStatuses, true));
					$cu = Users_Record_Model::getCurrentUserModel();
					$attendance['can_edit'] = $attendance['eligible']
						&& in_array($status, $editableStatuses, true)
						&& $cu && $cu->isAdminUser();
					// Khóa sau khi đã điểm danh (không lẫn với “chỉ Admin” khi chưa điểm danh).
					$attendance['locked'] = $attendance['eligible']
						&& in_array($status, array(
							Leads_OfflineGd11Service::STATUS_KHONG_THAM_GIA,
							Leads_OfflineGd11Service::STATUS_DA_THAM_GIA,
						), true);
				}
			}
		} catch (Exception $e) {
			// keep defaults
		}

		$nextAction = '';
		$nextActionTimeframe = '';
		$nextActionOverdue = false;
		$nextActionAlertDays = null;
		try {
			require_once 'modules/HelpDesk/models/TagRuleEngineService.php';
			$ruleSvc = HelpDesk_TagRuleEngineService::getInstance();
			$tags = $ruleSvc->getRecordTagLabels($moduleName, $recordId);
			$lastTouchRaw = $recordModel->get('modifiedtime');
			if (!$lastTouchRaw) {
				$adb = PearDatabase::getInstance();
				$ceRes = $adb->pquery(
					'SELECT modifiedtime FROM vtiger_crmentity WHERE crmid = ? AND deleted = 0',
					array($recordId)
				);
				if ($ceRes && $adb->num_rows($ceRes) > 0) {
					$lastTouchRaw = $adb->query_result($ceRes, 0, 'modifiedtime');
				}
			}
			$ruleMeta = $ruleSvc->resolveNextActionMeta($tags, $lastTouchRaw, '');
			$nextAction = $ruleMeta['next_action'];
			$nextActionTimeframe = $ruleMeta['timeframe_label'];
			$nextActionOverdue = !empty($ruleMeta['next_action_overdue']);
			$nextActionAlertDays = $ruleMeta['rule_alert_days'];
		} catch (Exception $e) {
			$nextAction = '';
		}

		$lastTouch = array(
			'can_add' => true,
			'next_n' => 1,
			'count' => 0,
			'max_calls' => 3,
			'hint' => '',
			'reminder_at_label' => '',
			'calls' => array(),
		);
		try {
			require_once 'modules/Potentials/models/LastTouchCallService.php';
			$lastTouch = Potentials_LastTouchCallService::getSummary($recordId);
		} catch (Exception $e) {
			// keep defaults
		}

		$viewer = $this->getViewer($request);
		Vtiger_MkSalesInlineDetailHelper::assignCommon($viewer, $recordModel, $moduleName, 'SALES', $infoFields, $title, $subtitle);
		$viewer->assign('INLINE_SHOW_NEXT_ACTION', true);
		$viewer->assign('INLINE_NEXT_ACTION', $nextAction);
		$viewer->assign('INLINE_NEXT_ACTION_LOCKED', true);
		$viewer->assign('INLINE_NEXT_ACTION_TIMEFRAME', $nextActionTimeframe);
		$viewer->assign('INLINE_NEXT_ACTION_OVERDUE', $nextActionOverdue);
		$viewer->assign('INLINE_NEXT_ACTION_ALERT_DAYS', $nextActionAlertDays);
		$viewer->assign('INLINE_LAST_TOUCH', $lastTouch);
		$viewer->assign('INLINE_ATTENDANCE', $attendance);
		return $viewer->view('partials/MkSalesPosInlineDetail.tpl', 'Vtiger', true);
	}

	protected function ensureSalesApp(Vtiger_Request $request) {
		require_once 'modules/Potentials/helpers/SalesAppGuard.php';
		Potentials_SalesAppGuard::enforce($request);
	}

	protected function assignSalesApp(Vtiger_Request $request) {
		require_once 'modules/Potentials/helpers/SalesAppGuard.php';
		Potentials_SalesAppGuard::assignViewer($request, $this->getViewer($request));
	}

	/**
	 * Boot interaction log into the summary template so Opp detail never hangs on AJAX.
	 */
	protected function assignInteractionLogBoot(Vtiger_Request $request, $recordId) {
		$recordId = (int) $recordId;
		$viewer = $this->getViewer($request);
		$empty = array(
			'phone' => '',
			'contact_id' => 0,
			'contact_name' => '',
			'lead_id' => 0,
			'items' => array(),
		);
		if ($recordId <= 0) {
			$viewer->assign('MK_OPP_INTERACTION_LOG_JSON', json_encode($empty));
			return;
		}
		try {
			require_once 'modules/Potentials/models/InteractionLogService.php';
			$log = Potentials_InteractionLogService::getLog($recordId);
			if (!is_array($log)) {
				$log = $empty;
			}
			$viewer->assign(
				'MK_OPP_INTERACTION_LOG_JSON',
				json_encode(
					$log,
					JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
				)
			);
		} catch (Exception $e) {
			$viewer->assign('MK_OPP_INTERACTION_LOG_JSON', json_encode($empty));
		}
	}

	protected function assignLinkedLeadAddress(Vtiger_Request $request, $recordId) {
		$recordId = (int)$recordId;
		if ($recordId <= 0) {
			return;
		}
		require_once 'modules/Leads/models/ConvertService.php';
		$viewer = $this->getViewer($request);
		$fullAddress = '';
		$leadId = Leads_ConvertService::getLinkedLeadIdByPotential($recordId);
		if ($leadId) {
			$adb = PearDatabase::getInstance();
			$res = $adb->pquery(
				"SELECT district, address_line, area FROM bace_lead_profile WHERE leadid = ?",
				array((int)$leadId)
			);
			if ($res && $adb->num_rows($res) > 0) {
				$district = decode_html((string)$adb->query_result($res, 0, 'district'));
				$addressLine = decode_html((string)$adb->query_result($res, 0, 'address_line'));
				$area = decode_html((string)$adb->query_result($res, 0, 'area'));
				$parts = array();
				if ($district !== '') {
					$parts[] = $district;
				}
				if ($addressLine !== '') {
					$parts[] = $addressLine;
				}
				$fullAddress = implode(', ', $parts);
				if ($fullAddress === '' && $area !== '') {
					$fullAddress = $area;
				}
			}
		}
		if ($fullAddress === '') {
			try {
				$potential = Vtiger_Record_Model::getInstanceById($recordId, 'Potentials');
				$contactId = (int)$potential->get('contact_id');
				if ($contactId > 0) {
					$contact = Vtiger_Record_Model::getInstanceById($contactId, 'Contacts');
					$street = trim((string)$contact->get('mailingstreet'));
					$city = trim((string)$contact->get('mailingcity'));
					$state = trim((string)$contact->get('mailingstate'));
					$parts = array_filter(array($street, $city, $state));
					$fullAddress = implode(', ', $parts);
				}
			} catch (Exception $e) {
				// ignore
			}
		}
		$viewer->assign('MK_OPP_FULL_ADDRESS', $fullAddress);
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->ensureSalesApp($request);
		$recordId = (int)$request->get('record');
		if ($recordId > 0 && strtolower((string)$request->get('view')) === 'detail') {
			require_once 'modules/Leads/models/ConvertService.php';
			try {
				Leads_ConvertService::ensurePotentialTagsFromLead($recordId);
			} catch (Exception $e) {
				// never block Opp detail on tag sync
			}
			$this->assignLinkedLeadAddress($request, $recordId);
			$this->assignInteractionLogBoot($request, $recordId);
		} else {
			$this->getViewer($request)->assign('MK_OPP_INTERACTION_LOG_JSON', '{"phone":"","contact_id":0,"contact_name":"","lead_id":0,"items":[]}');
		}
		try {
			require_once 'modules/HelpDesk/models/TagRuleEngineService.php';
			$this->getViewer($request)->assign('MK_OPP_TAG_LABELS_JSON', json_encode(
				HelpDesk_TagRuleEngineService::getInstance()->getScopeTagLabels('opp'),
				JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
			));
		} catch (Exception $e) {
			$this->getViewer($request)->assign('MK_OPP_TAG_LABELS_JSON', '{}');
		}
		parent::preProcess($request, false);
		$this->assignSalesApp($request);
		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	/**
	 * Summary tab AJAX reload — keep interaction log boot available.
	 */
	public function showModuleBasicView(Vtiger_Request $request) {
		$recordId = (int) $request->get('record');
		$this->assignInteractionLogBoot($request, $recordId);
		parent::showModuleBasicView($request);
	}
	/**
	 * Function to get activities
	 * @param Vtiger_Request $request
	 * @return <List of activity models>
	 */
	public function getActivities(Vtiger_Request $request) {
		$moduleName = 'Calendar';
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if($currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
			$moduleName = $request->getModule();
			$recordId = $request->get('record');

			$pageNumber = $request->get('page');
			if(empty ($pageNumber)) {
				$pageNumber = 1;
			}
			$pagingModel = new Vtiger_Paging_Model();
			$pagingModel->set('page', $pageNumber);
			$pagingModel->set('limit', 10);

			if(!$this->record) {
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
}
