<?php

class ProductsServices_Module_Model extends Vtiger_Module_Model {

	/**
	 * Allow ModComments summary widget when ModComments is active (fieldmodulerel may be unset on custom module).
	 */
	public function isCommentEnabled() {
		if ($this->getName() === 'ProductsServices') {
			$commentsModuleModel = Vtiger_Module_Model::getInstance('ModComments');
			if ($commentsModuleModel && $commentsModuleModel->isActive()) {
				return true;
			}
		}
		return parent::isCommentEnabled();
	}

	/**
	 * Return full icon markup like Vtiger_Module_Model::getModuleIcon(), not a bare CSS class string.
	 * Bare strings (e.g. "fa fa-cubes") render as visible text in Smarty templates.
	 */
	public function getModuleIcon() {
		$moduleName = $this->getName();
		$title = vtranslate($moduleName, $moduleName);

		return "<i class='fa fa-cubes' title='$title'></i>";
	}
}
