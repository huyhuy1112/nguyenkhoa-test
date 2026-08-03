<?php
/*+***********************************************************************************
 * Potentials Edit — premium Create workspace (SALES, new record). Stock Save + all fields.
 *************************************************************************************/

class Potentials_Edit_View extends Vtiger_Edit_View {

	protected function ensureSalesApp(Vtiger_Request $request) {
		require_once 'modules/Potentials/helpers/SalesAppGuard.php';
		Potentials_SalesAppGuard::enforce($request);
	}

	protected function isMkModernOpportunityCreate(Vtiger_Request $request) {
		if ($request->get('displayMode') === 'overlay') {
			return false;
		}
		return true;
	}

	protected function assignModernContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$user = Users_Record_Model::getCurrentUserModel();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$viewer->assign('SELECTED_MENU_CATEGORY', 'SALES');
		$viewer->assign('VIEW', 'Edit');
		$viewer->assign('MENU_SELECTED_MODULENAME', 'Potentials');
		$viewer->assign('MK_MODERN_OPPORTUNITY_CREATE', true);
		$viewer->assign('IS_DUPLICATE', $request->get('isDuplicate'));
		$viewer->assign('MK_OPP_OWNER_NAME', trim($user->getName()));
		$viewer->assign('MK_OPP_OWNER_INITIAL', $this->getUserInitial($user->getName()));

		$meta = array(
			'tags' => array(),
			'mk_region' => '',
			'mk_address' => '',
		);
		$recordId = (int) $request->get('record');
		if ($recordId > 0 && !$request->get('isDuplicate')) {
			try {
				global $current_user;
				require_once 'modules/Vtiger/models/Tag.php';
				require_once 'modules/Potentials/models/ModernService.php';
				$userId = (int) $current_user->id;
				$tagModels = Vtiger_Tag_Model::getAllAccessible($userId, 'Potentials', $recordId);
				foreach ($tagModels as $tagModel) {
					$name = trim(decode_html((string) $tagModel->getName()));
					if ($name !== '') {
						$meta['tags'][] = $name;
					}
				}
				Potentials_ModernService::ensureProfileSchema();
				$adb = PearDatabase::getInstance();
				$res = $adb->pquery(
					'SELECT district, address_line FROM bace_potential_profile WHERE potentialid = ?',
					array($recordId)
				);
				if ($res && $adb->num_rows($res) > 0) {
					$district = trim(decode_html((string) $adb->query_result($res, 0, 'district')));
					$meta['mk_address'] = trim(decode_html((string) $adb->query_result($res, 0, 'address_line')));
					if (preg_match('/([123])/', $district, $m)) {
						$meta['mk_region'] = 'kv' . $m[1];
					}
				}
				if ($meta['mk_region'] === '') {
					foreach ($meta['tags'] as $tg) {
						$key = strtolower(trim($tg));
						if (preg_match('/^kv([123])$/', $key, $km)) {
							$meta['mk_region'] = 'kv' . $km[1];
							break;
						}
					}
				}
			} catch (Exception $e) {
				// keep defaults
			}
		}
		$viewer->assign(
			'MK_OPP_EDIT_META_JSON',
			json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
		);
	}

	protected function getUserInitial($name) {
		$name = trim((string)$name);
		if ($name === '') {
			return '?';
		}
		$parts = preg_split('/\s+/', $name);
		if (count($parts) >= 2) {
			return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
		}
		return strtoupper(substr($name, 0, 1));
	}

	protected function redirectMarketingToSales(Vtiger_Request $request) {
		$app = strtoupper((string)$request->get('app'));
		if ($app === 'MARKETING' && empty($request->get('record'))) {
			header('Location: index.php?module=Potentials&view=Edit&app=SALES');
			exit;
		}
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		if ($request->get('displayMode') !== 'overlay') {
			$this->ensureSalesApp($request);
		}
		if ($this->isMkModernOpportunityCreate($request)) {
			$this->redirectMarketingToSales($request);
			parent::preProcess($request, false);
			$this->assignModernContext($request);
			if ($display) {
				$this->preProcessDisplay($request);
			}
			return;
		}
		parent::preProcess($request, $display);
	}

	public function preProcessTplName(Vtiger_Request $request) {
		if ($this->isMkModernOpportunityCreate($request)) {
			return 'EditViewPreProcess.tpl';
		}
		return parent::preProcessTplName($request);
	}

	public function postProcess(Vtiger_Request $request) {
		if ($this->isMkModernOpportunityCreate($request)) {
			$viewer = $this->getViewer($request);
			$viewer->view('EditViewPostProcess.tpl', $request->getModule());
			Vtiger_Basic_View::postProcess($request);
			return;
		}
		parent::postProcess($request);
	}

	public function process(Vtiger_Request $request) {
		if ($this->isMkModernOpportunityCreate($request)) {
			$this->assignModernContext($request);
		}
		parent::process($request);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		// OpportunityMkEdit.css is loaded once in EditViewPreProcess.tpl (after SalesMkEditShell)
		// so it wins the cascade. Do not register it here — ?v= query also breaks file_exists().
		return $headerCssInstances;
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			'~layouts/' . Vtiger_Viewer::getDefaultLayoutName() . '/modules/Potentials/resources/EditLockAutoFields.js',
		);
		// OpportunityMkEdit.js + PotentialsLovableRef.js load in EditViewPreProcess.tpl
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}
