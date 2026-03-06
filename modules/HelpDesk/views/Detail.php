<?php
/*+***********************************************************************************
 * Legacy Detail view redirector for HelpDesk.
 *
 * Any old links that still use view=Detail will be redirected
 * to the new TicketDetail view that uses the modern ticket system.
 ************************************************************************************/

class HelpDesk_Detail_View extends Vtiger_Detail_View {

	/** Bỏ permission + tồn tại record check, chỉ redirect thẳng sang TicketDetail. */
	public function requiresPermission(Vtiger_Request $request) { return []; }
	public function checkPermission(Vtiger_Request $request) { return true; }

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->redirectToNewDetail($request);
	}

	public function process(Vtiger_Request $request) {
		$this->redirectToNewDetail($request);
	}

	protected function redirectToNewDetail(Vtiger_Request $request) {
		$recordId = (int)$request->get('record');
		if ($recordId > 0) {
			header('Location: index.php?module=HelpDesk&view=TicketDetail&record=' . $recordId);
			exit;
		}
		header('Location: index.php?module=HelpDesk&view=List');
		exit;
	}
}
