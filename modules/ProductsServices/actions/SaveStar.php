<?php
/**
 * Persist follow/star for ProductsServices.
 * Core SaveStar only writes starred when vtiger_field.starred exists for the module
 * (often missing on this custom module), so the list star never survived a reload.
 */
require_once 'modules/Vtiger/actions/SaveStar.php';

class ProductsServices_SaveStar_Action extends Vtiger_SaveStar_Action {

	public function requiresPermission(Vtiger_Request $request) {
		return array(
			array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record'),
		);
	}

	public function checkPermission(Vtiger_Request $request) {
		if (!Users_Privileges_Model::isPermitted('ProductsServices', 'DetailView')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		if ($request->has('selected_ids')) {
			return parent::checkPermission($request);
		}
		$recordId = (int) $request->get('record');
		if ($recordId > 0 && !Users_Privileges_Model::isPermitted('ProductsServices', 'DetailView', $recordId)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		return true;
	}

	public function process(Vtiger_Request $request) {
		if ($request->has('selected_ids')) {
			$recordIds = $this->followRecordIds;
		} else {
			$recordIds = array($request->get('record'));
		}

		$starred = ((int) $request->get('value')) ? 1 : 0;
		$saved = 0;
		foreach ($recordIds as $recordId) {
			$recordId = (int) $recordId;
			if ($recordId <= 0) {
				continue;
			}
			if ($this->upsertStarred($recordId, $starred)) {
				$saved++;
			}
		}

		$this->ensureStarredField();

		$response = new Vtiger_Response();
		if ($saved <= 0) {
			$response->setError(500, 'Không lưu được theo dõi.');
		} else {
			$response->setResult(array('success' => true, 'starred' => $starred, 'count' => $saved));
		}
		$response->emit();
	}

	/**
	 * @return bool
	 */
	protected function upsertStarred($recordId, $starred) {
		global $current_user;
		$userId = $current_user && !empty($current_user->id) ? (int) $current_user->id : 0;
		if ($userId <= 0) {
			return false;
		}

		$db = PearDatabase::getInstance();
		$table = 'vtiger_crmentity_user_field';
		if (!Vtiger_Utils::CheckTable($table)) {
			Vtiger_Utils::CreateTable(
				$table,
				'(`recordid` INT(25) NOT NULL,
				 `userid` INT(25) NOT NULL,
				 `starred` INT(1) DEFAULT 0,
				 UNIQUE KEY `record_user_idx` (`recordid`, `userid`))',
				true
			);
		}

		$exists = $db->pquery(
			"SELECT 1 FROM {$table} WHERE recordid = ? AND userid = ?",
			array($recordId, $userId)
		);
		if ($exists && $db->num_rows($exists) > 0) {
			$ok = $db->pquery(
				"UPDATE {$table} SET starred = ? WHERE recordid = ? AND userid = ?",
				array($starred, $recordId, $userId)
			);
			return (bool) $ok;
		}

		$ok = $db->pquery(
			"INSERT INTO {$table} (recordid, userid, starred) VALUES (?, ?, ?)",
			array($recordId, $userId, $starred)
		);
		if ($ok) {
			return true;
		}

		$ok = $db->pquery(
			"INSERT INTO {$table} (recordid, userid) VALUES (?, ?)",
			array($recordId, $userId)
		);
		if (!$ok) {
			return false;
		}
		$ok = $db->pquery(
			"UPDATE {$table} SET starred = ? WHERE recordid = ? AND userid = ?",
			array($starred, $recordId, $userId)
		);
		return (bool) $ok;
	}

	/**
	 * Register starred field so Detail Follow and core retrieve_entity_info can see it.
	 */
	protected function ensureStarredField() {
		try {
			$moduleInstance = Vtiger_Module::getInstance('ProductsServices');
			if (!$moduleInstance) {
				return;
			}
			if (Vtiger_Field::getInstance('starred', $moduleInstance)) {
				return;
			}
			$db = PearDatabase::getInstance();
			$blockRes = $db->pquery(
				'SELECT blocklabel FROM vtiger_blocks WHERE tabid = ? ORDER BY sequence LIMIT 1',
				array($moduleInstance->id)
			);
			if (!$blockRes || !$db->num_rows($blockRes)) {
				return;
			}
			$blockLabel = $db->query_result($blockRes, 0, 'blocklabel');
			$blockInstance = Vtiger_Block::getInstance($blockLabel, $moduleInstance);
			if (!$blockInstance) {
				return;
			}
			$field = new Vtiger_Field();
			$field->name = 'starred';
			$field->label = 'starred';
			$field->table = Vtiger_Functions::getUserSpecificTableName('ProductsServices');
			$field->column = 'starred';
			$field->presence = 2;
			$field->displaytype = 6;
			$field->readonly = 1;
			$field->uitype = 56;
			$field->typeofdata = 'C~O';
			$field->quickcreate = 3;
			$field->masseditable = 0;
			$blockInstance->addField($field);
		} catch (Exception $e) {
			/* upsert already persisted; field metadata is optional */
		}
	}
}
