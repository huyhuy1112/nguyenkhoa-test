/*+***********************************************************************************
 * HelpDesk TicketDetail view controller
 *
 * Needed so app.controller() can instantiate a controller and invoke Vtiger_Index_Js.registerEvents(),
 * which registers the app-menu (hamburger) toggle handlers.
 *************************************************************************************/

Vtiger_Index_Js('HelpDesk_TicketDetail_Js', {}, {
	registerEvents: function () {
		this._super();
	}
});

