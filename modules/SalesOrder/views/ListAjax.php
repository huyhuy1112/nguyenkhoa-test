<?php
/*+***********************************************************************************
 * SalesOrder ListAjax: inherit SALES list header adjustment (status replaces quote).
 *************************************************************************************/

class SalesOrder_ListAjax_View extends SalesOrder_List_View {

	function preProcess(Vtiger_Request $request, $display = true) {
		return true;
	}

	function postProcess(Vtiger_Request $request) {
		return true;
	}
}
