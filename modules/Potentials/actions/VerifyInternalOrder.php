<?php
/*+***********************************************************************************
 * Verify password for Internal Orders access in Potentials.
 * Password must NOT exist in JS.
 *************************************************************************************/

class Potentials_VerifyInternalOrder_Action extends Vtiger_Action_Controller {

	public function validateRequest(Vtiger_Request $request) {
		// Use write-access validation to enforce CSRF token on POST
		return $request->validateWriteAccess();
	}

	public function requiresPermission(Vtiger_Request $request) {
		// Must have access to Potentials module list
		return array(
			array('module_parameter' => 'module', 'action' => 'ListView'),
		);
	}

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);

		$password = (string)$request->getRaw('password');
		$ok = hash_equals('internal@123', $password);

		if (session_status() !== PHP_SESSION_ACTIVE) {
			@session_start();
		}

		if ($ok) {
			$_SESSION['internal_order_verified'] = true;
		}

		$response->setResult(array('success' => $ok));
		$response->emit();
	}
}

