Vtiger_Index_Js("HelpDesk_RuleDetail_Js", {}, {
	registerEvents: function () {
		this._super();

		var $page = jQuery('.mk-hd-rule-detail-page');
		if (!$page.length) {
			return;
		}

		jQuery('.module-breadcrumb-RuleDetail').closest('.module-action-bar').hide();
	}
});
