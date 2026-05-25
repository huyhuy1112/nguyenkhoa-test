<?php

class ProductsServices_DetailView_Model extends Vtiger_DetailView_Model {

	/**
	 * Ensure Documents + ModComments summary widgets appear on ProductsServices Detail
	 * even when vtiger_relatedlists / fieldmodulerel were not fully provisioned.
	 */
	public function getWidgets() {
		$widgets = parent::getWidgets();
		$labels = array();
		foreach ($widgets as $widget) {
			$labels[$widget->getLabel()] = true;
		}

		$moduleName = $this->getModuleName();
		$recordId = $this->getRecord()->getId();
		$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();

		$modCommentsModel = Vtiger_Module_Model::getInstance('ModComments');
		if (!isset($labels['ModComments']) && $modCommentsModel && $modCommentsModel->isActive()
				&& $modCommentsModel->isPermitted('DetailView')) {
			$widgets[] = Vtiger_Link_Model::getInstanceFromValues(array(
				'linktype' => 'DETAILVIEWWIDGET',
				'linklabel' => 'ModComments',
				'linkurl' => 'module=' . $moduleName . '&view=Detail&record=' . $recordId
					. '&mode=showRecentComments&page=1&limit=5',
			));
			$labels['ModComments'] = true;
		}

		$documentsInstance = Vtiger_Module_Model::getInstance('Documents');
		if (!isset($labels['Documents']) && $documentsInstance
				&& $userPrivilegesModel->hasModuleActionPermission($documentsInstance->getId(), 'DetailView')) {
			$createPermission = $userPrivilegesModel->hasModuleActionPermission($documentsInstance->getId(), 'CreateView');
			$widgets[] = Vtiger_Link_Model::getInstanceFromValues(array(
				'linktype' => 'DETAILVIEWWIDGET',
				'linklabel' => 'Documents',
				'linkName' => $documentsInstance->getName(),
				'linkurl' => 'module=' . $moduleName . '&view=Detail&record=' . $recordId
					. '&relatedModule=Documents&mode=showRelatedRecords&page=1&limit=5',
				'action' => ($createPermission === true) ? array('Add') : array(),
				'actionURL' => $documentsInstance->getQuickCreateUrl(),
			));
		}

		return $widgets;
	}
}
