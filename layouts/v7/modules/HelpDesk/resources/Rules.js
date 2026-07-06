/*+***********************************************************************************
 * HelpDesk Rules view — Tag Rule Engine (Quản lý rule / tag / kịch bản)
 *************************************************************************************/

Vtiger_Index_Js('HelpDesk_Rules_Js', {}, {
	registerEvents: function () {
		this._super();

		var $rulesPage = jQuery('.helpdesk-rules-page');
		if (!$rulesPage.length) {
			return;
		}

		var $bc = jQuery('.module-breadcrumb-Rules');
		if ($bc.length) {
			$bc.closest('.module-action-bar').hide();
		} else {
			$rulesPage.closest('.main-container').find('.module-action-bar').first().hide();
		}

		if (window.MkTagRuleEngine && typeof window.MkTagRuleEngine.init === 'function') {
			window.MkTagRuleEngine.init();
		}
	}
});
