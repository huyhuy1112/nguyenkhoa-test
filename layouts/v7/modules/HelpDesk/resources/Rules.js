/*+***********************************************************************************
 * HelpDesk Rules view controller
 *
 * Needed so app.controller() can instantiate a controller and invoke Vtiger_Index_Js.registerEvents(),
 * which registers the app-menu (hamburger) toggle handlers.
 *************************************************************************************/

Vtiger_Index_Js('HelpDesk_Rules_Js', {}, {
	registerEvents: function () {
		this._super();

		var $rulesPage = jQuery('.helpdesk-rules-page');
		if (!$rulesPage.length) {
			return;
		}

		// Hide HelpDesk Tickets module action bar on Rules list only (same pattern as RuleEdit).
		var $bc = jQuery('.module-breadcrumb-Rules');
		if ($bc.length) {
			$bc.closest('.module-action-bar').hide();
		} else {
			$rulesPage.closest('.main-container').find('.module-action-bar').first().hide();
		}
	}
});

