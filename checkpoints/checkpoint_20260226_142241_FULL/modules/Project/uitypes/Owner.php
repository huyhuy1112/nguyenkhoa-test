<?php
/*+***********************************************************************************
 * Project Owner UIType: hiển thị team group name khi project assign theo nhóm
 *************************************************************************************/

require_once 'modules/Vtiger/uitypes/Owner.php';

class Project_Owner_UIType extends Vtiger_Owner_UIType {

	/**
	 * Khi project assign theo team group, hiển thị tên nhóm thay vì owner (tránh cột Assigned To trống).
	 */
	public function getDisplayValue($value, $record = false, $recordInstance = false) {
		$projectId = null;
		if ($recordInstance && method_exists($recordInstance, 'getId')) {
			$projectId = (int) $recordInstance->getId();
		} elseif ($recordInstance && isset($recordInstance->rawData['projectid'])) {
			$projectId = (int) $recordInstance->rawData['projectid'];
		} elseif (is_array($record) && isset($record['projectid'])) {
			$projectId = (int) $record['projectid'];
		} elseif (is_numeric($record)) {
			$projectId = (int) $record;
		}

		if ($projectId > 0) {
			$db = PearDatabase::getInstance();
			$gr = $db->pquery(
				"SELECT tg.group_name FROM vtiger_project_team_groups ptg
				 INNER JOIN vtiger_team_groups tg ON ptg.team_groupid = tg.groupid
				 WHERE ptg.projectid = ?",
				array($projectId)
			);
			if ($gr && $db->num_rows($gr) > 0) {
				$groupName = $db->query_result($gr, 0, 'group_name');
				return $groupName ?: parent::getDisplayValue($value, $record, $recordInstance);
			}
		}

		return parent::getDisplayValue($value, $record, $recordInstance);
	}
}
