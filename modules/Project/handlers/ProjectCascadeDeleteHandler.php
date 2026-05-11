<?php
/*+**********************************************************************************
 * Soft-delete ProjectTask records when their parent Project is deleted.
 * Optional: vtiger.entity.afterdelete — delegates to Project::cascadeSoftDeleteProjectTasks().
 * Primary path is Project::trash() override (no DB registration required).
 *************************************************************************************/

require_once 'include/events/VTEventHandler.inc';

class ProjectCascadeDeleteHandler extends VTEventHandler {

	/**
	 * @param string $eventName
	 * @param VTEntityData $entityData
	 */
	public function handleEvent($eventName, $entityData) {
		if ($eventName !== 'vtiger.entity.afterdelete') {
			return;
		}
		if (!$entityData) {
			return;
		}

		$projectId = (int) $entityData->getId();
		if ($projectId <= 0) {
			return;
		}

		// getModuleName() is CRMEntity class name (normally "Project"); fall back to setype.
		$moduleName = $entityData->getModuleName();
		$isProject = ($moduleName === 'Project');
		if (!$isProject && function_exists('getSalesEntityType')) {
			$isProject = (getSalesEntityType($projectId) === 'Project');
		}
		if (!$isProject) {
			return;
		}

		if (!class_exists('Project')) {
			require_once 'modules/Project/Project.php';
		}
		Project::cascadeSoftDeleteProjectTasks($projectId);
	}
}
