<?php
/**
 * OPTIONAL repair/backfill script.
 *
 * For tickets missing ticket_sla rows, generate SLA rows using current active rules.
 * Idempotent: skips tickets that already have at least 1 ticket_sla row.
 *
 * Usage:
 *   php modules/HelpDesk/scripts/BackfillTicketSla.php
 */

chdir(dirname(__FILE__) . '/../../..');

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'modules/HelpDesk/models/SupportRulesService.php';

$db = PearDatabase::getInstance();
$service = HelpDesk_SupportRulesService::getInstance();

$res = $db->pquery(
	"SELECT t.id, t.customer_id, t.created_at
	   FROM tickets t
	  WHERE t.customer_id IS NOT NULL
	    AND t.customer_id > 0
	    AND NOT EXISTS (SELECT 1 FROM ticket_sla ts WHERE ts.ticket_id = t.id)
	  ORDER BY t.id ASC",
	[]
);

$countTickets = 0;
$countInsertedTickets = 0;

if ($res && $db->num_rows($res) > 0) {
	while ($row = $db->fetchByAssoc($res)) {
		$countTickets++;
		$ticketId = (int)$row['id'];
		$customerId = (int)$row['customer_id'];
		$createdAt = (string)$row['created_at'];
		if ($ticketId <= 0 || $customerId <= 0) {
			continue;
		}
		$service->createSlaForTicket($ticketId, $customerId, $createdAt ?: date('Y-m-d H:i:s'));
		$countInsertedTickets++;
		echo "Backfilled SLA for ticket_id={$ticketId}\n";
	}
}

echo "Done. Tickets missing SLA: {$countTickets}. Tickets processed: {$countInsertedTickets}.\n";

