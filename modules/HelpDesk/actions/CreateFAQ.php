<?php
/*+***********************************************************************************
 * Redirect from HelpDesk Ticket to SupportFAQ Create view with prefilled fields.
 * No core modifications.
 *************************************************************************************/

class HelpDesk_CreateFAQ_Action extends Vtiger_Action_Controller {

	public function validateRequest(Vtiger_Request $request) {
		return $request->validateReadAccess();
	}

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record');
		$permissions[] = array('module_parameter' => 'custom_module', 'action' => 'CreateView');
		$request->set('custom_module', 'SupportFAQ');
		return $permissions;
	}

	public function process(Vtiger_Request $request) {
		$recordId = $request->get('record');
		if (empty($recordId)) {
			throw new AppException(vtranslate('LBL_RECORD_NOT_FOUND'));
		}

		$question = '';
		$description = '';

		// Prefer modern TicketService (custom ticket system) when available
		// TicketDetail view uses TicketService, so load it explicitly here.
		if (!class_exists('HelpDesk_TicketService')) {
			@include_once 'modules/HelpDesk/models/TicketService.php';
		}
		if (class_exists('HelpDesk_TicketService')) {
			$service = HelpDesk_TicketService::getInstance();
			$t = $service->getTicketById((int)$recordId);
			if (is_array($t)) {
				$question = (string)($t['subject'] ?? '');
				$description = (string)($t['description'] ?? '');
			}
		}

		// Fallback to legacy HelpDesk entity fields
		if ($question === '' && $description === '') {
			$ticket = Vtiger_Record_Model::getInstanceById($recordId, 'HelpDesk');
			$question = (string)$ticket->get('ticket_title');
			$description = (string)$ticket->get('description');
		}

		$app = $request->get('app');
		$redirectUrl = 'index.php?module=SupportFAQ&view=Edit'
			. '&question=' . urlencode($question)
			. '&description=' . urlencode($description)
			. '&related_ticket_id=' . urlencode($recordId);

		if (!empty($app)) {
			$redirectUrl .= '&app=' . urlencode($app);
		}

		if (ob_get_level() > 0) {
			ob_clean();
		}
		header('Location: ' . $redirectUrl);
		exit;
	}
}

