<?php
/************************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class DocumentTemplate extends CRMEntity {
	/**
	 * Create/attach DocumentTemplate to Tools menu and seed defaults.
	 *
	 * This module is an MVP template-management system:
	 * - list/search/filter templates by feature
	 * - copy/edit/delete templates
	 * - default templates are protected from deletion
	 */
	public function vtlib_handler($moduleName, $event_type) {
		// We keep install logic idempotent.
		global $adb;
		if (!$adb) {
			$adb = PearDatabase::getInstance();
		}

		if ($event_type === 'module.postinstall') {
			$tabid = getTabid($moduleName);
			if ($tabid) {
				// Bind DocumentTemplate tab to TOOLS app.
				$maxSeqResult = $adb->pquery("SELECT MAX(sequence) as max_seq FROM vtiger_app2tab WHERE appname = ?", array('TOOLS'));
				$maxSeq = (int) $adb->query_result($maxSeqResult, 0, 'max_seq');
				$nextSeq = $maxSeq ? ($maxSeq + 1) : 1;

				$checkResult = $adb->pquery("SELECT 1 FROM vtiger_app2tab WHERE appname = ? AND tabid = ?", array('TOOLS', $tabid));
				if ($adb->num_rows($checkResult) == 0) {
					$adb->pquery(
						"INSERT INTO vtiger_app2tab (appname, tabid, sequence, visible) VALUES (?, ?, ?, ?)",
						array('TOOLS', $tabid, $nextSeq, 1)
					);
				}
			}

			// Seed protected defaults (if table exists and no defaults yet).
			try {
				$db = PearDatabase::getInstance();
				$seedFeatures = array('Invoice', 'Quote', 'Contract');
				foreach ($seedFeatures as $feature) {
					$result = $db->pquery(
						"SELECT templateid FROM vtiger_documenttemplates WHERE feature = ? AND isdefault = 1 LIMIT 1",
						array($feature)
					);
					if ($db->num_rows($result) > 0) {
						continue;
					}

					$userId = Users_Record_Model::getCurrentUserModel()->getId();
					$now = date('Y-m-d H:i:s');
					$templatename = $feature . ' - Default';
					$description = 'Default ' . $feature . ' template (protected).';
					$content = '';

					$db->pquery(
						"INSERT INTO vtiger_documenttemplates (templatename, feature, description, content, version, isdefault, createdby, updatedby, createdtime, updatedtime, deleted)
						 VALUES (?,?,?,?,1,1,?,?,?,?,0)",
						array($templatename, $feature, $description, $content, $userId, $userId, $now, $now)
					);
				}
			} catch (Exception $e) {
				// Ignore seeding errors; module installation should still succeed.
			}
		}
	}
}

?>
