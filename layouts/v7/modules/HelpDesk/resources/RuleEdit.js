Vtiger_Index_Js("HelpDesk_RuleEdit_Js", {}, {
	registerEvents: function () {
		this._super();

		var $ruleEditPage = jQuery('.helpdesk-ruleedit-page');
		if (!$ruleEditPage.length) return;

		// Hide ONLY the RuleEdit toolbar row rendered by preProcess:
		// <div class="module-breadcrumb module-breadcrumb-RuleEdit"> ... </div>
		jQuery('.module-breadcrumb-RuleEdit').closest('.module-action-bar').hide();
	}
});

