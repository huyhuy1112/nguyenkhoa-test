<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Potentials_Save_Action extends Vtiger_Save_Action {

	public function process(Vtiger_Request $request) {
		//Restrict to store indirect relationship from Potentials to Contacts
		$sourceModule = $request->get('sourceModule');
		$relationOperation = $request->get('relationOperation');
		$skip = true;

		if ($relationOperation && $sourceModule === 'Contacts') {
			$request->set('relationOperation', false);
			$skip = false;
		}

		parent::process($request);

		// to link the relation in updates
		if (!$skip) {
			$sourceRecordId = $request->get('sourceRecord');
			$focus = CRMEntity::getInstance($sourceModule);
			$destinationModule = $request->get('module');
			$destinationRecordId = $this->savedRecordId;
			$focus->trackLinkedInfo($sourceModule, $sourceRecordId, $destinationModule, $destinationRecordId);
		}
	}

	public function saveRecord($request) {
		$recordModel = parent::saveRecord($request);
		$this->applyModernListExtras($request, $recordModel);
		return $recordModel;
	}

	/**
	 * Persist list-parity extras: region/address profile + tags (+ confirm timestamp).
	 */
	protected function applyModernListExtras(Vtiger_Request $request, $recordModel) {
		if (!$recordModel) {
			return;
		}
		$recordId = (int) $recordModel->getId();
		if ($recordId <= 0) {
			return;
		}
		try {
			require_once 'modules/Potentials/models/ModernService.php';

			$tagsRaw = $request->get('mk_tags');
			$hasTags = !($tagsRaw === null || $tagsRaw === '');
			$tags = array();
			if ($hasTags) {
				if (is_string($tagsRaw)) {
					$decoded = json_decode($tagsRaw, true);
					$tags = is_array($decoded) ? $decoded : preg_split('/\s*,\s*/', $tagsRaw);
				} elseif (is_array($tagsRaw)) {
					$tags = $tagsRaw;
				}
				if (!is_array($tags)) {
					$tags = array();
				}
				Potentials_ModernService::saveTags($recordId, $tags);
			}

			$all = method_exists($request, 'getAll') ? $request->getAll() : $_REQUEST;
			$hasRegion = array_key_exists('mk_region', $all);
			$hasAddress = array_key_exists('mk_address', $all);
			if ($hasRegion || $hasAddress) {
				Potentials_ModernService::saveInlineLocation(
					$recordId,
					$request->get('mk_region'),
					$request->get('mk_address')
				);
			}

			if ($hasTags) {
				$confirm = '';
				foreach ($tags as $tg) {
					$key = strtolower(trim(decode_html((string) $tg)));
					if ($key === 'xac_nhan_tham_gia' || $key === 'khong_xac_nhan_tham_gia') {
						$confirm = $key;
						break;
					}
				}
				// Only touch confirmed_at when confirm state actually needs a profile update.
				$adb = PearDatabase::getInstance();
				$prof = $adb->pquery(
					'SELECT confirmed_at FROM bace_potential_profile WHERE potentialid = ?',
					array($recordId)
				);
				$hadConfirmedAt = ($prof && $adb->num_rows($prof) > 0
					&& trim((string) $adb->query_result($prof, 0, 'confirmed_at')) !== '');
				$shouldSet = false;
				if ($confirm === 'xac_nhan_tham_gia' && !$hadConfirmedAt) {
					$shouldSet = true;
				} elseif ($confirm === 'khong_xac_nhan_tham_gia' && $hadConfirmedAt) {
					$shouldSet = true;
				} elseif ($confirm === '' && $hadConfirmedAt) {
					$shouldSet = true;
				}
				if ($shouldSet) {
					Potentials_ModernService::setConfirmTag($recordId, $confirm);
				}
			}
		} catch (Exception $e) {
			global $log;
			if ($log) {
				$log->error('Potentials modern extras save: ' . $e->getMessage());
			}
		}
	}
}
