<?php
/*+***********************************************************************************
 * Modern Tickets List View for HelpDesk module.
 *
 * Uses custom TicketService + tickets* tables instead of legacy vtiger_troubletickets
 * while still keeping HelpDesk as the vtiger module shell.
 ************************************************************************************/

require_once 'modules/HelpDesk/models/TicketService.php';

class HelpDesk_List_View extends Vtiger_Index_View {

	protected function preProcessTplName(Vtiger_Request $request) {
		return 'ListViewPreProcess.tpl';
	}

	protected function assignSupportContext(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', Vtiger_Module_Model::getInstance($moduleName));
		$appName = $request->get('app');
		$viewer->assign('SELECTED_MENU_CATEGORY', !empty($appName) ? $appName : 'SUPPORT');
		$viewer->assign('VIEW', 'List');
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->assignSupportContext($request);
		parent::preProcess($request, $display);
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('ListViewPostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}

	private static function assignedInitials($assignedUsers) {
		$assignedUsers = trim((string)$assignedUsers);
		if ($assignedUsers === '') {
			return '';
		}
		$names = preg_split('/\s*,\s*/', $assignedUsers);
		$first = trim($names[0]);
		if ($first === '') {
			return '';
		}
		$parts = preg_split('/\s+/', $first);
		if (count($parts) >= 2) {
			return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
		}
		return strtoupper(substr($first, 0, 2));
	}

	public function process(Vtiger_Request $request) {
		global $current_user;

		$moduleName = $request->getModule();
		$viewer     = $this->getViewer($request);
		$db         = PearDatabase::getInstance();

		// Basic filters
		$statusFilter   = trim((string)$request->get('status'));
		$priorityFilter = trim((string)$request->get('priority'));
		$searchText     = trim((string)$request->get('search'));

		$page      = (int)$request->get('page');
		$page      = $page > 0 ? $page : 1;
		$pageLimit = 20;
		$offset    = ($page - 1) * $pageLimit;

		$where  = ' WHERE ce.deleted = 0 ';
		$params = [];

		if ($statusFilter !== '') {
			$where   .= ' AND t.status = ?';
			$params[] = $statusFilter;
		}

		if ($priorityFilter !== '') {
			$where   .= ' AND t.priority = ?';
			$params[] = $priorityFilter;
		}

		if ($searchText !== '') {
			$where   .= ' AND (t.ticket_code LIKE ? OR t.subject LIKE ?)';
			$params[] = '%' . $searchText . '%';
			$params[] = '%' . $searchText . '%';
		}

		// Total count
		$countSql = "SELECT COUNT(DISTINCT t.id) AS total
			FROM tickets t
			INNER JOIN vtiger_crmentity ce ON ce.crmid = t.customer_id
			$where";
		$countRes = $db->pquery($countSql, $params);
		$total    = $countRes && $db->num_rows($countRes) ? (int)$db->query_result($countRes, 0, 'total') : 0;

		// Main query: tickets with customer label + assigned users
		$listSql = "SELECT
				t.*,
				ce.label AS customer_name,
				GROUP_CONCAT(DISTINCT CONCAT(u.first_name,' ',u.last_name) SEPARATOR ', ') AS assigned_users
			FROM tickets t
			INNER JOIN vtiger_crmentity ce ON ce.crmid = t.customer_id
			LEFT JOIN ticket_assignments ta ON ta.ticket_id = t.id
			LEFT JOIN vtiger_users u ON u.id = ta.user_id
			$where
			GROUP BY t.id
			ORDER BY t.created_at DESC
			LIMIT $offset, $pageLimit";

		$listRes = $db->pquery($listSql, $params);

		$tickets = [];
		$nowTs   = time();

		if ($listRes && $db->num_rows($listRes) > 0) {
			while ($row = $db->fetchByAssoc($listRes)) {
				// Compute SLA countdown
				$slaCountdown = null;
				if (!empty($row['sla_due_at']) && in_array($row['status'], ['Open', 'In Progress'], true)) {
					$slaTs = strtotime($row['sla_due_at']);
					if ($slaTs !== false) {
						$diffSec      = $slaTs - $nowTs;
						$slaCountdown = $diffSec;
					}
				}

				$row['sla_countdown']     = $slaCountdown;
				$row['assigned_users']    = $row['assigned_users'] ?? '';
				$row['assigned_initials'] = self::assignedInitials($row['assigned_users']);
				$tickets[]                = $row;
			}
		}

		// Stats cards
		$stats = [
			'total_open'   => 0,
			'total_overdue'=> 0,
			'by_priority'  => [
				'Critical' => 0,
				'High'     => 0,
				'Medium'   => 0,
				'Low'      => 0,
			],
		];

		$statsSql = "SELECT
				COUNT(*) AS cnt,
				SUM(CASE WHEN status = 'Open' OR status = 'In Progress' THEN 1 ELSE 0 END) AS open_cnt,
				SUM(CASE WHEN is_overdue = 1 THEN 1 ELSE 0 END) AS overdue_cnt
			FROM tickets t
			INNER JOIN vtiger_crmentity ce ON ce.crmid = t.customer_id
			WHERE ce.deleted = 0";
		$statsRes = $db->pquery($statsSql, []);
		if ($statsRes && $db->num_rows($statsRes) > 0) {
			$stats['total_open']    = (int)$db->query_result($statsRes, 0, 'open_cnt');
			$stats['total_overdue'] = (int)$db->query_result($statsRes, 0, 'overdue_cnt');
		}

		$prioSql = "SELECT priority, COUNT(*) AS cnt
			FROM tickets t
			INNER JOIN vtiger_crmentity ce ON ce.crmid = t.customer_id
			WHERE ce.deleted = 0
			GROUP BY priority";
		$prioRes = $db->pquery($prioSql, []);
		if ($prioRes && $db->num_rows($prioRes) > 0) {
			while ($row = $db->fetchByAssoc($prioRes)) {
				$p = $row['priority'];
				if (isset($stats['by_priority'][$p])) {
					$stats['by_priority'][$p] = (int)$row['cnt'];
				}
			}
		}

		// Pagination info
		$pageCount = $pageLimit > 0 ? (int)ceil($total / $pageLimit) : 1;
		$showFrom  = $total > 0 ? $offset + 1 : 0;
		$showTo    = min($offset + count($tickets), $total);

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('TICKETS', $tickets);
		$viewer->assign('TOTAL_COUNT', $total);
		$viewer->assign('SHOW_FROM', $showFrom);
		$viewer->assign('SHOW_TO', $showTo);
		$viewer->assign('TICKET_STATS', $stats);
		$viewer->assign('CURRENT_PAGE', $page);
		$viewer->assign('PAGE_COUNT', $pageCount);
		$viewer->assign('PAGE_LIMIT', $pageLimit);

		$viewer->assign('FILTER_STATUS', $statusFilter);
		$viewer->assign('FILTER_PRIORITY', $priorityFilter);
		$viewer->assign('FILTER_SEARCH', $searchText);

		$viewer->view('ListViewContents.tpl', $moduleName);
	}
}

