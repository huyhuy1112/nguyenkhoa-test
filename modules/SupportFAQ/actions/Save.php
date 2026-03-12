<?php
/*+***********************************************************************************
 * SupportFAQ Save action.
 * Adds Throwable handling to avoid white screen and logs errors for debugging.
 *************************************************************************************/

class SupportFAQ_Save_Action extends Vtiger_Save_Action {

	public function process(Vtiger_Request $request) {
		try {
			parent::process($request);
		} catch (Throwable $t) {
			$this->logThrowable($t, $request);

			// Render error page instead of blank screen
			$viewer = new Vtiger_Viewer();
			$viewer->assign('ERROR_MESSAGE', $t->getMessage());
			$viewer->view('OperationNotPermitted.tpl', 'Vtiger');
		}
	}

	private function logThrowable(Throwable $t, Vtiger_Request $request): void {
		$logFile = 'logs/supportfaq_save_error.log';
		$line = sprintf(
			"[%s] %s %s | module=%s action=%s record=%s | %s in %s:%s\n%s\n\n",
			date('Y-m-d H:i:s'),
			get_class($t),
			$t->getMessage(),
			$request->getModule(),
			$request->get('action'),
			$request->get('record'),
			$t->getTraceAsString(),
			$t->getFile(),
			$t->getLine(),
			str_repeat('-', 60)
		);
		@file_put_contents($logFile, $line, FILE_APPEND);
	}
}

