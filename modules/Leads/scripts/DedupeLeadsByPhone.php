<?php
/*+***********************************************************************************
 * Deduplicate modern Leads that share the same phone (or email when phone empty).
 *
 * Dry run (default):
 *   php -f modules/Leads/scripts/DedupeLeadsByPhone.php
 *
 * Apply deletes:
 *   php -f modules/Leads/scripts/DedupeLeadsByPhone.php -- --apply
 *************************************************************************************/

chdir(dirname(__DIR__, 3));
require_once 'vendor/autoload.php';
require_once 'config.php';
include_once 'vtlib/Vtiger/Cron.php';
vimport('includes.runtime.EntryPoint');
require_once 'modules/Users/Users.php';
require_once 'modules/Leads/models/ModernService.php';

global $current_user;
$current_user = Users::getActiveAdminUser();

$apply = in_array('--apply', $argv ?? array(), true);
$dryRun = !$apply;

echo "=== Dedupe Modern Leads by phone/email ===\n";
echo $dryRun ? "Mode: DRY RUN (pass --apply to delete duplicates)\n" : "Mode: APPLY (deleting duplicates)\n";

$result = Leads_ModernService::dedupeModernLeadsByPhone($dryRun);

echo "Duplicate groups: {$result['groups']}\n";
echo ($dryRun ? 'Would delete' : 'Deleted') . ": {$result['deleted']}\n";

foreach ($result['report'] as $row) {
	echo "  keep #{$row['keep']} | remove #{$row['delete']} | {$row['group']}\n";
}

echo "Done.\n";
